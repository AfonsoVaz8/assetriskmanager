<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $defaultGrafanaDashboard = $grafanaDashboards[0] ?? null;
    @endphp

    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
            @if($showSecurityDashboard && !empty($grafanaDashboards))
                <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl"
                         x-data="{ selectedDashboard: '{{ $defaultGrafanaDashboard['key'] ?? '' }}' }">
                    <div class="border-b border-slate-200 bg-slate-50/80 px-6 py-6 lg:px-8">
                        <div class="max-w-3xl">
                            <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">
                                {{ __('Security Monitoring') }}
                            </div>
                            <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                                {{ __('Embedded SOC dashboards inside the application') }}
                            </h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ __('These views are rendered as embedded Grafana panels, without the normal Grafana navigation or edit controls, so the monitoring experience stays inside ARM.') }}
                            </p>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            @foreach($grafanaDashboards as $index => $dashboard)
                                <button type="button"
                                        @click="selectedDashboard = '{{ $dashboard['key'] }}'"
                                        :class="selectedDashboard === '{{ $dashboard['key'] }}'
                                            ? 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-900/10'
                                            : 'border-slate-200 bg-white text-slate-900 hover:border-slate-300 hover:bg-slate-50'"
                                        class="group rounded-[1.5rem] border p-5 text-left transition">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div :class="selectedDashboard === '{{ $dashboard['key'] }}' ? 'text-slate-300' : 'text-slate-500'"
                                                 class="text-xs font-semibold uppercase tracking-[0.2em]">
                                                {{ __('Dashboard') }} {{ $index + 1 }}
                                            </div>
                                            <div class="mt-2 text-lg font-semibold">
                                                {{ $dashboard['title'] }}
                                            </div>
                                        </div>
                                        <div :class="selectedDashboard === '{{ $dashboard['key'] }}' ? 'bg-white/10 text-slate-100' : 'bg-slate-100 text-slate-500'"
                                             class="rounded-full px-3 py-1 text-xs font-semibold">
                                            {{ __('Live') }}
                                        </div>
                                    </div>
                                    <p :class="selectedDashboard === '{{ $dashboard['key'] }}' ? 'text-slate-300' : 'text-slate-500'"
                                       class="mt-4 text-sm leading-6">
                                        {{ $dashboard['description'] }}
                                    </p>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                        @foreach($grafanaDashboards as $dashboard)
                            <template x-if="selectedDashboard === '{{ $dashboard['key'] }}'">
                            <div class="space-y-4">
                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                                    <div>
                                        <h3 class="text-xl font-semibold text-slate-900">{{ $dashboard['title'] }}</h3>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $dashboard['description'] }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-5 xl:grid-cols-12">
                                    @foreach($dashboard['panels'] as $panel)
                                        @php
                                            $panelSize = $panel['size'] ?? 'chart';
                                            $panelClasses = match ($panelSize) {
                                                'stat' => 'xl:col-span-3',
                                                'table' => 'xl:col-span-6',
                                                default => 'xl:col-span-6',
                                            };
                                            $panelHeight = match ($panelSize) {
                                                'stat' => 180,
                                                'table' => 420,
                                                default => 320,
                                            };
                                        @endphp
                                        <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm {{ $panelClasses }}">
                                            <div class="border-b border-slate-200 px-4 py-3">
                                                <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                    {{ $panel['title'] }}
                                                </h4>
                                            </div>
                                            <div class="overflow-hidden rounded-b-[1.5rem] bg-white">
                                                <iframe
                                                    src="{{ $panel['embed_url'] }}"
                                                    title="{{ $panel['title'] }}"
                                                    class="w-full bg-white"
                                                    style="height: {{ $panelHeight }}px; border: 0;"
                                                    loading="lazy"
                                                    referrerpolicy="strict-origin-when-cross-origin"></iframe>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($dashboard['key'] === 'soc_overview')
                                    <section class="space-y-4">
                                        <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-5">
                                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">
                                                {{ __('Pending Actions') }}
                                            </div>
                                            <h4 class="mt-2 text-lg font-semibold text-slate-900">
                                                {{ __('Requires Action First') }}
                                            </h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-700">
                                                {{ __('Use these lists as the first operational queue. M365 accounts rise when they concentrate alerts or repeated suspicious activity. Assets rise when they combine high-risk threats, active CVEs, KEV exposure or visible external exposure.') }}
                                            </p>
                                        </div>

                                        <div class="grid gap-5 xl:grid-cols-12">
                                            <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm xl:col-span-6">
                                                <div class="border-b border-slate-200 px-5 py-4">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                                {{ __('M365 Accounts Requiring Action') }}
                                                            </h4>
                                                            <p class="mt-1 text-sm text-slate-600">
                                                                {{ __('Open the related alerts first. If there are no alerts yet, jump directly into the underlying threat events.') }}
                                                            </p>
                                                        </div>
                                                        <a href="{{ route('dashboard.pending-m365-accounts') }}"
                                                           class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                                            {{ __('View all') }}
                                                        </a>
                                                    </div>
                                                </div>

                                                @if($pendingM365Accounts->isEmpty())
                                                    <div class="px-5 py-6 text-sm text-slate-500">
                                                        {{ __('No M365 accounts are currently above the priority threshold.') }}
                                                    </div>
                                                @else
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                            <thead class="bg-slate-50">
                                                                <tr>
                                                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Account') }}</th>
                                                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Why First') }}</th>
                                                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Pressure') }}</th>
                                                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">{{ __('Action') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100">
                                                                @foreach($pendingM365Accounts as $account)
                                                                    <tr class="align-top">
                                                                        <td class="px-4 py-4">
                                                                            <a href="{{ $account->primary_href }}" class="font-semibold text-slate-900 hover:text-blue-700 hover:underline">
                                                                                {{ $account->principal }}
                                                                            </a>
                                                                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                                                                                    {{ __('Priority') }} {{ number_format((float) $account->priority_score, 0) }}
                                                                                </span>
                                                                                <span class="rounded-full bg-red-50 px-2.5 py-1 font-semibold text-red-700">
                                                                                    {{ __('Alerts') }} {{ $account->incident_count }}
                                                                                </span>
                                                                                <span class="rounded-full bg-amber-50 px-2.5 py-1 font-semibold text-amber-700">
                                                                                    {{ __('Risk Signals') }} {{ $account->risky_event_count }}
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-4 py-4 text-slate-600">
                                                                            {{ $account->priority_reason ?: __('Priority score elevated by repeated suspicious activity.') }}
                                                                            <div class="mt-2 text-xs text-slate-500">
                                                                                {{ __('Last seen') }}: {{ $account->last_seen_at ?: __('Unknown') }}
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-4 py-4 text-slate-600">
                                                                            <div>{{ __('Max score') }}: <span class="font-semibold text-slate-900">{{ $account->max_score ?? 0 }}</span></div>
                                                                            <div class="mt-1">{{ __('Failures') }}: <span class="font-semibold text-slate-900">{{ $account->failure_event_count }}</span></div>
                                                                        </td>
                                                                        <td class="px-4 py-4">
                                                                            <div class="flex flex-col gap-2">
                                                                                <a href="{{ $account->primary_href }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                                                    {{ $account->primary_label }}
                                                                                </a>
                                                                                <a href="{{ $account->events_href }}" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                                                                    {{ __('View events') }}
                                                                                </a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="rounded-[1.5rem] border border-slate-200 bg-white shadow-sm xl:col-span-6">
                                                <div class="border-b border-slate-200 px-5 py-4">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                                {{ __('Assets Requiring Action') }}
                                                            </h4>
                                                            <p class="mt-1 text-sm text-slate-600">
                                                                {{ __('Use the asset view for overall risk context. If the asset has active external exposure, jump directly into discovered host details for technical evidence.') }}
                                                            </p>
                                                        </div>
                                                        <a href="{{ route('dashboard.pending-assets') }}"
                                                           class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                                            {{ __('View all') }}
                                                        </a>
                                                    </div>
                                                </div>

                                                @if($pendingAssets->isEmpty())
                                                    <div class="px-5 py-6 text-sm text-slate-500">
                                                        {{ __('No assets are currently above the priority threshold.') }}
                                                    </div>
                                                @else
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
                                                                @foreach($pendingAssets as $asset)
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
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </section>
                                @endif
                            </div>
                            </template>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
