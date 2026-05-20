<?php

namespace App\Services;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Models\Asset;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Builder;

class ShodanIntegrationResolver
{
    public function resolveForAsset(Asset $asset): ?Integration
    {
        return $this->resolveGlobal();
    }

    public function eligibleAssetsQuery(?Integration $integration = null): Builder
    {
        $query = Asset::query()
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '');

        if (!$integration) {
            return $query;
        }

        return $query;
    }

    private function baseQuery(): Builder
    {
        return Integration::query()
            ->active()
            ->where('provider', IntegrationProvider::SHODAN->value);
    }

    public function resolveGlobal(): ?Integration
    {
        return $this->baseQuery()
            ->orderByDesc('id')
            ->first();
    }
}
