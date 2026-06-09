<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __("Threat Event Details") }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Integration") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $event->integration?->name ?? __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Occurred At") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ optional($event->occurred_at)->toDateTimeString() ?? __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Principal") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $event->principal_display ?: $event->principal ?: __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("IP Address") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $event->ip_address ?: __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Event Type") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \Illuminate\Support\Str::of($event->event_type)->replace('_', ' ')->title() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Severity / Confidence") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ strtoupper($event->severity) }} / {{ strtoupper($event->confidence) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Application") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $event->application_name ?: __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __("Score") }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $event->score }}</dd>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __("Findings") }}</h3>
                        <div class="space-y-3">
                            @forelse($event->findings ?? [] as $finding)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="font-medium text-gray-900">
                                        {{ \Illuminate\Support\Str::of($finding['name'] ?? __('Unnamed finding'))->replace('_', ' ')->title() }}
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">{{ $finding['description'] ?? '' }}</div>
                                    <div class="text-sm text-gray-500 mt-2">{{ __("Points") }}: {{ $finding['points'] ?? 0 }}</div>
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
                            @empty
                                <p class="text-sm text-gray-500">{{ __("No findings recorded.") }}</p>
                            @endforelse
                        </div>
                    </div>

                    @if(!empty($relatedSignIn))
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __("Related Sign-In") }}</h3>
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

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __("Normalized Payload") }}</h3>
                        <pre class="bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode($event->normalized_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __("Raw Payload") }}</h3>
                        <pre class="bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode($event->raw_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    @if($event->incidents->isNotEmpty())
                        <div>
                            <a href="{{ route('incidents.show', $event->incidents->first()) }}"
                               class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __("Open Related Alert") }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
