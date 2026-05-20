<?php

namespace App\Models;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Integration extends Model
{
    protected $fillable = [
        'tenant_type',
        'tenant_id',
        'name',
        'provider',
        'status',
        'credentials',
        'settings',
        'sync_state',
        'last_synced_at',
        'last_error_at',
        'last_error',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'sync_state' => 'array',
        'last_synced_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    public function tenant(): MorphTo
    {
        return $this->morphTo();
    }

    public function threatEvents(): HasMany
    {
        return $this->hasMany(ThreatEvent::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function usesProvider(IntegrationProvider $provider): bool
    {
        return $this->provider === $provider->value;
    }

    public function safeCredentials(): ?array
    {
        try {
            return $this->credentials;
        } catch (DecryptException) {
            return null;
        }
    }

    public function hasReadableCredentials(): bool
    {
        return $this->safeCredentials() !== null;
    }
}
