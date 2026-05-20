<?php

namespace App\Console\Commands;

use App\Jobs\ThreatMonitoring\SyncThreatIntegration;
use App\Models\Integration;
use Illuminate\Console\Command;

class SyncThreatIntegrations extends Command
{
    protected $signature = 'threat-integrations:sync {--integration=}';

    protected $description = 'Dispatch sync jobs for active threat integrations.';

    public function handle(): int
    {
        $query = Integration::query()->active();

        if ($integrationId = $this->option('integration')) {
            $query->whereKey($integrationId);
        }

        $integrations = $query->get();

        foreach ($integrations as $integration) {
            SyncThreatIntegration::dispatch($integration->id);
        }

        $this->info(sprintf('Dispatched %d threat integration sync job(s).', $integrations->count()));

        return self::SUCCESS;
    }
}
