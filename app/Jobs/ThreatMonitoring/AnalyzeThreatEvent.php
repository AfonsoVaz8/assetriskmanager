<?php

namespace App\Jobs\ThreatMonitoring;

use App\Domain\IncidentManagement\Services\IncidentService;
use App\Domain\ThreatMonitoring\Services\IntegrationSyncStateService;
use App\Domain\ThreatMonitoring\Services\ThreatAnalysisEngine;
use App\Models\ThreatEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeThreatEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $threatEventId)
    {
    }

    public function handle(
        ThreatAnalysisEngine $analysisEngine,
        IncidentService $incidentService,
        IntegrationSyncStateService $integrationSyncStateService,
    ): void {
        $event = ThreatEvent::query()->find($this->threatEventId);

        if (!$event) {
            return;
        }

        $assessment = $analysisEngine->assess($event);

        $event->forceFill([
            'severity' => $assessment->severity->value,
            'confidence' => $assessment->confidence->value,
            'score' => $assessment->score,
            'findings' => $assessment->findings,
            'processed_at' => now(),
        ])->save();

        $incidentService->ingestEvent($event);
        $integrationSyncStateService->refreshThreatProcessingState($event->integration_id);
    }
}
