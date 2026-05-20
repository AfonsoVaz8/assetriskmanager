<?php

namespace App\Jobs\ThreatMonitoring;

use App\Domain\ThreatMonitoring\Services\ThreatProviderManager;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncThreatIntegration implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $integrationId)
    {
    }

    public function handle(ThreatProviderManager $providerManager): void
    {
        $integration = Integration::query()->active()->find($this->integrationId);

        if (!$integration) {
            return;
        }

        $providerManager->sync($integration);
    }
}
