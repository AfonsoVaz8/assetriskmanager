<?php

namespace App\Console\Commands;

use App\Jobs\ThreatMonitoring\AnalyzeThreatEvent;
use App\Models\ThreatEvent;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ReprocessThreatEvents extends Command
{
    protected $signature = 'threat-events:reprocess {--integration_id=}';

    protected $description = 'Re-dispatch analysis jobs for existing threat events.';

    public function handle(): int
    {
        $query = ThreatEvent::query();

        if ($integrationId = $this->option('integration_id')) {
            $query->where('integration_id', $integrationId);
        }

        $count = 0;

        $query->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($events) use (&$count): void {
                foreach ($events as $event) {
                    AnalyzeThreatEvent::dispatch($event->id);
                    $count++;
                }
            });

        $this->info(sprintf('Dispatched %d threat event reprocessing job(s).', $count));

        return CommandAlias::SUCCESS;
    }
}
