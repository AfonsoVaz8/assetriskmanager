<?php

namespace App\Console\Commands;

use App\Jobs\SyncAssetFromShodan;
use App\Models\Integration;
use App\Models\Asset;
use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Services\ShodanIntegrationResolver;
use Illuminate\Console\Command;

class SyncAssetsFromShodan extends Command
{
    protected $signature = 'shodan:sync-assets {asset_id? : Only sync a specific asset} {--chunk=50 : Chunk size for batching job dispatches}';

    protected $description = 'Dispatch jobs that pull the latest Shodan intelligence for assets with IP addresses.';

    public function handle(ShodanIntegrationResolver $resolver): int
    {
        $hasActiveIntegration = Integration::query()
            ->active()
            ->where('provider', IntegrationProvider::SHODAN->value)
            ->exists();

        if (!$hasActiveIntegration) {
            $this->warn('No active Shodan integration configured. Skipping sync.');

            return self::SUCCESS;
        }

        $chunk = max(1, (int)$this->option('chunk'));
        $assetId = $this->argument('asset_id');

        $query = $resolver->eligibleAssetsQuery();

        if ($assetId) {
            $query->whereKey($assetId);

            if (!$query->exists()) {
                $this->error("Asset {$assetId} not found or missing IP address.");

                return self::FAILURE;
            }
        }

        $dispatched = 0;

        $query->chunkById($chunk, function ($assets) use (&$dispatched) {
            foreach ($assets as $asset) {
                SyncAssetFromShodan::dispatch($asset->id);
                $dispatched++;
            }
        });

        $this->info("Dispatched {$dispatched} Shodan sync job(s).");

        return self::SUCCESS;
    }
}
