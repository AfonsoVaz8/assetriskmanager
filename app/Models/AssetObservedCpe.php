<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetObservedCpe extends Model
{
    protected $fillable = [
        'asset_id',
        'discovered_host_id',
        'discovered_host_enrichment_run_id',
        'cpe',
        'part',
        'vendor',
        'product',
        'version',
        'source',
        'confidence',
        'score',
        'is_primary',
        'context',
        'first_observed_at',
        'last_observed_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'score' => 'integer',
        'context' => 'array',
        'first_observed_at' => 'datetime',
        'last_observed_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function discoveredHost(): BelongsTo
    {
        return $this->belongsTo(DiscoveredHost::class);
    }

    public function enrichmentRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveredHostEnrichmentRun::class, 'discovered_host_enrichment_run_id');
    }
}
