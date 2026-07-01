<?php

namespace App\Console\Commands;

use App\Domain\ThreatMonitoring\Services\ThreatDataRetentionService;
use Illuminate\Console\Command;

class PruneThreatRetention extends Command
{
    protected $signature = 'threat-integrations:prune-retention {--integration=}';

    protected $description = 'Prune stored threat-monitoring data according to each integration retention policy.';

    public function handle(ThreatDataRetentionService $retentionService): int
    {
        $integrationId = $this->option('integration');

        if ($integrationId) {
            $integration = \App\Models\Integration::query()->find($integrationId);

            if (!$integration) {
                $this->error('Integration not found.');

                return self::FAILURE;
            }

            if (!$retentionService->retentionEnabled($integration)) {
                $this->warn('Retention is not enabled for the selected integration.');

                return self::SUCCESS;
            }

            $result = $retentionService->pruneIntegration($integration, now());
            $this->info(sprintf(
                'Pruned integration %d: %d event(s) deleted, %d alert(s) deleted, %d alert(s) updated.',
                $integration->id,
                $result['deleted_events'],
                $result['deleted_alerts'],
                $result['updated_alerts'],
            ));

            return self::SUCCESS;
        }

        $summary = $retentionService->pruneEligibleIntegrations(now());

        $this->info(sprintf(
            'Checked %d integration(s); pruned %d. Deleted %d event(s), deleted %d alert(s), updated %d alert(s).',
            $summary['integrations_checked'],
            $summary['integrations_pruned'],
            $summary['deleted_events'],
            $summary['deleted_alerts'],
            $summary['updated_alerts'],
        ));

        return self::SUCCESS;
    }
}
