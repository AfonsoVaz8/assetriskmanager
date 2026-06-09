<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __("Alert Details") }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Title") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $incident->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Integration") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $incident->integration?->name ?? __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Severity") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ strtoupper($incident->severity) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Status") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \Illuminate\Support\Str::of($incident->status->value ?? $incident->status)->replace('_', ' ')->title() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("First Seen") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ optional($incident->first_seen_at)->toDateTimeString() ?? __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Last Seen") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ optional($incident->last_seen_at)->toDateTimeString() ?? __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Affected User") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $incident->affected_principal_display ?: $incident->affected_principal ?: __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Related Events") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $incident->event_count }}</dd>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <form method="POST" action="{{ route('incidents.update-status', $incident) }}" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label for="status" class="block mb-2 text-sm font-medium text-gray-900">{{ __("Change Status") }}</label>
                                <select id="status" name="status"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    <option value="in_progress">{{ __("In Progress") }}</option>
                                    <option value="resolved">{{ __("Resolved") }}</option>
                                    <option value="dismissed">{{ __("Dismissed") }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="resolution_note" class="block mb-2 text-sm font-medium text-gray-900">{{ __("Resolution Note") }}</label>
                                <textarea id="resolution_note" name="resolution_note" rows="3"
                                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"></textarea>
                            </div>
                            <button type="submit"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __("Apply") }}
                            </button>
                        </form>

                        @if(($incident->status->value ?? $incident->status) !== 'open')
                            <div class="space-y-3">
                                <dt class="text-sm font-medium text-gray-500">{{ __("Lifecycle") }}</dt>
                                <dd class="text-sm text-gray-900">
                                    @if($incident->resolved_at)
                                        {{ __("Resolved at") }}: {{ $incident->resolved_at->toDateTimeString() }}
                                    @elseif($incident->dismissed_at)
                                        {{ __("Dismissed at") }}: {{ $incident->dismissed_at->toDateTimeString() }}
                                    @else
                                        {{ __("In progress") }}
                                    @endif
                                </dd>
                                @if($incident->resolution_note)
                                    <dd class="text-sm text-gray-700">{{ $incident->resolution_note }}</dd>
                                @endif
                                <form method="POST" action="{{ route('incidents.reopen', $incident) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-white bg-slate-700 hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __("Reopen Alert") }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                    @php
                        $context = $incident->context ?? [];
                        $latestFindings = data_get($context, 'latest_findings', []);
                        $relatedSignIn = data_get($context, 'related_sign_in');
                    @endphp
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __("Context") }}</h3>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __("Initial Event ID") }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ data_get($context, 'initial_event_id', __('Unknown')) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __("Latest Event ID") }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ data_get($context, 'last_event_id', __('Unknown')) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __("Latest IP Address") }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ data_get($context, 'last_ip_address', data_get($context, 'ip_address', __('Unknown'))) ?: __('Unknown') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __("Latest Location") }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ data_get($context, 'last_location_label', data_get($context, 'location_label', __('Unknown'))) ?: __('Unknown') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __("Latest Application") }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ data_get($context, 'last_application_name', data_get($context, 'application_name', __('Unknown'))) ?: __('Unknown') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __("Notification State") }}</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    @if(data_get($context, 'notified_at'))
                                        {{ __("Notified at") }} {{ data_get($context, 'notified_at') }}
                                    @else
                                        {{ __("Not notified") }}
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        @if(!empty($relatedSignIn))
                            <div class="mt-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-3">{{ __("Related Sign-In") }}</h4>
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("Status") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ strtoupper(data_get($relatedSignIn, 'status', __('Unknown'))) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("Matched By") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ \Illuminate\Support\Str::of(data_get($relatedSignIn, 'match_strategy', __('Unknown')))->replace('_', ' ')->title() }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("Occurred At") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ data_get($relatedSignIn, 'occurred_at', __('Unknown')) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("Application") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ data_get($relatedSignIn, 'application_name', __('Unknown')) ?: __('Unknown') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("IP Address") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ data_get($relatedSignIn, 'ip_address', __('Unknown')) ?: __('Unknown') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("Location") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ data_get($relatedSignIn, 'location_label', __('Unknown')) ?: __('Unknown') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("Conditional Access") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ data_get($relatedSignIn, 'conditional_access_status', __('Unknown')) ?: __('Unknown') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __("Authentication Requirement") }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ data_get($relatedSignIn, 'authentication_requirement', __('Unknown')) ?: __('Unknown') }}</dd>
                                    </div>
                                    @if(data_get($relatedSignIn, 'failure_reason'))
                                        <div class="md:col-span-2">
                                            <dt class="text-sm font-medium text-gray-500">{{ __("Failure Reason") }}</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ data_get($relatedSignIn, 'failure_reason') }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        @endif

                        @if(!empty($latestFindings))
                            <div class="mt-6">
                                <h4 class="text-md font-semibold text-gray-900 mb-3">{{ __("Latest Findings Summary") }}</h4>
                                <div class="space-y-3">
                                    @foreach($latestFindings as $finding)
                                        <div class="rounded-lg border border-gray-200 p-4">
                                            <div class="font-medium text-gray-900">
                                                {{ \Illuminate\Support\Str::of($finding['name'] ?? __('Unnamed finding'))->replace('_', ' ')->title() }}
                                            </div>
                                            <div class="text-sm text-gray-600 mt-1">{{ $finding['description'] ?? '' }}</div>
                                            @if(!empty($finding['details']) && is_array($finding['details']))
                                                <dl class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                                                    @foreach($finding['details'] as $key => $value)
                                                        @continue(blank($value))
                                                        <div>
                                                            <dt class="font-medium text-gray-500">
                                                                {{ \Illuminate\Support\Str::of($key)->replace('_', ' ')->title() }}
                                                            </dt>
                                                            <dd class="text-gray-900 break-all">
                                                                @if(is_array($value))
                                                                    {{ implode(', ', $value) }}
                                                                @else
                                                                    {{ $value }}
                                                                @endif
                                                            </dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __("Related Events") }}</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">{{ __("Occurred At") }}</th>
                                    <th class="px-4 py-3">{{ __("Type") }}</th>
                                    <th class="px-4 py-3">{{ __("Principal") }}</th>
                                    <th class="px-4 py-3">{{ __("IP") }}</th>
                                    <th class="px-4 py-3">{{ __("Severity") }}</th>
                                    <th class="px-4 py-3">{{ __("Action") }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($events as $event)
                                    <tr class="bg-white border-b">
                                        <td class="px-4 py-3">{{ optional($event->occurred_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($event->event_type)->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3">{{ $event->principal_display ?: $event->principal ?: __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ $event->ip_address ?: __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ strtoupper($event->severity) }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('threat-events.show', $event) }}"
                                               class="text-white bg-slate-700 hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-medium rounded-lg text-sm px-4 py-2">
                                                {{ __("View Event") }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-3 text-gray-500">{{ __("No related events found.") }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $events->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
