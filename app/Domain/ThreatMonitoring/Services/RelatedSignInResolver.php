<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Domain\ThreatMonitoring\Normalizers\GraphSignInNormalizer;
use App\Models\Integration;
use App\Models\ThreatEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class RelatedSignInResolver
{
    public function __construct(
        private readonly MicrosoftGraphClient $graphClient,
        private readonly GraphSignInNormalizer $signInNormalizer,
    ) {
    }

    public function forRiskDetection(ThreatEvent $event): ?array
    {
        if ($event->event_type !== 'risk_detection') {
            return null;
        }

        $normalized = $event->normalized_payload ?? [];
        $requestId = data_get($normalized, 'request_id');
        $correlationId = data_get($normalized, 'correlation_id');

        if ($requestId) {
            $matched = ThreatEvent::query()
                ->where('integration_id', $event->integration_id)
                ->where('event_type', 'sign_in')
                ->where('provider_event_key', 'sign_in:' . $requestId)
                ->latest('occurred_at')
                ->first();

            if ($matched) {
                return $this->toContext($matched, 'request_id');
            }
        }

        if ($correlationId) {
            $matched = ThreatEvent::query()
                ->where('integration_id', $event->integration_id)
                ->where('event_type', 'sign_in')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(normalized_payload, '$.correlation_id')) = ?", [$correlationId])
                ->latest('occurred_at')
                ->first();

            if ($matched) {
                return $this->toContext($matched, 'correlation_id');
            }
        }

        $referenceTime = data_get($normalized, 'activity_date_time')
            ? Carbon::parse(data_get($normalized, 'activity_date_time'))
            : $event->occurred_at;

        if (!$referenceTime) {
            return null;
        }

        $windowStart = $referenceTime->copy()->subHours(2);
        $windowEnd = $referenceTime->copy()->addHours(2);

        $candidates = ThreatEvent::query()
            ->where('integration_id', $event->integration_id)
            ->where('event_type', 'sign_in')
            ->when($event->principal, fn ($query) => $query->where('principal', $event->principal))
            ->when($event->ip_address, fn ($query) => $query->where('ip_address', $event->ip_address))
            ->whereBetween('occurred_at', [$windowStart, $windowEnd])
            ->get();

        $matched = $candidates
            ->sortBy(fn (ThreatEvent $candidate) => abs($candidate->occurred_at?->diffInSeconds($referenceTime) ?? PHP_INT_MAX))
            ->first();

        if ($matched) {
            return $this->toContext($matched, 'principal_ip_time');
        }

        $principalCandidates = ThreatEvent::query()
            ->where('integration_id', $event->integration_id)
            ->where('event_type', 'sign_in')
            ->when($event->principal, fn ($query) => $query->where('principal', $event->principal))
            ->whereBetween('occurred_at', [$referenceTime->copy()->subMinutes(30), $referenceTime->copy()->addMinutes(30)])
            ->get();

        $matched = $principalCandidates
            ->sortBy([
                fn (ThreatEvent $candidate) => $candidate->ip_address === $event->ip_address ? 0 : 1,
                fn (ThreatEvent $candidate) => abs($candidate->occurred_at?->diffInSeconds($referenceTime) ?? PHP_INT_MAX),
            ])
            ->first();

        if ($matched) {
            return $this->toContext($matched, 'principal_time');
        }

        return $this->lookupRemoteSignIn($event, $referenceTime);
    }

    private function toContext(ThreatEvent $event, string $matchStrategy): array
    {
        return [
            'event_id' => $event->id,
            'provider_event_key' => $event->provider_event_key,
            'match_strategy' => $matchStrategy,
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'status' => $event->status,
            'failure_reason' => $event->failure_reason,
            'application_name' => $event->application_name,
            'resource_name' => $event->resource_name,
            'ip_address' => $event->ip_address,
            'location_label' => $event->location_label,
            'country_code' => $event->country_code,
            'risk_level' => $event->risk_level,
            'risk_state' => $event->risk_state,
            'conditional_access_status' => data_get($event->normalized_payload, 'conditional_access_status'),
            'authentication_requirement' => data_get($event->normalized_payload, 'authentication_requirement'),
            'browser' => data_get($event->normalized_payload, 'browser'),
            'operating_system' => data_get($event->normalized_payload, 'operating_system'),
            'correlation_id' => data_get($event->normalized_payload, 'correlation_id'),
        ];
    }

    private function lookupRemoteSignIn(ThreatEvent $event, Carbon $referenceTime): ?array
    {
        $integration = $event->relationLoaded('integration')
            ? $event->integration
            : $event->integration()->first();

        if (!$integration instanceof Integration || !$integration->usesProvider(\App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH)) {
            return null;
        }

        try {
            $candidates = $this->graphClient->fetch(
                $integration,
                'auditLogs/signIns',
                $this->buildRemoteLookupQuery($event, $referenceTime)
            );
        } catch (Throwable) {
            return null;
        }

        $matchedPayload = collect($candidates)
            ->sortBy([
                fn (array $candidate) => data_get($candidate, 'correlationId') === data_get($event->normalized_payload, 'correlation_id') ? 0 : 1,
                fn (array $candidate) => data_get($candidate, 'ipAddress') === $event->ip_address ? 0 : 1,
                fn (array $candidate) => abs(
                    optional($this->candidateTime($candidate))->diffInSeconds($referenceTime) ?? PHP_INT_MAX
                ),
            ])
            ->first();

        if (!$matchedPayload) {
            return null;
        }

        $normalized = $this->signInNormalizer->normalize($matchedPayload);

        $signInEvent = DB::transaction(function () use ($integration, $normalized): ThreatEvent {
            return ThreatEvent::query()->updateOrCreate(
                [
                    'integration_id' => $integration->id,
                    'provider_event_key' => $normalized->providerEventKey,
                ],
                $normalized->toPersistenceArray($integration)
            );
        });

        return $this->toContext($signInEvent, 'graph_lookup');
    }

    private function buildRemoteLookupQuery(ThreatEvent $event, Carbon $referenceTime): array
    {
        $filters = [
            sprintf("createdDateTime ge %s", $referenceTime->copy()->subHours(12)->toIso8601String()),
            sprintf("createdDateTime le %s", $referenceTime->copy()->addHours(12)->toIso8601String()),
        ];

        if ($event->principal) {
            $filters[] = sprintf("userPrincipalName eq '%s'", str_replace("'", "''", $event->principal));
        }

        return [
            '$orderby' => 'createdDateTime asc',
            '$top' => 25,
            '$filter' => implode(' and ', $filters),
        ];
    }

    private function candidateTime(array $candidate): ?Carbon
    {
        $value = data_get($candidate, 'createdDateTime');

        return $value ? Carbon::parse($value) : null;
    }
}
