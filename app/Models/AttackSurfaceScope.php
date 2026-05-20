<?php

namespace App\Models;

use App\Enums\AttackSurfaceScopeStatus;
use App\Enums\AttackSurfaceScopeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttackSurfaceScope extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'submitted_by_user_id',
        'approved_by_user_id',
        'justification',
        'scope_definition',
        'settings',
        'last_run_at',
    ];

    protected $casts = [
        'type' => AttackSurfaceScopeType::class,
        'status' => AttackSurfaceScopeStatus::class,
        'scope_definition' => 'array',
        'settings' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AttackSurfaceRun::class);
    }

    public function discoveredHosts(): HasMany
    {
        return $this->hasMany(DiscoveredHost::class);
    }
}
