<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __("Integrations") }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="GET" action="{{ route('integrations.index') }}">
                        <div class="mb-6">
                            <label for="filter"
                                   class="block mb-2 text-sm font-medium text-gray-900">{{ __("Name / Provider / Status") }}</label>
                            <div class="flex gap-2">
                                <input type="text" id="filter" name="filter" value="{{ $filter }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                <button type="submit"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                    {{ __("Search") }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">{{ __("ID") }}</th>
                            <th scope="col" class="px-6 py-3">{{ __("Name") }}</th>
                            <th scope="col" class="px-6 py-3">{{ __("Provider") }}</th>
                            <th scope="col" class="px-6 py-3">{{ __("Status") }}</th>
                            <th scope="col" class="px-6 py-3">{{ __("Last Sync") }}</th>
                            <th scope="col" class="px-6 py-3">{{ __("Action") }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($integrations as $integration)
                            <tr class="bg-white border-b">
                                <td class="px-6 py-4">{{ $integration->id }}</td>
                                <td class="px-6 py-4">{{ $integration->name }}</td>
                                <td class="px-6 py-4">{{ \Illuminate\Support\Str::of($integration->provider)->replace('_', ' ')->title() }}</td>
                                <td class="px-6 py-4">{{ ucfirst($integration->status) }}</td>
                                <td class="px-6 py-4">{{ optional($integration->last_synced_at)->diffForHumans() ?? __('Never') }}</td>
                                <td class="px-6 py-4 flex gap-2">
                                    <a href="{{ route('integrations.show', $integration) }}"
                                       class="text-white bg-slate-700 hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __("View") }}
                                    </a>
                                    <a href="{{ route('integrations.edit', $integration) }}"
                                       class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __("Manage") }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    {{ $integrations->links() }}

                    <div class="flex justify-center">
                        <a class="inline-flex items-center h-10 px-5 m-2 text-sm text-green-100 transition-colors duration-150 bg-green-700 rounded-lg focus:shadow-outline hover:bg-green-800"
                           href="{{ route('integrations.create') }}">
                            {{ __("Create") }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
