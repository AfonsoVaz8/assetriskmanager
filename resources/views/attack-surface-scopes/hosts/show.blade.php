<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Discovered Host Details') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @php
                $latestRun = $host->latestEnrichmentRun;
                $latestNormalizedPayload = $latestRun?->normalized_payload ?? $host->normalized_payload ?? [];
                $scanStages = collect($scanStages ?? []);
                $technicalProfile = $technicalProfile ?? [];
                $activeFindings = $host->findings;
                $promotedThreats = $promotedThreats ?? collect();
                $assetTypeInference = $assetTypeInference ?? [];
                $inferredAssetTypeName = data_get($assetTypeInference, 'asset_type_name');
                $inferredAssetTypeConfidence = data_get($assetTypeInference, 'confidence');
                $inferredAssetTypeReasons = collect(data_get($assetTypeInference, 'reasons', []))
                    ->filter(fn ($reason) => filled($reason))
                    ->values();
                $promotedThreatsByFindingId = $promotedThreats
                    ->filter(fn ($threat) => filled(data_get($threat->source_context, 'finding_id')))
                    ->groupBy(fn ($threat) => (int) data_get($threat->source_context, 'finding_id'));
                $resolveFindingCategory = function ($finding): string {
                    $templatePath = (string) data_get($finding->context, 'template_path', '');

                    return match (true) {
                        $templatePath !== '' && $templatePath !== 'Not Found' && str_contains($templatePath, '/http/cves/') => __('Nuclei CVE'),
                        $templatePath !== '' && $templatePath !== 'Not Found' && str_contains($templatePath, '/http/exposures/') => __('Nuclei Exposure'),
                        $templatePath !== '' && $templatePath !== 'Not Found' && str_contains($templatePath, '/http/misconfiguration/') => __('Nuclei Misconfiguration'),
                        $templatePath !== '' && $templatePath !== 'Not Found' && str_contains($templatePath, '/http/exposed-panels/') => __('Nuclei Exposed Panel'),
                        $templatePath !== '' && $templatePath !== 'Not Found' && str_contains($templatePath, '/http/technologies/') => __('Nuclei Technology'),
                        $templatePath !== '' && $templatePath !== 'Not Found' && str_contains($templatePath, '/ssl/') => __('Nuclei SSL/TLS'),
                        str_contains(strtolower((string) $finding->source), 'nikto') => __('Nikto Web Check'),
                        str_contains(strtolower((string) $finding->source), 'nmap') => __('Nmap Service Discovery'),
                        str_contains(strtolower((string) $finding->source), 'shodan') => __('Shodan Intelligence'),
                        default => \Illuminate\Support\Str::of($finding->kind)->replace('_', ' ')->title(),
                    };
                };

                $criticalFindings = $activeFindings->filter(fn ($finding) => strtolower((string) $finding->severity) === 'critical');
                $highFindings = $activeFindings->filter(fn ($finding) => strtolower((string) $finding->severity) === 'high');
                $priorityFindings = $activeFindings
                    ->sortByDesc(function ($finding) {
                        return match (strtolower((string) $finding->severity)) {
                            'critical' => 5,
                            'high' => 4,
                            'medium' => 3,
                            'low' => 2,
                            default => 1,
                        };
                    })
                    ->take(5)
                    ->values();

                $findingSourceCounts = $activeFindings
                    ->groupBy(fn ($finding) => (string) $finding->source)
                    ->map->count()
                    ->sortDesc();

                $findingSeverityCounts = $activeFindings
                    ->groupBy(fn ($finding) => strtolower((string) ($finding->severity ?: 'not_specified')))
                    ->map->count()
                    ->sortDesc();

                $findingCategoryCounts = $activeFindings
                    ->groupBy(fn ($finding) => (string) $resolveFindingCategory($finding))
                    ->map->count()
                    ->sortDesc();
                $findingRows = $activeFindings
                    ->map(function ($finding) use ($resolveFindingCategory, $promotedThreatsByFindingId) {
                        return [
                            'finding' => $finding,
                            'category' => $resolveFindingCategory($finding),
                            'template_id' => data_get($finding->context, 'template_id'),
                            'linked_threats' => $promotedThreatsByFindingId->get($finding->id, collect()),
                            'evidence_parts' => collect([
                                data_get($finding->context, 'port') ? __('Port').': '.data_get($finding->context, 'port') : null,
                                data_get($finding->context, 'service') ? __('Service').': '.data_get($finding->context, 'service') : null,
                                data_get($finding->context, 'technology') ? __('Technology').': '.data_get($finding->context, 'technology') : null,
                                data_get($finding->context, 'cve') ? __('CVE').': '.data_get($finding->context, 'cve') : null,
                                data_get($finding->context, 'url') ? __('URL').': '.data_get($finding->context, 'url') : null,
                                data_get($finding->context, 'score') ? __('Score').': '.data_get($finding->context, 'score') : null,
                                data_get($finding->context, 'template_id') ? __('Template').': '.data_get($finding->context, 'template_id') : null,
                                data_get($finding->context, 'matcher_name') ? __('Matcher').': '.data_get($finding->context, 'matcher_name') : null,
                            ])->filter()->values(),
                        ];
                    })
                    ->values();

                $openPorts = collect($host->open_ports ?? [])->filter()->values();
                $latestWarnings = collect(data_get($latestRun?->normalized_payload, 'metadata.warnings', []))
                    ->filter(fn ($warning) => filled($warning))
                    ->values();
                $serviceRows = collect(data_get($technicalProfile, 'services', []))
                    ->filter(fn ($service) => is_array($service))
                    ->values();
                $technologyRows = collect(data_get($technicalProfile, 'technologies', []))
                    ->filter(fn ($technology) => filled($technology))
                    ->values();
                $certificateRows = collect(data_get($technicalProfile, 'certificates', []))
                    ->filter(function ($certificate) {
                        return is_array($certificate) && $certificate !== [];
                    })
                    ->values();
                $vulnerabilityRows = collect(data_get($technicalProfile, 'vulnerabilities', []))
                    ->filter(function ($vulnerability) {
                        return is_array($vulnerability)
                            && filled(data_get($vulnerability, 'cve'));
                    })
                    ->values();
                $hostnames = collect(data_get($technicalProfile, 'hostnames', []))
                    ->filter(fn ($hostname) => filled($hostname))
                    ->values();
                $domains = collect(data_get($technicalProfile, 'domains', []))
                    ->filter(fn ($domain) => filled($domain))
                    ->values();
                $operatingSystem = data_get($technicalProfile, 'operating_system');
                $organization = data_get($technicalProfile, 'organization');
                $isp = data_get($technicalProfile, 'isp');
                $asn = data_get($technicalProfile, 'asn');
                $country = data_get($technicalProfile, 'country');
                $city = data_get($technicalProfile, 'city');
                $region = data_get($technicalProfile, 'region');
                $reputationScore = data_get($technicalProfile, 'reputation.score');
                $reputationTags = collect(data_get($technicalProfile, 'reputation.tags', []))
                    ->filter(fn ($tag) => filled($tag))
                    ->values();
                $identityCoverageMissing = !filled($organization) && !filled($isp) && !filled($asn) && !filled($country) && !filled($city) && !filled($region);
                $observedCpeCandidates = collect($observedCpeCandidates ?? [])
                    ->filter(fn ($candidate) => filled(data_get($candidate, 'cpe')))
                    ->values();
                $primaryObservedCpe = $observedCpeCandidates->first();
                $assignedAssetCpe = $host->asset?->detected_cpe;
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 space-y-8">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="space-y-3">
                            <p class="text-sm text-gray-500">
                                <a href="{{ route('attack-surface-scopes.show', $scope) }}" class="text-blue-700 hover:underline">
                                    {{ $scope->name }}
                                </a>
                            </p>
                            <div>
                                <h3 class="text-3xl font-semibold text-gray-900">{{ $host->ip_address }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $host->fqdn ?: __('No hostname resolved') }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ __('Status') }}: {{ \Illuminate\Support\Str::of($host->status->value ?? $host->status)->replace('_', ' ')->title() }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ __('Origin') }}: {{ $host->origin ?: __('Unknown') }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ __('Discovery Method') }}: {{ $host->discovery_method ?: __('Unknown') }}
                                </span>
                                @if($host->asset)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                        {{ __('Linked Asset') }}: {{ $host->asset->name }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 xl:justify-end">
                            @if(!$host->asset)
                                <form method="POST" action="{{ route('attack-surface-scopes.hosts.add-to-assets', [$scope, $host]) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-white bg-emerald-700 hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __('Add to Assets') }}
                                    </button>
                                </form>
                            @endif
                            @if(($host->status->value ?? $host->status) === \App\Enums\DiscoveredHostStatus::ACTIVE->value)
                                <form method="POST" action="{{ route('attack-surface-scopes.hosts.enrich', [$scope, $host]) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                        {{ __('Enrich This Host') }}
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('attack-surface-scopes.show', $scope) }}"
                               class="text-white bg-slate-700 hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __('Back to Scope') }}
                            </a>
                        </div>
                    </div>

                    @if($promotedThreats->isNotEmpty())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-rose-700">{{ __('Threats need attention') }}</div>
                                    <p class="mt-1 text-sm text-rose-600">
                                        {{ __('This host already generated asset threats after matching the scope rules. Review the promoted threats section below.') }}
                                    </p>
                                </div>
                                <div class="inline-flex items-center rounded-full bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700">
                                    {{ $promotedThreats->count() }} {{ __('Promoted Threats') }}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($latestRun?->error)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div class="text-sm font-semibold text-amber-700">{{ __('Latest enrichment reported an error') }}</div>
                            <p class="mt-1 text-sm text-amber-700">{{ $latestRun->error }}</p>
                        </div>
                    @endif

                    @if($latestWarnings->isNotEmpty())
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <div class="text-sm font-semibold text-blue-700">{{ __('Latest enrichment warnings') }}</div>
                            <ul class="mt-2 space-y-1 text-sm text-blue-700">
                                @foreach($latestWarnings as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">{{ __('Risk Snapshot') }}</h4>
                        <p class="mt-1 text-sm text-gray-500">{{ __('The most important host indicators are shown first so the user can decide quickly whether deeper review is needed.') }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                            <div class="text-sm font-medium text-slate-500">{{ __('Open Ports') }}</div>
                            <div class="mt-3 text-3xl font-semibold text-slate-900">{{ $openPorts->count() }}</div>
                            <div class="mt-2 text-sm text-slate-600">{{ $openPorts->isNotEmpty() ? $openPorts->implode(', ') : __('None reported') }}</div>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                            <div class="text-sm font-medium text-amber-700">{{ __('Active Findings') }}</div>
                            <div class="mt-3 text-3xl font-semibold text-amber-900">{{ $activeFindings->count() }}</div>
                            <div class="mt-2 text-sm text-amber-700">
                                {{ $criticalFindings->count() }} {{ __('critical') }}, {{ $highFindings->count() }} {{ __('high') }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-5">
                            <div class="text-sm font-medium text-rose-700">{{ __('Promoted Threats') }}</div>
                            <div class="mt-3 text-3xl font-semibold text-rose-900">{{ $promotedThreats->count() }}</div>
                            <div class="mt-2 text-sm text-rose-700">
                                {{ $promotedThreats->isNotEmpty() ? __('Requires review in the linked asset.') : __('No threats created from this host yet.') }}
                            </div>
                        </div>

                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                            <div class="text-sm font-medium text-blue-700">{{ __('Latest Enrichment') }}</div>
                            <div class="mt-3 text-lg font-semibold text-blue-900">
                                {{ $latestRun ? \Illuminate\Support\Str::of($latestRun->provider)->replace('_', ' ')->title() : __('Not Run Yet') }}
                            </div>
                            <div class="mt-2 text-sm text-blue-700">
                                {{ $latestRun ? \Illuminate\Support\Str::of($latestRun->status)->replace('_', ' ')->title() : __('No enrichment recorded') }}
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-6 space-y-5">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">{{ __('Scan Process') }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Shows which enrichment/scanner stages were configured, whether they ran successfully, and what each stage is supposed to contribute.') }}</p>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                            @forelse($scanStages as $stage)
                                @php
                                    $stageClasses = match ($stage['status']) {
                                        'synced' => 'border-emerald-200 bg-emerald-50',
                                        'error' => 'border-rose-200 bg-rose-50',
                                        'running' => 'border-blue-200 bg-blue-50',
                                        default => 'border-slate-200 bg-slate-50',
                                    };
                                    $stageTextClasses = match ($stage['status']) {
                                        'synced' => 'text-emerald-700',
                                        'error' => 'text-rose-700',
                                        'running' => 'text-blue-700',
                                        default => 'text-slate-700',
                                    };
                                @endphp
                                <div class="rounded-xl border p-5 {{ $stageClasses }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="text-base font-semibold text-gray-900">{{ $stage['label'] }}</div>
                                            <p class="mt-1 text-sm {{ $stageTextClasses }}">{{ $stage['purpose'] }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold {{ $stageTextClasses }}">
                                            {{ $stage['status_label'] }}
                                        </span>
                                    </div>

                                    <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <dt class="text-gray-500">{{ __('Last Run') }}</dt>
                                            <dd class="mt-1 text-gray-900">{{ $stage['run_id'] ? '#'.$stage['run_id'] : __('Not run yet') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">{{ __('Finished At') }}</dt>
                                            <dd class="mt-1 text-gray-900">{{ optional($stage['finished_at'])->toDateTimeString() ?? __('Not finished') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">{{ __('Open Ports Reported') }}</dt>
                                            <dd class="mt-1 text-gray-900">{{ collect($stage['open_ports'] ?? [])->filter()->implode(', ') ?: __('None reported by this stage') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-500">{{ __('Vulnerabilities Reported') }}</dt>
                                            <dd class="mt-1 text-gray-900">{{ count($stage['vulnerabilities'] ?? []) }}</dd>
                                        </div>
                                    </dl>

                                    @if(filled($stage['error']))
                                        <div class="mt-4 rounded-lg border border-rose-200 bg-white/70 p-3 text-sm text-rose-700">
                                            <div class="font-semibold">{{ __('Stage Error') }}</div>
                                            <div class="mt-1">{{ $stage['error'] }}</div>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500 xl:col-span-2">
                                    {{ __('No enrichment provider or scanner stage has been configured for this host yet.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <div class="xl:col-span-2 rounded-xl border border-gray-200 p-6">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ __('Exposure Summary') }}</h4>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('Core host context and latest enrichment status.') }}</p>
                                </div>
                            </div>

                            <dl class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Linked Asset') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if($host->asset)
                                            <a href="{{ route('assets.show', $host->asset) }}" class="text-blue-700 hover:underline">{{ $host->asset->name }}</a>
                                        @else
                                            {{ __('Not linked') }}
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Latest Provider') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $latestRun ? \Illuminate\Support\Str::of($latestRun->provider)->replace('_', ' ')->title() : __('Not run yet') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Inferred Asset Type') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ filled($inferredAssetTypeName) ? $inferredAssetTypeName : __('Not inferred yet') }}
                                        @if(filled($inferredAssetTypeConfidence))
                                            <span class="text-gray-500">({{ \Illuminate\Support\Str::of($inferredAssetTypeConfidence)->title() }} {{ __('confidence') }})</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('First Seen') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ optional($host->first_seen_at)->toDateTimeString() ?? __('Unknown') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Last Seen') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ optional($host->last_seen_at)->toDateTimeString() ?? __('Unknown') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Latest Sync Time') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ optional($latestRun?->synced_at)->toDateTimeString() ?? __('Not synced') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Hostname') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $host->fqdn ?: __('Not resolved') }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="rounded-xl border border-gray-200 p-6">
                            <h4 class="text-lg font-semibold text-gray-900">{{ __('Priority Findings') }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Top findings sorted by severity so the user sees the most relevant issues first.') }}</p>

                            <div class="mt-5 space-y-3">
                                @forelse($priorityFindings as $finding)
                                    @php
                                        $prioritySeverity = strtolower((string) ($finding->severity ?: 'not_specified'));
                                        $priorityClasses = match ($prioritySeverity) {
                                            'critical' => 'border-rose-200 bg-rose-50 text-rose-700',
                                            'high' => 'border-amber-200 bg-amber-50 text-amber-700',
                                            'medium' => 'border-blue-200 bg-blue-50 text-blue-700',
                                            default => 'border-slate-200 bg-slate-50 text-slate-700',
                                        };
                                    @endphp
                                    <div class="rounded-lg border p-4 {{ $priorityClasses }}">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-sm font-semibold">{{ $finding->title }}</div>
                                                <div class="mt-1 text-xs">
                                                    {{ \Illuminate\Support\Str::of($finding->source)->replace('_', ' ')->title() }}
                                                    @if($finding->severity)
                                                        · {{ \Illuminate\Support\Str::of($finding->severity)->replace('_', ' ')->title() }}
                                                    @endif
                                                </div>
                                            </div>
                                            @if($promotedThreatsByFindingId->has($finding->id))
                                                <span class="inline-flex items-center rounded-full bg-white/80 px-2.5 py-1 text-xs font-semibold">
                                                    {{ __('Threat') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">
                                        {{ __('No active findings have been derived from the latest enrichment yet.') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-6 space-y-6">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">{{ __('Technical Profile') }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Key information discovered by the scanners and enrichment providers, organized for a fast SOC review.') }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-sm font-medium text-slate-500">{{ __('Detected Services') }}</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $serviceRows->count() }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ __('Ports with service, protocol or version data.') }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-sm font-medium text-slate-500">{{ __('Technologies') }}</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $technologyRows->count() }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ __('Products, frameworks or technology fingerprints.') }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-sm font-medium text-slate-500">{{ __('Certificates') }}</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $certificateRows->count() }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ __('TLS certificate details discovered on this host.') }}</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div class="text-sm font-medium text-slate-500">{{ __('Vulnerabilities') }}</div>
                                <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $vulnerabilityRows->count() }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ __('CVEs explicitly present in the normalized payload.') }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            <div class="rounded-lg border border-gray-200 p-5">
                                <h5 class="text-base font-semibold text-gray-900">{{ __('Asset Fingerprint') }}</h5>
                                @if($identityCoverageMissing)
                                    <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                                        <div class="font-semibold">{{ __('Coverage note') }}</div>
                                        <div class="mt-1">{{ __('The current successful sources for this host exposed service and certificate data, but did not return ownership or geolocation fields such as organization, ISP, ASN or country/city. This is a source coverage limitation, not necessarily a platform parsing error.') }}</div>
                                    </div>
                                @endif
                                @if($inferredAssetTypeReasons->isNotEmpty())
                                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                        <div class="font-semibold">
                                            {{ __('Inferred Asset Type') }}:
                                            {{ $inferredAssetTypeName ?: __('Not inferred yet') }}
                                            @if(filled($inferredAssetTypeConfidence))
                                                <span class="font-normal">({{ \Illuminate\Support\Str::of($inferredAssetTypeConfidence)->title() }} {{ __('confidence') }})</span>
                                            @endif
                                        </div>
                                        <ul class="mt-2 space-y-1">
                                            @foreach($inferredAssetTypeReasons as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __('Operating System') }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ filled($operatingSystem) ? $operatingSystem : __('Not observed yet') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __('Organization') }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ filled($organization) ? $organization : __('Not observed by the selected enrichment provider') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __('ISP') }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ filled($isp) ? $isp : __('Not observed by the selected enrichment provider') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __('ASN') }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ filled($asn) ? $asn : __('Not observed by the selected enrichment provider') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __('Location') }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ collect([$city, $region, $country])->filter(fn ($value) => filled($value))->implode(', ') ?: __('No geolocation data observed yet') }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">{{ __('Reputation Score') }}</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ filled($reputationScore) ? $reputationScore : __('No reputation score observed yet') }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-5 space-y-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-500">{{ __('Hostnames') }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @forelse($hostnames as $hostname)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $hostname }}</span>
                                            @empty
                                                <span class="text-sm text-gray-500">{{ __('No hostnames found') }}</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-sm font-medium text-gray-500">{{ __('Domains') }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @forelse($domains as $domain)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $domain }}</span>
                                            @empty
                                                <span class="text-sm text-gray-500">
                                                    {{ $hostnames->isNotEmpty() ? __('No separate domain list was returned; only hostnames were observed.') : __('No domains found') }}
                                                </span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-sm font-medium text-gray-500">{{ __('Reputation Tags') }}</div>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @forelse($reputationTags as $tag)
                                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">{{ $tag }}</span>
                                            @empty
                                                <span class="text-sm text-gray-500">{{ __('No reputation tags found') }}</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-5">
                                <h5 class="text-base font-semibold text-gray-900">{{ __('Open Services and Versions') }}</h5>
                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3">{{ __('Port') }}</th>
                                            <th class="px-4 py-3">{{ __('Protocol') }}</th>
                                            <th class="px-4 py-3">{{ __('Service') }}</th>
                                            <th class="px-4 py-3">{{ __('Product') }}</th>
                                            <th class="px-4 py-3">{{ __('Version') }}</th>
                                            <th class="px-4 py-3">{{ __('State') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($serviceRows as $service)
                                            <tr class="border-b align-top">
                                                <td class="px-4 py-3 font-medium text-gray-900">{{ data_get($service, 'port') ?: __('Not observed') }}</td>
                                                <td class="px-4 py-3">{{ data_get($service, 'protocol') ?: __('Not observed') }}</td>
                                                <td class="px-4 py-3">{{ data_get($service, 'service') ?: __('Not observed') }}</td>
                                                <td class="px-4 py-3">{{ data_get($service, 'product') ?: __('Not observed') }}</td>
                                                <td class="px-4 py-3">{{ data_get($service, 'version') ?: __('Not observed') }}</td>
                                                <td class="px-4 py-3">{{ data_get($service, 'state') ?: __('Not observed') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-4 py-3 text-gray-500">{{ __('No service details were normalized from the latest enrichment.') }}</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                            <div class="rounded-lg border border-gray-200 p-5">
                                <h5 class="text-base font-semibold text-gray-900">{{ __('Detected Technologies') }}</h5>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @forelse($technologyRows as $technology)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">{{ $technology }}</span>
                                    @empty
                                        <span class="text-sm text-gray-500">{{ __('No technologies were normalized from the latest enrichment.') }}</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h5 class="text-base font-semibold text-gray-900">{{ __('Observed CPEs') }}</h5>
                                        <p class="mt-1 text-sm text-gray-500">{{ __('The platform derives CPE candidates from the enrichment data so the host can later assign a stable fingerprint to the linked asset.') }}</p>
                                    </div>
                                    @if($primaryObservedCpe)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                            {{ __('Primary Candidate') }}: {{ \Illuminate\Support\Str::of((string) data_get($primaryObservedCpe, 'confidence', ''))->title() }}
                                        </span>
                                    @endif
                                </div>

                                <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="font-medium text-gray-900">{{ __('Suggested CPE') }}</dt>
                                        <dd class="mt-1 text-gray-700 break-all">{{ data_get($primaryObservedCpe, 'cpe', __('Not inferred yet')) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-gray-900">{{ __('Assigned Asset CPE') }}</dt>
                                        <dd class="mt-1 text-gray-700 break-all">{{ $assignedAssetCpe ?: __('No linked asset CPE assigned yet') }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-500">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3">{{ __('CPE') }}</th>
                                            <th class="px-4 py-3">{{ __('Source') }}</th>
                                            <th class="px-4 py-3">{{ __('Confidence') }}</th>
                                            <th class="px-4 py-3">{{ __('Why') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($observedCpeCandidates as $candidate)
                                            <tr class="border-b align-top">
                                                <td class="px-4 py-3 font-medium text-gray-900 break-all">{{ data_get($candidate, 'cpe') }}</td>
                                                <td class="px-4 py-3">{{ \Illuminate\Support\Str::of((string) data_get($candidate, 'source', 'unknown'))->replace('_', ' ')->title() }}</td>
                                                <td class="px-4 py-3">{{ \Illuminate\Support\Str::of((string) data_get($candidate, 'confidence', 'unknown'))->title() }}</td>
                                                <td class="px-4 py-3">
                                                    @php($reasons = collect(data_get($candidate, 'context.reasons', []))->filter()->values())
                                                    @if($reasons->isNotEmpty())
                                                        <ul class="space-y-1 text-xs text-gray-700">
                                                            @foreach($reasons as $reason)
                                                                <li>{{ $reason }}</li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-xs text-gray-500">{{ __('No explicit reasoning stored.') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-3 text-gray-500">{{ __('No CPE candidates could be inferred from the current enrichment yet.') }}</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-5">
                                <h5 class="text-base font-semibold text-gray-900">{{ __('TLS Certificates') }}</h5>
                                <div class="mt-4 space-y-3">
                                    @forelse($certificateRows as $certificate)
                                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                            <div class="text-sm font-semibold text-slate-900">{{ data_get($certificate, 'subject') ?: __('Not observed') }}</div>
                                            <div class="mt-2 text-xs text-slate-700 space-y-1">
                                                <div>{{ __('Issuer') }}: {{ data_get($certificate, 'issuer') ?: __('Not observed') }}</div>
                                                <div>{{ __('Valid From') }}: {{ data_get($certificate, 'valid_from') ?: __('Not observed') }}</div>
                                                <div>{{ __('Valid To') }}: {{ data_get($certificate, 'valid_to') ?: __('Not observed') }}</div>
                                                <div>{{ __('Fingerprint') }}: {{ data_get($certificate, 'fingerprint') ?: __('Not observed') }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-sm text-gray-500">{{ __('No certificates were normalized from the latest enrichment.') }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-5">
                            <h5 class="text-base font-semibold text-gray-900">{{ __('Known Vulnerabilities') }}</h5>
                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('CVE') }}</th>
                                        <th class="px-4 py-3">{{ __('Severity') }}</th>
                                        <th class="px-4 py-3">{{ __('CVSS') }}</th>
                                        <th class="px-4 py-3">{{ __('KEV') }}</th>
                                        <th class="px-4 py-3">{{ __('EPSS') }}</th>
                                        <th class="px-4 py-3">{{ __('CWE') }}</th>
                                        <th class="px-4 py-3">{{ __('Source') }}</th>
                                        <th class="px-4 py-3">{{ __('Description') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($vulnerabilityRows as $vulnerability)
                                        <tr class="border-b align-top">
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ data_get($vulnerability, 'cve') ?: __('Not observed') }}</td>
                                            <td class="px-4 py-3">{{ data_get($vulnerability, 'severity') ?: __('Not observed') }}</td>
                                            <td class="px-4 py-3">{{ data_get($vulnerability, 'cvss') ?: __('Not observed') }}</td>
                                            <td class="px-4 py-3">
                                                @if(data_get($vulnerability, 'cisa_kev'))
                                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700">{{ __('Known Exploited') }}</span>
                                                @else
                                                    {{ __('No') }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div>{{ data_get($vulnerability, 'epss') ?: __('Not observed') }}</div>
                                                @if(data_get($vulnerability, 'epss_percentile'))
                                                    <div class="mt-1 text-xs text-gray-500">{{ __('Percentile') }}: {{ data_get($vulnerability, 'epss_percentile') }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">{{ data_get($vulnerability, 'cwe') ?: __('Not observed') }}</td>
                                            <td class="px-4 py-3">{{ data_get($vulnerability, 'intelligence_source') ?: __('Scanner only') }}</td>
                                            <td class="px-4 py-3">
                                                <div>{{ data_get($vulnerability, 'description') ?: __('No description provided by the scanner') }}</div>
                                                @if(data_get($vulnerability, 'cvss_vector') || collect(data_get($vulnerability, 'references', []))->isNotEmpty())
                                                    <div class="mt-2 space-y-1 text-xs text-gray-500">
                                                        @if(data_get($vulnerability, 'cvss_vector'))
                                                            <div>{{ __('Vector') }}: {{ data_get($vulnerability, 'cvss_vector') }}</div>
                                                        @endif
                                                        @if(data_get($vulnerability, 'last_enriched_at'))
                                                            <div>{{ __('Intelligence Refreshed') }}: {{ data_get($vulnerability, 'last_enriched_at') }}</div>
                                                        @endif
                                                        @if(data_get($vulnerability, 'cisa_exploit_added'))
                                                            <div>{{ __('KEV Added') }}: {{ data_get($vulnerability, 'cisa_exploit_added') }}</div>
                                                        @endif
                                                        @if(data_get($vulnerability, 'cisa_action_due'))
                                                            <div>{{ __('KEV Action Due') }}: {{ data_get($vulnerability, 'cisa_action_due') }}</div>
                                                        @endif
                                                        @if(data_get($vulnerability, 'cisa_required_action') && data_get($vulnerability, 'cisa_required_action') !== 'Not Found')
                                                            <div>{{ __('KEV Required Action') }}: {{ data_get($vulnerability, 'cisa_required_action') }}</div>
                                                        @endif
                                                        @if(data_get($vulnerability, 'epss_date'))
                                                            <div>{{ __('EPSS Date') }}: {{ data_get($vulnerability, 'epss_date') }}</div>
                                                        @endif
                                                        @if(collect(data_get($vulnerability, 'references', []))->isNotEmpty())
                                                            <div>{{ __('References') }}: {{ collect(data_get($vulnerability, 'references', []))->take(3)->implode(', ') }}</div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-3 text-gray-500">{{ __('No vulnerabilities were normalized from the latest enrichment.') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($activeFindings->isNotEmpty())
                        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="text-sm font-medium text-gray-500 mb-3">{{ __('By Source') }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($findingSourceCounts as $source => $count)
                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                            {{ \Illuminate\Support\Str::of($source)->replace('_', ' ')->title() }}: {{ $count }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="text-sm font-medium text-gray-500 mb-3">{{ __('By Severity') }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($findingSeverityCounts as $severity => $count)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
                                            {{ \Illuminate\Support\Str::of($severity)->replace('_', ' ')->title() }}: {{ $count }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <div class="text-sm font-medium text-gray-500 mb-3">{{ __('By Category') }}</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($findingCategoryCounts as $category => $count)
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                                            {{ \Illuminate\Support\Str::of($category)->replace('_', ' ')->title() }}: {{ $count }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($promotedThreats->isNotEmpty())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                            <div class="flex justify-between items-start gap-4">
                                <div>
                                    <div class="text-sm font-medium text-rose-700">{{ __('Promoted Threats') }}</div>
                                    <p class="mt-1 text-sm text-rose-600">{{ __('These findings already matched the scope rules and were converted into asset threats.') }}</p>
                                </div>
                                <div class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-medium text-rose-700">
                                    {{ $promotedThreats->count() }} {{ __('Threats') }}
                                </div>
                            </div>

                            <div class="overflow-x-auto mt-4">
                                <table class="w-full text-sm text-left text-rose-900">
                                    <thead class="text-xs uppercase bg-rose-100 text-rose-700">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Threat') }}</th>
                                        <th class="px-4 py-3">{{ __('Finding') }}</th>
                                        <th class="px-4 py-3">{{ __('Probability') }}</th>
                                        <th class="px-4 py-3">{{ __('Impacts') }}</th>
                                        <th class="px-4 py-3">{{ __('Reason') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($promotedThreats as $threat)
                                        <tr class="border-b border-rose-200 align-top">
                                            <td class="px-4 py-3">
                                                <div class="font-medium">{{ $threat->threat?->name ?? __('Unnamed threat') }}</div>
                                                <div class="text-xs text-rose-700 mt-1">{{ __('Threat ID') }} #{{ $threat->id }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div>{{ data_get($threat->source_context, 'finding_title', __('Unknown finding')) }}</div>
                                                <div class="text-xs text-rose-700 mt-1">{{ \Illuminate\Support\Str::of((string) data_get($threat->source_context, 'finding_kind', ''))->replace('_', ' ')->title() }}</div>
                                            </td>
                                            <td class="px-4 py-3">{{ $threat->probability }}/5</td>
                                            <td class="px-4 py-3">
                                                {{ __('A') }} {{ $threat->availability_impact }}/5,
                                                {{ __('I') }} {{ $threat->integrity_impact }}/5,
                                                {{ __('C') }} {{ $threat->confidentiality_impact }}/5
                                            </td>
                                            <td class="px-4 py-3">{{ data_get($threat->source_context, 'reason', __('No reason stored')) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="rounded-xl border border-gray-200 p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">{{ __('Active Findings') }}</h4>
                                <p class="mt-1 text-sm text-gray-500">{{ __('Detailed findings remain available below, but the highest-signal indicators now appear above for faster review.') }}</p>
                            </div>
                            <div class="text-sm text-gray-500">{{ $activeFindings->count() }} {{ __('active') }}</div>
                        </div>

                        <div class="overflow-x-auto mt-6">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Source') }}</th>
                                    <th class="px-4 py-3">{{ __('Category') }}</th>
                                    <th class="px-4 py-3">{{ __('Kind') }}</th>
                                    <th class="px-4 py-3">{{ __('Title') }}</th>
                                    <th class="px-4 py-3">{{ __('Threat Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Severity') }}</th>
                                    <th class="px-4 py-3">{{ __('Last Detected') }}</th>
                                    <th class="px-4 py-3">{{ __('Evidence') }}</th>
                                    <th class="px-4 py-3">{{ __('Details') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($findingRows as $findingRow)
                                    <tr class="bg-white border-b align-top">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ \Illuminate\Support\Str::of($findingRow['finding']->source)->replace('_', ' ')->title() }}</div>
                                            @if($findingRow['finding']->lastEnrichmentRun)
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ __('Run') }} #{{ $findingRow['finding']->lastEnrichmentRun->id }} / {{ \Illuminate\Support\Str::of($findingRow['finding']->lastEnrichmentRun->provider)->replace('_', ' ')->title() }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                                                {{ $findingRow['category'] }}
                                            </div>
                                            @if(filled($findingRow['template_id']) && $findingRow['template_id'] !== 'Not Found')
                                                <div class="text-xs text-gray-500 mt-2">{{ __('Template') }}: {{ $findingRow['template_id'] }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($findingRow['finding']->kind)->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $findingRow['finding']->title }}</div>
                                            @if($findingRow['finding']->description)
                                                <div class="text-xs text-gray-500 mt-1">{{ $findingRow['finding']->description }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($findingRow['linked_threats']->isNotEmpty())
                                                <div class="space-y-2">
                                                    @foreach($findingRow['linked_threats'] as $linkedThreat)
                                                        <div class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2">
                                                            <div class="text-xs font-medium text-rose-700">{{ __('Promoted to Threat') }}</div>
                                                            <div class="text-sm text-rose-900 mt-1">{{ $linkedThreat->threat?->name ?? __('Unnamed threat') }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                                    {{ __('Observation Only') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">{{ $findingRow['finding']->severity ?: __('Not specified') }}</td>
                                        <td class="px-4 py-3">{{ optional($findingRow['finding']->last_detected_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                        <td class="px-4 py-3">
                                            @if($findingRow['evidence_parts']->isNotEmpty())
                                                <div class="space-y-1">
                                                    @foreach($findingRow['evidence_parts'] as $part)
                                                        <div class="text-xs text-gray-700">{{ $part }}</div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-500">{{ __('No compact evidence') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(!empty($findingRow['finding']->context))
                                                <details class="group">
                                                    <summary class="cursor-pointer text-xs font-medium text-blue-700 hover:text-blue-800">
                                                        {{ __('Show JSON details') }}
                                                    </summary>
                                                    <pre class="mt-3 bg-slate-900 text-slate-100 text-xs rounded-lg p-3 overflow-x-auto">{{ json_encode($findingRow['finding']->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                </details>
                                            @else
                                                {{ __('No additional details') }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-3 text-gray-500">{{ __('No active findings have been derived from the latest enrichment yet.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <details class="rounded-xl border border-gray-200 p-6">
                            <summary class="cursor-pointer list-none">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ __('Enrichment History') }}</h4>
                                    <p class="mt-1 text-sm text-gray-500">{{ __('Older runs stay available, but are collapsed by default to keep the page readable.') }}</p>
                                </div>
                            </summary>

                            <div class="overflow-x-auto mt-6">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Run') }}</th>
                                        <th class="px-4 py-3">{{ __('Provider') }}</th>
                                        <th class="px-4 py-3">{{ __('Status') }}</th>
                                        <th class="px-4 py-3">{{ __('Open Ports') }}</th>
                                        <th class="px-4 py-3">{{ __('Vulnerabilities') }}</th>
                                        <th class="px-4 py-3">{{ __('Finished At') }}</th>
                                        <th class="px-4 py-3">{{ __('Error') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($host->enrichmentRuns as $run)
                                        <tr class="bg-white border-b align-top">
                                            <td class="px-4 py-3">{{ $run->id }}</td>
                                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($run->provider)->replace('_', ' ')->title() }}</td>
                                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($run->status)->replace('_', ' ')->title() }}</td>
                                            <td class="px-4 py-3">{{ implode(', ', $run->open_ports ?? []) ?: __('None') }}</td>
                                            <td class="px-4 py-3">{{ count($run->vulnerabilities ?? []) }}</td>
                                            <td class="px-4 py-3">{{ optional($run->finished_at)->toDateTimeString() ?? __('Running / Pending') }}</td>
                                            <td class="px-4 py-3">{{ $run->error ?: __('None') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-3 text-gray-500">{{ __('No enrichment history recorded yet.') }}</td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </details>

                        <div class="space-y-6">
                            <details class="rounded-xl border border-gray-200 p-6">
                                <summary class="cursor-pointer list-none">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900">{{ __('Normalized Payload') }}</h4>
                                        <p class="mt-1 text-sm text-gray-500">{{ __('The normalized payload is still available for deeper technical validation.') }}</p>
                                    </div>
                                </summary>
                                <pre class="mt-6 bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode($latestNormalizedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>

                            <details class="rounded-xl border border-gray-200 p-6">
                                <summary class="cursor-pointer list-none">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900">{{ __('Raw Payload') }}</h4>
                                        <p class="mt-1 text-sm text-gray-500">{{ __('The original provider output is collapsed by default because it is useful mainly for debugging.') }}</p>
                                    </div>
                                </summary>
                                <pre class="mt-6 bg-slate-900 text-slate-100 text-sm rounded-lg p-4 overflow-x-auto">{{ json_encode($latestRun?->raw_payload ?? $host->raw_payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
