<?php

namespace App\Jobs;

use App\Models\AttackSurfaceRun;
use App\Services\AttackSurfaceDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunAttackSurfaceDiscovery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $runId)
    {
    }

    public function handle(AttackSurfaceDiscoveryService $service): void
    {
        $run = AttackSurfaceRun::query()->with('scope')->find($this->runId);

        if (!$run) {
            return;
        }

        $service->executeRun($run);
    }
}
