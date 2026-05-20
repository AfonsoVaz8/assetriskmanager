<?php

namespace App\Jobs;

use App\Models\Asset;
use App\Models\AssetShodanReport;
use App\Models\Integration;
use App\Services\IpIntelligenceNormalizer;
use App\Services\ShodanClient;
use App\Services\ShodanIntegrationResolver;
use App\Services\ShodanThreatSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncAssetFromShodan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $assetId)
    {
    }

    public function assetId(): int
    {
        return $this->assetId;
    }

    public function handle(
        ShodanClient $client,
        ShodanIntegrationResolver $resolver,
        ShodanThreatSyncService $threatSyncService,
        IpIntelligenceNormalizer $normalizer
    ): void
    {
        $asset = Asset::query()->find($this->assetId);

        if (!$asset || blank($asset->ip_address)) {
            return;
        }

        $integration = $resolver->resolveForAsset($asset);

        if (!$integration || !$client->isEnabled($integration)) {
            AssetShodanReport::create([
                'asset_id' => $asset->id,
                'ip_address' => $asset->ip_address,
                'synced_at' => now(),
                'status' => 'error',
                'error' => 'No active Shodan integration is configured for this asset.',
            ]);

            return;
        }

        try {
            $payload = $client->fetchHost($integration, $asset->ip_address);
            $syncedAt = now();
            $normalizedPayload = $normalizer->normalize(
                ip: $asset->ip_address,
                raw: $payload,
                source: (string) data_get($payload, '_source_label', 'Shodan'),
                collectedAt: $syncedAt->toIso8601String(),
            );

            AssetShodanReport::create([
                'asset_id' => $asset->id,
                'ip_address' => $asset->ip_address,
                'open_ports' => ShodanClient::extractPorts($payload),
                'vulnerabilities' => ShodanClient::extractVulnerabilities($payload),
                'last_seen_at' => $this->parseTimestamp($payload['last_update'] ?? $payload['last_seen'] ?? null),
                'synced_at' => $syncedAt,
                'status' => 'synced',
                'raw_payload' => $payload,
                'normalized_payload' => $normalizedPayload,
                'error' => null,
            ]);

            $threatSyncService->sync($asset, $payload);

            $this->touchIntegrationState($integration, 'synced', null);
        } catch (Throwable $exception) {
            Log::warning('Failed syncing asset from Shodan', [
                'asset_id' => $this->assetId,
                'integration_id' => $integration->id,
                'message' => $exception->getMessage(),
            ]);

            AssetShodanReport::create([
                'asset_id' => $asset->id,
                'ip_address' => $asset->ip_address,
                'synced_at' => now(),
                'status' => 'error',
                'error' => $exception->getMessage(),
            ]);

            $this->touchIntegrationState($integration, 'error', $exception->getMessage());
        }
    }

    protected function parseTimestamp(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function touchIntegrationState(Integration $integration, string $status, ?string $error): void
    {
        $existingState = $integration->sync_state ?? [];

        $integration->forceFill([
            'last_synced_at' => now(),
            'last_error' => $error,
            'last_error_at' => $error ? now() : null,
            'sync_state' => [
                'last_dispatched_at' => data_get($existingState, 'last_dispatched_at'),
                'last_job_dispatch_count' => data_get($existingState, 'last_job_dispatch_count', 0),
                'last_report_synced_at' => now()->toIso8601String(),
                'last_report_status' => $status,
                'last_report_error' => $error,
            ],
        ])->save();
    }
}
