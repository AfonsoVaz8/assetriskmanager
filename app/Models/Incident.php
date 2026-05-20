<?php

namespace App\Models;

use App\Domain\IncidentManagement\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Incident extends Model
{
    protected $fillable = [
        'tenant_type',
        'tenant_id',
        'integration_id',
        'fingerprint',
        'title',
        'status',
        'severity',
        'confidence',
        'event_count',
        'affected_principal',
        'affected_principal_display',
        'event_type',
        'first_seen_at',
        'last_seen_at',
        'assigned_to',
        'resolved_at',
        'resolved_by',
        'resolution_note',
        'dismissed_at',
        'dismissed_by',
        'context',
    ];

    protected $casts = [
        'status' => IncidentStatus::class,
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'context' => 'array',
    ];

    public function tenant(): MorphTo
    {
        return $this->morphTo();
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(ThreatEvent::class, 'incident_events')
            ->withPivot('linked_at')
            ->withTimestamps();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function dismisser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }
}
