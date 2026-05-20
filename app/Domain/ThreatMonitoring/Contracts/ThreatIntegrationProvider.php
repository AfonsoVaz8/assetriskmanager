<?php

namespace App\Domain\ThreatMonitoring\Contracts;

use App\Models\Integration;

interface ThreatIntegrationProvider
{
    public function supports(Integration $integration): bool;

    public function sync(Integration $integration): void;
}
