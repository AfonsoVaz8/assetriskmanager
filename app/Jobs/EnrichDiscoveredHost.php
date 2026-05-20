<?php

namespace App\Jobs;

use App\Enums\DiscoveredHostStatus;
use App\Models\DiscoveredHost;
use App\Services\DiscoveredHostEnrichmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichDiscoveredHost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $discoveredHostId)
    {
    }

    public function handle(DiscoveredHostEnrichmentService $service): void
    {
        $host = DiscoveredHost::query()->with('asset')->find($this->discoveredHostId);

        if (!$host || $host->status !== DiscoveredHostStatus::ACTIVE) {
            return;
        }

        $service->enrich($host);
    }
}
