<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ThreatEvent extends Model
{
    protected $fillable = [
        'integration_id',
        'provider',
        'provider_event_key',
        'event_type',
        'source_stream',
        'occurred_at',
        'principal',
        'principal_display',
        'application_name',
        'resource_name',
        'ip_address',
        'location_label',
        'country_code',
        'status',
        'failure_reason',
        'risk_level',
        'risk_state',
        'risk_detail',
        'severity',
        'confidence',
        'score',
        'incident_fingerprint',
        'findings',
        'normalized_payload',
        'raw_payload',
        'processed_at',
        'notified_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
        'notified_at' => 'datetime',
        'score' => 'integer',
        'findings' => 'array',
        'normalized_payload' => 'array',
        'raw_payload' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function incidents(): BelongsToMany
    {
        return $this->belongsToMany(Incident::class, 'incident_events')
            ->withPivot('linked_at')
            ->withTimestamps();
    }

    public function scopePendingAnalysis(Builder $query): Builder
    {
        return $query->whereNull('processed_at');
    }
}
