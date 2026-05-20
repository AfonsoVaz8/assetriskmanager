<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Domain\ThreatMonitoring\Contracts\ThreatIntegrationProvider;
use App\Models\Integration;
use RuntimeException;

class ThreatProviderManager
{
    /**
     * @param iterable<int, ThreatIntegrationProvider> $providers
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    public function sync(Integration $integration): void
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($integration)) {
                $provider->sync($integration);

                return;
            }
        }

        throw new RuntimeException('No threat integration provider registered for ' . $integration->provider . '.');
    }
}
