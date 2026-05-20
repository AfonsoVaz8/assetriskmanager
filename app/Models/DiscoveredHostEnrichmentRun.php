<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscoveredHostEnrichmentRun extends Model
{
    protected $fillable = [
        'discovered_host_id',
        'asset_id',
        'provider',
        'status',
        'started_at',
        'finished_at',
        'synced_at',
        'open_ports',
        'vulnerabilities',
        'error',
        'raw_payload',
        'normalized_payload',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'synced_at' => 'datetime',
        'open_ports' => 'array',
        'vulnerabilities' => 'array',
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
    ];

    public function discoveredHost(): BelongsTo
    {
        return $this->belongsTo(DiscoveredHost::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(DiscoveredHostFinding::class, 'last_enrichment_run_id');
    }
}
