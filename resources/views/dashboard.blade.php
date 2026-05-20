<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-xl sm:rounded-lg">
                @if(!empty($incidentSummary))
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-center text-2xl font-normal leading-normal mt-0 mb-6">{{ __("Incident Overview") }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                            @foreach([
                                __('Open') => $incidentSummary['open'] ?? 0,
                                __('In Progress') => $incidentSummary['in_progress'] ?? 0,
                                __('Resolved') => $incidentSummary['resolved'] ?? 0,
                                __('Dismissed') => $incidentSummary['dismissed'] ?? 0,
                                __('High Severity') => $incidentSummary['high'] ?? 0,
                                __('Medium Severity') => $incidentSummary['medium'] ?? 0,
                            ] as $label => $value)
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <div class="text-sm font-medium text-gray-500">{{ $label }}</div>
                                    <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $value }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-medium text-gray-900">{{ __("Recent Incidents") }}</h3>
                            <a href="{{ route('incidents.index') }}"
                               class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __("Open Incident Queue") }}
                            </a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3">{{ __("Title") }}</th>
                                    <th class="px-6 py-3">{{ __("Affected User") }}</th>
                                    <th class="px-6 py-3">{{ __("Severity") }}</th>
                                    <th class="px-6 py-3">{{ __("Events") }}</th>
                                    <th class="px-6 py-3">{{ __("Status") }}</th>
                                    <th class="px-6 py-3">{{ __("Last Activity") }}</th>
                                    <th class="px-6 py-3">{{ __("Action") }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentIncidents as $incident)
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4">{{ $incident->title }}</td>
                                        <td class="px-6 py-4">{{ $incident->affected_principal_display ?: $incident->affected_principal ?: __('Unknown') }}</td>
                                        <td class="px-6 py-4">{{ strtoupper($incident->severity) }}</td>
                                        <td class="px-6 py-4">{{ $incident->event_count }}</td>
                                        <td class="px-6 py-4">{{ \Illuminate\Support\Str::of($incident->status->value ?? $incident->status)->replace('_', ' ')->title() }}</td>
                                        <td class="px-6 py-4">{{ optional($incident->last_seen_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('incidents.show', $incident) }}"
                                               class="text-white bg-slate-700 hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-medium rounded-lg text-sm px-4 py-2">
                                                {{ __("View") }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-gray-500">{{ __("No incidents found.") }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(count($assetsWithControlsToValidate)>0)
                    <h2 class="text-center text-2xl font-normal leading-normal mt-0 mb-2">{{__("Assets with Controls to Be Validated")}}</h2>
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                {{__("ID")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Name")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Type")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Threats")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Action")}}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($assetsWithControlsToValidate as $asset)
                            <tr class="{{$asset->remainingRiskAccepted ? "bg-green-300" : "bg-white"}} border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{$asset->id}}</td>
                                <td class="px-6 py-4">{{$asset->name}}</td>
                                <td class="px-6 py-4">{{$asset->type->name}}</td>
                                <td class="px-6 py-4">{{$asset->threats()->count()}}</td>
                                <td class="px-6 py-4">
                                    <a href="{{route("assets.edit",$asset->id).'#threats-controls-tab'}}"
                                       target="_blank"
                                       class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                        {{__("Manage")}}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
                @if(count($tasks)>0)
                    <h2 class="text-center text-2xl font-normal leading-normal mt-0 mb-2">{{__("Pending Tasks")}}</h2>
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead
                                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">
                                {{__("ID")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Name")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Type")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Task Description")}}
                            </th>
                            <th scope="col" class="px-6 py-3">
                                {{__("Action")}}
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($tasks as $task)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="px-6 py-4">{{$task["asset"]->id}}</td>
                                <td class="px-6 py-4">{{$task["asset"]->name}}</td>
                                <td class="px-6 py-4">{{$task["asset"]->type->name}}</td>
                                <td class="px-6 py-4">{{$task["message"]}}</td>
                                <td class="px-6 py-4">
                                    <a href="{{route("assets.edit",$task["asset"]->id).'#'.$task["tab"]}}"
                                       target="_blank"
                                       class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                        {{__("Manage")}}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>