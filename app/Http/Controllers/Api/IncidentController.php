<?php

namespace App\Http\Controllers\Api;

use App\Domain\IncidentManagement\Services\IncidentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateIncidentStatusRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncidentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Incident::class);

        $query = Incident::query()
            ->with(['integration', 'assignee'])
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
                'event_type',
                'assigned_to',
                'first_seen_at',
                'last_seen_at',
                'resolution_note',
                'resolved_at',
                'dismissed_at',
            ]);

        if ($value = $request->input('status')) {
            $query->where('status', $value);
        }

        if ($value = $request->input('severity')) {
            $query->where('severity', $value);
        }

        if ($value = $request->input('integration_id')) {
            $query->where('integration_id', $value);
        }

        if ($value = $request->input('q')) {
            $query->where(function ($subQuery) use ($value) {
                $subQuery->whereRaw(lowerLike('title'), [caseInsensitiveMatch($value)])
                    ->orWhereRaw(lowerLike('affected_principal'), [caseInsensitiveMatch($value)])
                    ->orWhereRaw(lowerLike('affected_principal_display'), [caseInsensitiveMatch($value)]);
            });
        }

        return IncidentResource::collection(
            $query->latest('last_seen_at')->paginate(25)->withQueryString()
        );
    }

    public function show(Incident $incident): IncidentResource
    {
        $this->authorize('view', $incident);

        return IncidentResource::make(
            $incident->load([
                'integration',
                'assignee',
                'events' => fn ($query) => $query->latest('occurred_at')->limit(100),
            ])
        );
    }

    public function updateStatus(
        UpdateIncidentStatusRequest $request,
        Incident $incident,
        IncidentService $incidentService
    ): IncidentResource {
        $this->authorize('update', $incident);

        $incident = match ($request->input('status')) {
            'in_progress' => $incidentService->markInProgress($incident, $request->user()),
            'resolved' => $incidentService->resolve($incident, $request->user(), $request->input('resolution_note')),
            'dismissed' => $incidentService->dismiss($incident, $request->user(), $request->input('resolution_note')),
        };

        return IncidentResource::make($incident->load(['integration', 'assignee']));
    }

    public function reopen(Request $request, Incident $incident, IncidentService $incidentService): IncidentResource
    {
        $this->authorize('update', $incident);

        return IncidentResource::make(
            $incidentService->reopen($incident)->load(['integration', 'assignee'])
        );
    }
}
