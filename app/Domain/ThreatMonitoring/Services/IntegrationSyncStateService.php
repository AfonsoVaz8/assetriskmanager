<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Models\Integration;
use App\Models\ThreatEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class IntegrationSyncStateService
{
    public function markThreatSyncQueued(Integration $integration): void
    {
        $state = $integration->sync_state ?? [];

        $state['monitoring_status'] = 'queued';
        $state['last_requested_at'] = now()->toIso8601String();

        $integration->forceFill([
            'sync_state' => $state,
            'last_error' => null,
            'last_error_at' => null,
        ])->save();
    }

    public function markThreatSyncStarted(Integration $integration): void
    {
        $state = $integration->sync_state ?? [];

        $state['monitoring_status'] = 'syncing';
        $state['last_sync_started_at'] = now()->toIso8601String();
        $state['last_sync_finished_at'] = null;
        $state['last_processing_completed_at'] = null;
        $state['last_sync_sign_in_count'] = 0;
        $state['last_sync_risk_detection_count'] = 0;
        $state['last_sync_collected_count'] = 0;
        $state['last_sync_pending_analysis_count'] = 0;

        $integration->forceFill([
            'sync_state' => $state,
            'last_error' => null,
            'last_error_at' => null,
        ])->save();
    }

    public function markThreatSyncFetched(
        Integration $integration,
        int $signInCount,
        int $riskDetectionCount,
        ?string $signInsCursor,
        ?string $riskDetectionsCursor,
    ): void {
        $state = $integration->sync_state ?? [];
        $pendingCount = $this->pendingAnalysisCount(
            $integration->id,
            data_get($state, 'last_sync_started_at'),
        );

        $state['monitoring_status'] = $pendingCount > 0 ? 'processing' : 'completed';
        $state['last_sync_finished_at'] = now()->toIso8601String();
        $state['last_sync_sign_in_count'] = $signInCount;
        $state['last_sync_risk_detection_count'] = $riskDetectionCount;
        $state['last_sync_collected_count'] = $signInCount + $riskDetectionCount;
        $state['last_sync_pending_analysis_count'] = $pendingCount;
        $state['sign_ins_last_seen_at'] = $signInsCursor;
        $state['risk_detections_last_seen_at'] = $riskDetectionsCursor;

        $integration->forceFill([
            'sync_state' => $state,
            'last_synced_at' => now(),
            'last_error' => null,
            'last_error_at' => null,
        ])->save();
    }

    public function refreshThreatProcessingState(int $integrationId): void
    {
        $integration = Integration::query()->find($integrationId);

        if (!$integration) {
            return;
        }

        $state = $integration->sync_state ?? [];
        $pendingCount = $this->pendingAnalysisCount(
            $integrationId,
            data_get($state, 'last_sync_started_at'),
        );

        $state['last_sync_pending_analysis_count'] = $pendingCount;

        if (in_array(data_get($state, 'monitoring_status'), ['queued', 'syncing', 'processing'], true)) {
            if ($pendingCount > 0) {
                $state['monitoring_status'] = 'processing';
            } else {
                $state['monitoring_status'] = 'completed';
                $state['last_processing_completed_at'] = now()->toIso8601String();
            }
        }

        $integration->forceFill([
            'sync_state' => $state,
        ])->save();
    }

    public function markThreatSyncError(Integration $integration, string $message): void
    {
        $state = $integration->sync_state ?? [];
        $state['monitoring_status'] = 'error';
        $state['last_sync_finished_at'] = now()->toIso8601String();

        $integration->forceFill([
            'sync_state' => $state,
            'last_error' => $message,
            'last_error_at' => now(),
        ])->save();
    }

    private function pendingAnalysisCount(int $integrationId, ?string $startedAt = null): int
    {
        return $this->pendingAnalysisQuery($integrationId, $startedAt)->count();
    }

    private function pendingAnalysisQuery(int $integrationId, ?string $startedAt = null): Builder
    {
        $query = ThreatEvent::query()
            ->where('integration_id', $integrationId)
            ->pendingAnalysis();

        if ($startedAt) {
            $startedAtValue = CarbonImmutable::parse($startedAt);

            $query->where(function (Builder $builder) use ($startedAtValue): void {
                $builder->where('updated_at', '>=', $startedAtValue)
                    ->orWhere('created_at', '>=', $startedAtValue);
            });
        }

        return $query;
    }
}
