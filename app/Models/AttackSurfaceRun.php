<?php

namespace App\Models;

use App\Enums\AttackSurfaceRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttackSurfaceRun extends Model
{
    protected $fillable = [
        'attack_surface_scope_id',
        'status',
        'strategy',
        'target_count',
        'active_host_count',
        'created_asset_count',
        'error_count',
        'started_at',
        'finished_at',
        'error',
        'config_snapshot',
    ];

    protected $casts = [
        'status' => AttackSurfaceRunStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'config_snapshot' => 'array',
    ];

    public function scope(): BelongsTo
    {
        return $this->belongsTo(AttackSurfaceScope::class, 'attack_surface_scope_id');
    }

    public function discoveredHosts(): HasMany
    {
        return $this->hasMany(DiscoveredHost::class);
    }
}
