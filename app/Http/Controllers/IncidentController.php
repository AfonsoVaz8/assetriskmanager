<?php

namespace App\Http\Controllers;

use App\Domain\IncidentManagement\Services\IncidentService;
use App\Http\Requests\UpdateIncidentStatusRequest;
use App\Models\Incident;
use App\Models\Integration;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Incident::class, 'incident');
    }

    public function index(Request $request): Application|Factory|View
    {
        $filters = [
            'q' => $request->input('q', ''),
            'severity' => $request->input('severity', ''),
            'status' => $request->input('status', ''),
            'integration_id' => $request->input('integration_id', ''),
        ];

        $incidents = Incident::query()
            ->with(['integration', 'assignee'])
            ->when($filters['q'], function ($query, $value) {
                $query->where(function ($subQuery) use ($value) {
                    $subQuery->whereRaw(lowerLike('title'), [caseInsensitiveMatch($value)])
                        ->orWhereRaw(lowerLike('severity'), [caseInsensitiveMatch($value)])
                        ->orWhereRaw(lowerLike('affected_principal'), [caseInsensitiveMatch($value)])
                        ->orWhereRaw(lowerLike('affected_principal_display'), [caseInsensitiveMatch($value)]);
                });
            })
            ->when($filters['severity'], fn ($query, $value) => $query->where('severity', $value))
            ->when($filters['status'], fn ($query, $value) => $query->where('status', $value))
            ->when($filters['integration_id'], fn ($query, $value) => $query->where('integration_id', $value))
            ->select([
                'id',
                'integration_id',
                'title',
                'status',
                'severity',
                'confidence',
                'event_count',
                'affected_principal',
                'affected_principal_display',
                'assigned_to',
                'last_seen_at',
            ])
            ->latest('last_seen_at')
            ->paginate(20)
            ->withQueryString();

        return view('incidents.index', [
            'incidents' => $incidents,
            'filters' => $filters,
            'integrations' => Integration::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Incident $incident): Application|Factory|View
    {
        $incident->load(['integration', 'assignee', 'resolver', 'dismisser']);
        $events = $incident->events()
            ->select([
                'threat_events.id',
                'threat_events.integration_id',
                'threat_events.occurred_at',
                'threat_events.event_type',
                'threat_events.severity',
                'threat_events.confidence',
                'threat_events.principal',
                'threat_events.principal_display',
                'threat_events.ip_address',
                'threat_events.location_label',
                'threat_events.country_code',
                'threat_events.score',
            ])
            ->latest('threat_events.occurred_at')
            ->paginate(25, ['*'], 'events_page');

        return view('incidents.show', [
            'incident' => $incident,
            'events' => $events,
        ]);
    }

    public function updateStatus(UpdateIncidentStatusRequest $request, Incident $incident, IncidentService $incidentService): RedirectResponse
    {
        $this->authorize('update', $incident);

        $incident = match ($request->input('status')) {
            'in_progress' => $incidentService->markInProgress($incident, $request->user()),
            'resolved' => $incidentService->resolve($incident, $request->user(), $request->input('resolution_note')),
            'dismissed' => $incidentService->dismiss($incident, $request->user(), $request->input('resolution_note')),
        };

        return redirect()->route('incidents.show', $incident)->with('status', __('Incident updated'));
    }

    public function reopen(Request $request, Incident $incident, IncidentService $incidentService): RedirectResponse
    {
        $this->authorize('update', $incident);

        $incident = $incidentService->reopen($incident);

        return redirect()->route('incidents.show', $incident)->with('status', __('Incident reopened'));
    }
}
