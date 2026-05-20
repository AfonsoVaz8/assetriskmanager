<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveredHostFinding extends Model
{
    protected $fillable = [
        'discovered_host_id',
        'asset_id',
        'last_enrichment_run_id',
        'kind',
        'source',
        'source_key',
        'title',
        'description',
        'severity',
        'context',
        'active',
        'first_detected_at',
        'last_detected_at',
    ];

    protected $casts = [
        'context' => 'array',
        'active' => 'boolean',
        'first_detected_at' => 'datetime',
        'last_detected_at' => 'datetime',
    ];

    public function discoveredHost(): BelongsTo
    {
        return $this->belongsTo(DiscoveredHost::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function lastEnrichmentRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveredHostEnrichmentRun::class, 'last_enrichment_run_id');
    }
}
