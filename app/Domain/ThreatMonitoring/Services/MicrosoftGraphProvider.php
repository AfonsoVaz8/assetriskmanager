<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Domain\ThreatMonitoring\Contracts\ThreatIntegrationProvider;
use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Domain\ThreatMonitoring\Normalizers\GraphRiskDetectionNormalizer;
use App\Domain\ThreatMonitoring\Normalizers\GraphSignInNormalizer;
use App\Jobs\ThreatMonitoring\AnalyzeThreatEvent;
use App\Models\Integration;
use App\Models\ThreatEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class MicrosoftGraphProvider implements ThreatIntegrationProvider
{
    private const RISK_DETECTION_SYNC_PAGE_SIZE = 10;
    private const MAX_RISK_DETECTION_RECORDS_PER_SYNC = 100;

    public function __construct(
        private readonly MicrosoftGraphClient $client,
        private readonly GraphSignInNormalizer $signInNormalizer,
        private readonly GraphRiskDetectionNormalizer $riskDetectionNormalizer,
    ) {
    }

    public function supports(Integration $integration): bool
    {
        return $integration->usesProvider(IntegrationProvider::MICROSOFT_GRAPH);
    }

    public function sync(Integration $integration): void
    {
        $syncState = $integration->sync_state ?? [];
        $signInsCursor = $syncState['sign_ins_last_seen_at'] ?? null;
        $riskDetectionsCursor = $syncState['risk_detections_last_seen_at'] ?? null;

        try {
            $signInsLastSeenAt = $this->syncStream(
                integration: $integration,
                resource: 'auditLogs/signIns',
                query: $this->buildSignInQuery($signInsCursor),
                normalizer: fn (array $payload) => $this->signInNormalizer->normalize($payload),
            );

            $signInsCursor = $signInsLastSeenAt?->toIso8601String() ?? $signInsCursor;

            $this->persistSyncState($integration, $signInsCursor, $riskDetectionsCursor);

            $riskDetectionsCursor = $this->syncRiskDetections($integration, $riskDetectionsCursor);

            $integration->forceFill([
                'sync_state' => $this->buildSyncState($signInsCursor, $riskDetectionsCursor),
                'last_synced_at' => now(),
                'last_error_at' => null,
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $integration->forceFill([
                'last_error_at' => now(),
                'last_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    private function syncRiskDetections(Integration $integration, ?string $cursor): ?string
    {
        $existingCursor = $cursor ? Carbon::parse($cursor) : null;
        $latestSeenAt = $existingCursor;
        $processed = 0;

        foreach ($this->client->stream($integration, 'identityProtection/riskDetections', $this->buildRiskDetectionQuery()) as $record) {
            $normalized = $this->riskDetectionNormalizer->normalize($record);
            $occurredAt = $normalized->occurredAt;

            if ($occurredAt && $existingCursor && $occurredAt->lt($existingCursor)) {
                break;
            }

            DB::transaction(function () use ($integration, $normalized): void {
                $event = ThreatEvent::query()->updateOrCreate(
                    [
                        'integration_id' => $integration->id,
                        'provider_event_key' => $normalized->providerEventKey,
                    ],
                    $normalized->toPersistenceArray($integration)
                );

                $event->forceFill([
                    'processed_at' => null,
                ])->save();

                AnalyzeThreatEvent::dispatch($event->id);
            });

            if ($occurredAt && ($latestSeenAt === null || $occurredAt->greaterThan($latestSeenAt))) {
                $latestSeenAt = $occurredAt;
                $cursor = $latestSeenAt->toIso8601String();

                $this->persistSyncState(
                    $integration,
                    data_get($integration->sync_state ?? [], 'sign_ins_last_seen_at'),
                    $cursor,
                );
            }

            $processed++;

            if ($processed >= self::MAX_RISK_DETECTION_RECORDS_PER_SYNC) {
                break;
            }
        }

        return $cursor ?? $existingCursor?->toIso8601String();
    }

    /**
     * @param callable(array): \App\Domain\ThreatMonitoring\DTO\NormalizedThreatEventData $normalizer
     */
    private function syncStream(Integration $integration, string $resource, array $query, callable $normalizer): ?Carbon
    {
        $latestSeenAt = null;

        foreach ($this->client->stream($integration, $resource, $query) as $record) {
            $normalized = $normalizer($record);

            DB::transaction(function () use ($integration, $normalized, &$latestSeenAt): void {
                $event = ThreatEvent::query()->updateOrCreate(
                    [
                        'integration_id' => $integration->id,
                        'provider_event_key' => $normalized->providerEventKey,
                    ],
                    $normalized->toPersistenceArray($integration)
                );

                $event->forceFill([
                    'processed_at' => null,
                ])->save();

                AnalyzeThreatEvent::dispatch($event->id);

                if ($event->occurred_at && ($latestSeenAt === null || $event->occurred_at->greaterThan($latestSeenAt))) {
                    $latestSeenAt = $event->occurred_at;
                }
            });
        }

        return $latestSeenAt;
    }

    private function buildSignInQuery(?string $cursor): array
    {
        $query = [
            '$orderby' => 'createdDateTime asc',
            '$top' => 100,
        ];

        if ($cursor) {
            $query['$filter'] = sprintf('createdDateTime ge %s', Carbon::parse($cursor)->toIso8601String());
        }

        return $query;
    }

    private function buildRiskDetectionQuery(): array
    {
        return [
            '$orderby' => 'detectedDateTime desc',
            '$top' => self::RISK_DETECTION_SYNC_PAGE_SIZE,
        ];
    }

    private function persistSyncState(Integration $integration, ?string $signInsCursor, ?string $riskDetectionsCursor): void
    {
        $integration->forceFill([
            'sync_state' => $this->buildSyncState($signInsCursor, $riskDetectionsCursor),
        ])->save();
    }

    private function buildSyncState(?string $signInsCursor, ?string $riskDetectionsCursor): array
    {
        return array_filter([
            'sign_ins_last_seen_at' => $signInsCursor,
            'risk_detections_last_seen_at' => $riskDetectionsCursor,
        ]);
    }
}
