<?php

namespace App\Models;

use App\Enums\DiscoveredHostStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiscoveredHost extends Model
{
    protected $fillable = [
        'attack_surface_scope_id',
        'attack_surface_run_id',
        'asset_id',
        'ip_address',
        'fqdn',
        'status',
        'origin',
        'discovery_method',
        'open_ports',
        'was_auto_created',
        'first_seen_at',
        'last_seen_at',
        'error',
        'raw_payload',
        'normalized_payload',
    ];

    protected $casts = [
        'status' => DiscoveredHostStatus::class,
        'open_ports' => 'array',
        'was_auto_created' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
    ];

    public function scope(): BelongsTo
    {
        return $this->belongsTo(AttackSurfaceScope::class, 'attack_surface_scope_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AttackSurfaceRun::class, 'attack_surface_run_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function enrichmentRuns(): HasMany
    {
        return $this->hasMany(DiscoveredHostEnrichmentRun::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(DiscoveredHostFinding::class);
    }

    public function latestEnrichmentRun(): HasOne
    {
        return $this->hasOne(DiscoveredHostEnrichmentRun::class)->latestOfMany();
    }
}
