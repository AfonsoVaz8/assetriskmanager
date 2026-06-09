<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Assets Requiring Action') }}
        </h2>
    </x-slot>

    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-[1.5rem] border border-slate-200 bg-white px-6 py-6 shadow-sm">
                <p class="text-sm leading-6 text-slate-600">
                    {{ __('This list shows every asset currently above the priority threshold. Start with the asset view for overall context, then jump into external evidence when the asset still has active exposed hosts.') }}
                </p>
            </div>

            <div class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Asset') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Why First') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Exposure') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($assets as $asset)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <a href="{{ $asset->primary_href }}" class="font-semibold text-slate-900 hover:text-blue-700 hover:underline">
                                            {{ $asset->asset_name }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $asset->asset_type }} · {{ $asset->primary_identifier }}
                                        </div>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                                {{ __('Priority') }} {{ number_format((float) $asset->priority_score, 0) }}
                                            </span>
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700">
                                                {{ __('High-Risk Threats') }} {{ $asset->high_risk_threat_count }}
                                            </span>
                                            <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700">
                                                {{ __('KEV') }} {{ $asset->kev_cve_findings }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        {{ $asset->priority_reason ?: __('Priority score elevated by combined threat and exposure signals.') }}
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">
                                        <div>{{ __('CVEs') }}: <span class="font-semibold text-slate-900">{{ $asset->active_cve_findings }}</span></div>
                                        <div class="mt-1">{{ __('External Hosts') }}: <span class="font-semibold text-slate-900">{{ $asset->active_external_hosts }}</span></div>
                                        <div class="mt-1">{{ __('Residual Risk') }}: <span class="font-semibold text-slate-900">{{ $asset->max_residual_risk }}</span></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-2">
                                            <a href="{{ $asset->primary_href }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                {{ __('Open asset') }}
                                            </a>
                                            @if($asset->external_href)
                                                <a href="{{ $asset->external_href }}" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                                    {{ __('View external evidence') }}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                        {{ __('No assets are currently above the priority threshold.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $assets->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
