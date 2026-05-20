<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __("Threat Events") }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('threat-events.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <label for="q" class="block mb-2 text-sm font-medium text-gray-900">{{ __("Search") }}</label>
                            <input type="text" id="q" name="q" value="{{ $filters['q'] }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>
                        <div>
                            <label for="severity" class="block mb-2 text-sm font-medium text-gray-900">{{ __("Severity") }}</label>
                            <select id="severity" name="severity"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <option value="">{{ __("All") }}</option>
                                @foreach(['high', 'medium', 'low', 'informational'] as $severity)
                                    <option value="{{ $severity }}" @selected($filters['severity'] === $severity)>{{ strtoupper($severity) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="event_type" class="block mb-2 text-sm font-medium text-gray-900">{{ __("Event Type") }}</label>
                            <select id="event_type" name="event_type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <option value="">{{ __("All") }}</option>
                                @foreach(['sign_in', 'risk_detection'] as $eventType)
                                    <option value="{{ $eventType }}" @selected($filters['event_type'] === $eventType)>{{ \Illuminate\Support\Str::of($eventType)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="integration_id" class="block mb-2 text-sm font-medium text-gray-900">{{ __("Integration") }}</label>
                            <select id="integration_id" name="integration_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <option value="">{{ __("All") }}</option>
                                @foreach($integrations as $integration)
                                    <option value="{{ $integration->id }}" @selected((string) $filters['integration_id'] === (string) $integration->id)>{{ $integration->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-4 flex gap-2">
                            <button type="submit"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __("Filter") }}
                            </button>
                            <a href="{{ route('threat-events.index') }}"
                               class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __("Clear") }}
                            </a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">{{ __("Occurred At") }}</th>
                                <th class="px-4 py-3">{{ __("Integration") }}</th>
                                <th class="px-4 py-3">{{ __("Type") }}</th>
                                <th class="px-4 py-3">{{ __("Principal") }}</th>
                                <th class="px-4 py-3">{{ __("Severity") }}</th>
                                <th class="px-4 py-3">{{ __("Score") }}</th>
                                <th class="px-4 py-3">{{ __("Action") }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($events as $event)
                                <tr class="bg-white border-b">
                                    <td class="px-4 py-3">{{ optional($event->occurred_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                    <td class="px-4 py-3">{{ $event->integration?->name ?? __('Unknown') }}</td>
                                    <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($event->event_type)->replace('_', ' ')->title() }}</td>
                                    <td class="px-4 py-3">{{ $event->principal_display ?: $event->principal ?: __('Unknown') }}</td>
                                    <td class="px-4 py-3">{{ strtoupper($event->severity) }}</td>
                                    <td class="px-4 py-3">{{ $event->score }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('threat-events.show', $event) }}"
                                           class="text-white bg-slate-700 hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-medium rounded-lg text-sm px-4 py-2">
                                            {{ __("View") }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-3 text-gray-500">{{ __("No threat events found.") }}</td>
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
</x-app-layout>
