<?php

namespace App\Http\Controllers;

use App\Domain\ThreatMonitoring\Services\RelatedSignInResolver;
use App\Models\Integration;
use App\Models\ThreatEvent;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ThreatEventController extends Controller
{
    public function index(Request $request): Application|Factory|View
    {
        $filters = [
            'q' => $request->input('q', ''),
            'severity' => $request->input('severity', ''),
            'event_type' => $request->input('event_type', ''),
            'integration_id' => $request->input('integration_id', ''),
        ];

        $events = ThreatEvent::query()
            ->with(['integration', 'incidents'])
            ->when($filters['q'], function ($query, $value) {
                $query->where(function ($subQuery) use ($value) {
                    $subQuery->whereRaw(lowerLike('principal'), [caseInsensitiveMatch($value)])
                        ->orWhereRaw(lowerLike('principal_display'), [caseInsensitiveMatch($value)])
                        ->orWhereRaw(lowerLike('ip_address'), [caseInsensitiveMatch($value)])
                        ->orWhereRaw(lowerLike('application_name'), [caseInsensitiveMatch($value)]);
                });
            })
            ->when($filters['severity'], fn ($query, $value) => $query->where('severity', $value))
            ->when($filters['event_type'], fn ($query, $value) => $query->where('event_type', $value))
            ->when($filters['integration_id'], fn ($query, $value) => $query->where('integration_id', $value))
            ->latest('occurred_at')
            ->paginate(20)
            ->withQueryString();

        return view('threat-events.index', [
            'events' => $events,
            'filters' => $filters,
            'integrations' => Integration::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(ThreatEvent $threatEvent, RelatedSignInResolver $relatedSignInResolver): Application|Factory|View
    {
        $threatEvent->load(['integration', 'incidents']);

        return view('threat-events.show', [
            'event' => $threatEvent,
            'relatedSignIn' => $relatedSignInResolver->forRiskDetection($threatEvent),
        ]);
    }
}
