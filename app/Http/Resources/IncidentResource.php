<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value ?? $this->status,
            'severity' => $this->severity,
            'confidence' => $this->confidence,
            'event_count' => $this->event_count,
            'affected_principal' => $this->affected_principal,
            'affected_principal_display' => $this->affected_principal_display,
            'event_type' => $this->event_type,
            'first_seen_at' => optional($this->first_seen_at)->toIso8601String(),
            'last_seen_at' => optional($this->last_seen_at)->toIso8601String(),
            'resolution_note' => $this->resolution_note,
            'resolved_at' => optional($this->resolved_at)->toIso8601String(),
            'dismissed_at' => optional($this->dismissed_at)->toIso8601String(),
            'integration' => $this->whenLoaded('integration', fn () => [
                'id' => $this->integration?->id,
                'name' => $this->integration?->name,
                'provider' => $this->integration?->provider,
            ]),
            'assignee' => $this->whenLoaded('assignee', fn () => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
                'email' => $this->assignee?->email,
            ]),
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($event) => [
                'id' => $event->id,
                'occurred_at' => optional($event->occurred_at)->toIso8601String(),
                'event_type' => $event->event_type,
                'severity' => $event->severity,
                'confidence' => $event->confidence,
                'principal' => $event->principal,
                'principal_display' => $event->principal_display,
                'ip_address' => $event->ip_address,
                'location_label' => $event->location_label,
                'country_code' => $event->country_code,
                'score' => $event->score,
            ])),
        ];
    }
}
