<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Attack Surface Scope Details') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 space-y-8">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900">{{ $scope->name }}</h3>
                            <p class="text-sm text-gray-500">{{ \Illuminate\Support\Str::of($scope->type->value ?? $scope->type)->replace('_', ' ')->title() }}</p>
                        </div>
                        <div class="flex gap-2">
                            @if(($scope->status->value ?? $scope->status) === \App\Enums\AttackSurfaceScopeStatus::DRAFT->value)
                                <form method="POST" action="{{ route('attack-surface-scopes.approve', $scope) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-white bg-emerald-700 hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __('Approve') }}
                                    </button>
                                </form>
                            @endif
                            @if(($scope->status->value ?? $scope->status) === \App\Enums\AttackSurfaceScopeStatus::APPROVED->value)
                                <form method="POST" action="{{ route('attack-surface-scopes.run', $scope) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-white bg-emerald-700 hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __('Run Discovery') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('attack-surface-scopes.enrich-active', $scope) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __('Enrich All Active Hosts') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('attack-surface-scopes.disable', $scope) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-white bg-amber-700 hover:bg-amber-800 focus:ring-4 focus:ring-amber-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __('Disable') }}
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('attack-surface-scopes.edit', $scope) }}"
                               class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __('Edit') }}
                            </a>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \Illuminate\Support\Str::of($scope->status->value ?? $scope->status)->replace('_', ' ')->title() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Discovery Target Summary') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if(($scope->type->value ?? $scope->type) === \App\Enums\AttackSurfaceScopeType::HOSTNAME_TARGET->value)
                                    {{ __('Hostname/domain target: :hostname', ['hostname' => data_get($scope->scope_definition, 'hostname', __('Unknown'))]) }}
                                @elseif(($scope->type->value ?? $scope->type) === \App\Enums\AttackSurfaceScopeType::DOMAIN_EXPANSION->value)
                                    {{ __('Domain expansion target: :domain', ['domain' => data_get($scope->scope_definition, 'domain', __('Unknown'))]) }}
                                @elseif(($scope->type->value ?? $scope->type) === \App\Enums\AttackSurfaceScopeType::CIDR_RANGE->value)
                                    {{ __('CIDR range: :cidr', ['cidr' => data_get($scope->scope_definition, 'cidr', __('Unknown'))]) }}
                                @else
                                    {{ __('All registered assets with an IP address') }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Last Run') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ optional($scope->last_run_at)->toDateTimeString() ?? __('Never') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Submitted By') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scope->submittedBy?->name ?? __('Unknown') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Approved By') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $scope->approvedBy?->name ?? __('Not approved yet') }}</dd>
                        </div>
                    </dl>

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Scope Definition') }}</h4>
                        @if(($scope->type->value ?? $scope->type) === \App\Enums\AttackSurfaceScopeType::HOSTNAME_TARGET->value)
                            <p class="text-sm text-gray-500 mb-3">
                                {{ __('This scope resolves the configured hostname/domain to one or more IPs and probes those IPs to discover additional exposed assets.') }}
                            </p>
                        @elseif(($scope->type->value ?? $scope->type) === \App\Enums\AttackSurfaceScopeType::DOMAIN_EXPANSION->value)
                            <p class="text-sm text-gray-500 mb-3">
                                {{ __('This scope uses passive subdomain discovery for the configured base domain, resolves the discovered hostnames to IPs and probes those IPs to expand the external attack surface more broadly.') }}
                            </p>
                        @endif
                        <pre class="bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode($scope->scope_definition ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Safe Discovery Settings') }}</h4>
                        <pre class="bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode($scope->settings ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Configured Scanners') }}</h4>
                        <pre class="bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode(data_get($scope->settings, 'scanners', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Threat Classification Rules') }}</h4>
                        <pre class="bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode(data_get($scope->settings, 'threat_rules', []), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Justification') }}</h4>
                        <p class="text-sm text-gray-900 whitespace-pre-line">{{ $scope->justification ?: __('None provided') }}</p>
                    </div>

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Recent Runs') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">{{ __('ID') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Targets') }}</th>
                                    <th class="px-4 py-3">{{ __('Active Hosts') }}</th>
                                    <th class="px-4 py-3">{{ __('Created Assets') }}</th>
                                    <th class="px-4 py-3">{{ __('Finished At') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($scope->runs as $run)
                                    <tr class="bg-white border-b">
                                        <td class="px-4 py-3">{{ $run->id }}</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($run->status->value ?? $run->status)->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3">{{ $run->target_count }}</td>
                                        <td class="px-4 py-3">{{ $run->active_host_count }}</td>
                                        <td class="px-4 py-3">{{ $run->created_asset_count }}</td>
                                        <td class="px-4 py-3">{{ optional($run->finished_at)->toDateTimeString() ?? __('Running / Pending') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-3 text-gray-500">{{ __('No runs have been dispatched yet.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">{{ __('Recently Discovered Hosts') }}</h4>
                                <p class="text-sm text-gray-500 mt-1">{{ __('You can enrich all active hosts, only the selected active hosts, or a single host from the table.') }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="submit"
                                        form="enrich-selected-hosts-form"
                                        class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                    {{ __('Enrich Selected Hosts') }}
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Select') }}</th>
                                    <th class="px-4 py-3">{{ __('IP') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Open Ports') }}</th>
                                    <th class="px-4 py-3">{{ __('Last Enrichment') }}</th>
                                    <th class="px-4 py-3">{{ __('Asset') }}</th>
                                    <th class="px-4 py-3">{{ __('Last Seen') }}</th>
                                    <th class="px-4 py-3">{{ __('Error') }}</th>
                                    <th class="px-4 py-3">{{ __('Actions') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($scope->discoveredHosts as $host)
                                    <tr class="bg-white border-b">
                                        <td class="px-4 py-3">
                                            @if(($host->status->value ?? $host->status) === \App\Enums\DiscoveredHostStatus::ACTIVE->value)
                                                <input type="checkbox"
                                                       name="host_ids[]"
                                                       value="{{ $host->id }}"
                                                       form="enrich-selected-hosts-form"
                                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            @else
                                                <span class="text-xs text-gray-400">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('attack-surface-scopes.hosts.show', [$scope, $host]) }}"
                                               class="text-blue-700 hover:underline font-medium">
                                                {{ $host->ip_address }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($host->status->value ?? $host->status)->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3">{{ implode(', ', $host->open_ports ?? []) ?: __('None') }}</td>
                                        <td class="px-4 py-3">
                                            @if($host->latestEnrichmentRun)
                                                {{ strtoupper($host->latestEnrichmentRun->provider) }} / {{ \Illuminate\Support\Str::of($host->latestEnrichmentRun->status)->replace('_', ' ')->title() }}
                                            @else
                                                {{ __('Never') }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">{{ $host->asset?->name ?? __('Not linked') }}</td>
                                        <td class="px-4 py-3">{{ optional($host->last_seen_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ $host->error ?: __('None') }}</td>
                                        <td class="px-4 py-3">
                                            @if(($host->status->value ?? $host->status) === \App\Enums\DiscoveredHostStatus::ACTIVE->value)
                                                <form method="POST" action="{{ route('attack-surface-scopes.hosts.enrich', [$scope, $host]) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-xs px-3 py-2">
                                                        {{ __('Enrich This Host') }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400">{{ __('Only active hosts can be enriched') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-3 text-gray-500">{{ __('No discovered hosts have been recorded yet.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <form id="enrich-selected-hosts-form" method="POST" action="{{ route('attack-surface-scopes.enrich-selected', $scope) }}" class="hidden">
                            @csrf
                        </form>
                    </div>

                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('attack-surface-scopes.destroy', $scope) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5"
                                    onclick="return confirm('{{ __('Delete this attack surface scope?') }}')">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
