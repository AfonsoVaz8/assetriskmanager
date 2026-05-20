<?php

namespace App\Http\Controllers;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Enums\AttackSurfaceScopeStatus;
use App\Enums\AttackSurfaceScopeType;
use App\Http\Requests\StoreAttackSurfaceScopeRequest;
use App\Http\Requests\UpdateAttackSurfaceScopeRequest;
use App\Jobs\EnrichDiscoveredHost;
use App\Jobs\RunAttackSurfaceDiscovery;
use App\Models\AssetThreat;
use App\Models\AssetType;
use App\Models\AttackSurfaceScope;
use App\Models\DiscoveredHost;
use App\Models\Integration;
use App\Models\User;
use App\Services\AttackSurfaceDiscoveryService;
use App\Services\AssetCpeInferenceService;
use App\Services\AssetExternalExposureService;
use App\Services\AssetTypeInferenceService;
use App\Services\DiscoveredHostThreatSyncService;
use App\Enums\AssetOperationType;
use App\Models\Asset;
use App\Models\AssetLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttackSurfaceScopeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AttackSurfaceScope::class, 'attack_surface_scope');
    }

    public function index(Request $request): Application|Factory|View
    {
        $filter = trim((string) $request->input('filter', ''));

        $query = AttackSurfaceScope::query()
            ->with(['submittedBy', 'approvedBy'])
            ->latest('id');

        if ($filter !== '') {
            $query->where(function ($subQuery) use ($filter) {
                $subQuery->whereRaw(lowerLike('name'), [caseInsensitiveMatch($filter)])
                    ->orWhereRaw(lowerLike('type'), [caseInsensitiveMatch($filter)])
                    ->orWhereRaw(lowerLike('status'), [caseInsensitiveMatch($filter)]);
            });
        }

        return view('attack-surface-scopes.index', [
            'scopes' => $query->paginate(15)->withQueryString(),
            'filter' => $filter,
        ]);
    }

    public function create(): Application|Factory|View
    {
        return view('attack-surface-scopes.create', $this->formData());
    }

    public function store(StoreAttackSurfaceScopeRequest $request): RedirectResponse
    {
        $scope = AttackSurfaceScope::query()->create($this->payloadFromRequest($request) + [
            'submitted_by_user_id' => $request->user()->id,
            'status' => AttackSurfaceScopeStatus::DRAFT,
        ]);

        Log::channel('application')->info(sprintf(
            'Create Attack Surface Scope %d (Type: %s, Name: %s)',
            $scope->id,
            $scope->type->value,
            $scope->name
        ));

        return redirect()->route('attack-surface-scopes.show', $scope)
            ->with('status', __('Attack surface scope created'));
    }

    public function show(AttackSurfaceScope $attackSurfaceScope): Application|Factory|View
    {
        $attackSurfaceScope->load([
            'submittedBy',
            'approvedBy',
            'runs' => fn ($query) => $query->latest('id')->limit(10),
            'discoveredHosts' => fn ($query) => $query->with(['latestEnrichmentRun', 'asset'])->latest('last_seen_at')->limit(50),
        ]);

        return view('attack-surface-scopes.show', [
            'scope' => $attackSurfaceScope,
        ]);
    }

    public function showHost(
        AttackSurfaceScope $attackSurfaceScope,
        DiscoveredHost $discoveredHost,
        AssetTypeInferenceService $assetTypeInferenceService,
        AssetCpeInferenceService $assetCpeInferenceService
    ): Application|Factory|View
    {
        $this->authorize('view', $attackSurfaceScope);

        abort_unless(
            $discoveredHost->attack_surface_scope_id === $attackSurfaceScope->id,
            404
        );

        $discoveredHost->load([
            'scope',
            'run',
            'asset',
            'findings' => fn ($query) => $query
                ->with('lastEnrichmentRun')
                ->where('active', true)
                ->latest('last_detected_at'),
            'enrichmentRuns' => fn ($query) => $query->latest('id')->limit(10),
            'latestEnrichmentRun',
        ]);

        $promotedThreats = $discoveredHost->asset
            ? AssetThreat::query()
                ->with('threat')
                ->where('asset_id', $discoveredHost->asset_id)
                ->where('source', 'attack_surface')
                ->where('auto_generated', true)
                ->where('source_key', 'like', 'discovered_host:'.$discoveredHost->id.':%')
                ->latest('id')
                ->get()
            : collect();

        $scanStages = $this->buildScanStages($attackSurfaceScope, $discoveredHost);
        $technicalProfile = $this->buildTechnicalProfile($discoveredHost);
        $assetTypeInference = $assetTypeInferenceService->infer($discoveredHost);
        $observedCpeCandidates = $assetCpeInferenceService->inferCandidatesFromDiscoveredHost(
            $discoveredHost,
            $technicalProfile
        );

        return view('attack-surface-scopes.hosts.show', [
            'scope' => $attackSurfaceScope,
            'host' => $discoveredHost,
            'promotedThreats' => $promotedThreats,
            'scanStages' => $scanStages,
            'technicalProfile' => $technicalProfile,
            'assetTypeInference' => $assetTypeInference,
            'observedCpeCandidates' => $observedCpeCandidates,
        ]);
    }

    public function edit(AttackSurfaceScope $attackSurfaceScope): Application|Factory|View
    {
        return view('attack-surface-scopes.edit', $this->formData() + [
            'scope' => $attackSurfaceScope,
        ]);
    }

    public function update(UpdateAttackSurfaceScopeRequest $request, AttackSurfaceScope $attackSurfaceScope): RedirectResponse
    {
        $attackSurfaceScope->update($this->payloadFromRequest($request));

        Log::channel('application')->info(sprintf(
            'Update Attack Surface Scope %d (Type: %s, Name: %s)',
            $attackSurfaceScope->id,
            $attackSurfaceScope->type->value,
            $attackSurfaceScope->name
        ));

        return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
            ->with('status', __('Attack surface scope updated'));
    }

    public function destroy(AttackSurfaceScope $attackSurfaceScope): RedirectResponse
    {
        Log::channel('application')->info(sprintf(
            'Delete Attack Surface Scope %d (Type: %s, Name: %s)',
            $attackSurfaceScope->id,
            $attackSurfaceScope->type->value,
            $attackSurfaceScope->name
        ));

        $attackSurfaceScope->delete();

        return redirect()->route('attack-surface-scopes.index')
            ->with('status', __('Attack surface scope deleted'));
    }

    public function approve(AttackSurfaceScope $attackSurfaceScope, Request $request): RedirectResponse
    {
        $this->authorize('update', $attackSurfaceScope);

        $attackSurfaceScope->forceFill([
            'status' => AttackSurfaceScopeStatus::APPROVED,
            'approved_by_user_id' => $request->user()->id,
        ])->save();

        return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
            ->with('status', __('Attack surface scope approved'));
    }

    public function disable(AttackSurfaceScope $attackSurfaceScope): RedirectResponse
    {
        $this->authorize('update', $attackSurfaceScope);

        $attackSurfaceScope->forceFill([
            'status' => AttackSurfaceScopeStatus::DISABLED,
        ])->save();

        return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
            ->with('status', __('Attack surface scope disabled'));
    }

    public function run(
        AttackSurfaceScope $attackSurfaceScope,
        AttackSurfaceDiscoveryService $service
    ): RedirectResponse {
        $this->authorize('update', $attackSurfaceScope);

        try {
            $run = $service->createRun($attackSurfaceScope);
            RunAttackSurfaceDiscovery::dispatch($run->id);
        } catch (Throwable $exception) {
            return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
                ->with('status', __('Discovery could not be started: :message', ['message' => $exception->getMessage()]));
        }

        return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
            ->with('status', __('Attack surface discovery run dispatched'));
    }

    public function enrichActive(AttackSurfaceScope $attackSurfaceScope): RedirectResponse
    {
        $this->authorize('update', $attackSurfaceScope);

        $hostIds = $attackSurfaceScope->discoveredHosts()
            ->where('status', 'active')
            ->pluck('id');

        $count = $this->dispatchEnrichmentJobs($hostIds);

        return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
            ->with('status', trans_choice(':count active host queued for enrichment.|:count active hosts queued for enrichment.', $count, ['count' => $count]));
    }

    public function enrichSelected(AttackSurfaceScope $attackSurfaceScope, Request $request): RedirectResponse
    {
        $this->authorize('update', $attackSurfaceScope);

        $requestedIds = collect($request->input('host_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($requestedIds->isEmpty()) {
            return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
                ->with('status', __('Select at least one active host to enrich.'));
        }

        $hostIds = $attackSurfaceScope->discoveredHosts()
            ->where('status', 'active')
            ->whereIn('id', $requestedIds)
            ->pluck('id');

        $count = $this->dispatchEnrichmentJobs($hostIds);

        return redirect()->route('attack-surface-scopes.show', $attackSurfaceScope)
            ->with('status', trans_choice(':count selected active host queued for enrichment.|:count selected active hosts queued for enrichment.', $count, ['count' => $count]));
    }

    public function enrichHost(AttackSurfaceScope $attackSurfaceScope, DiscoveredHost $discoveredHost): RedirectResponse
    {
        $this->authorize('update', $attackSurfaceScope);

        abort_unless(
            $discoveredHost->attack_surface_scope_id === $attackSurfaceScope->id,
            404
        );

        if ($discoveredHost->status !== \App\Enums\DiscoveredHostStatus::ACTIVE) {
            return redirect()->route('attack-surface-scopes.hosts.show', [$attackSurfaceScope, $discoveredHost])
                ->with('status', __('Only active hosts can be enriched.'));
        }

        $count = $this->dispatchEnrichmentJobs(collect([$discoveredHost->id]));

        return redirect()->route('attack-surface-scopes.hosts.show', [$attackSurfaceScope, $discoveredHost])
            ->with('status', trans_choice(':count host queued for enrichment.|:count hosts queued for enrichment.', $count, ['count' => $count]));
    }

    public function addHostToAssets(
        AttackSurfaceScope $attackSurfaceScope,
        DiscoveredHost $discoveredHost,
        AssetExternalExposureService $assetExternalExposureService,
        AssetTypeInferenceService $assetTypeInferenceService,
        DiscoveredHostThreatSyncService $discoveredHostThreatSyncService
    ): RedirectResponse {
        $this->authorize('update', $attackSurfaceScope);

        abort_unless(
            $discoveredHost->attack_surface_scope_id === $attackSurfaceScope->id,
            404
        );

        if ($discoveredHost->asset) {
            return redirect()->route('attack-surface-scopes.hosts.show', [$attackSurfaceScope, $discoveredHost])
                ->with('status', __('This discovered host is already linked to an asset.'));
        }

        $existingAsset = Asset::query()
            ->where('ip_address', $discoveredHost->ip_address)
            ->when(filled($discoveredHost->fqdn), fn ($query) => $query->orWhere('fqdn', $discoveredHost->fqdn))
            ->first();

        if ($existingAsset) {
            $this->linkHostToAsset($discoveredHost, $existingAsset, $discoveredHostThreatSyncService);
            $assetExternalExposureService->syncFromDiscoveredHost($existingAsset, $discoveredHost);

            return redirect()->route('attack-surface-scopes.hosts.show', [$attackSurfaceScope, $discoveredHost])
                ->with('status', __('The discovered host was linked to the existing asset ":asset".', ['asset' => $existingAsset->name]));
        }

        $inference = $assetTypeInferenceService->infer($discoveredHost);
        $assetType = $inference['asset_type'] ?? AssetType::query()->whereRaw('LOWER(name) = ?', ['other'])->first() ?? AssetType::query()->first();
        $managerId = (int) (data_get($attackSurfaceScope->settings, 'auto_create_manager_id') ?: Auth::id());
        $manager = User::query()->find($managerId) ?? Auth::user();

        if (!$assetType || !$manager) {
            return redirect()->route('attack-surface-scopes.hosts.show', [$attackSurfaceScope, $discoveredHost])
                ->with('status', __('The host could not be added because no valid asset type or manager could be resolved.'));
        }

        $asset = DB::transaction(function () use ($attackSurfaceScope, $discoveredHost, $assetType, $manager, $inference) {
            $asset = Asset::query()->create([
                'name' => $this->discoveredHostAssetName($discoveredHost, $assetType),
                'asset_type_id' => $assetType->id,
                'manager_id' => $manager->id,
                'description' => $this->discoveredHostAssetDescription($attackSurfaceScope, $discoveredHost, $inference),
                'sku' => 'DISC-HOST-'.$discoveredHost->id,
                'manufacturer' => data_get($discoveredHost->normalized_payload, 'organization')
                    ?: data_get($discoveredHost->normalized_payload, 'isp')
                    ?: 'Unknown',
                'location' => 'External Attack Surface',
                'manufacturer_contract_type' => 'NONE',
                'mac_address' => null,
                'fqdn' => $discoveredHost->fqdn,
                'ip_address' => $discoveredHost->ip_address,
                'allowed_open_ports' => $this->normalizeAllowedOpenPorts($discoveredHost->open_ports ?? []),
                'availability_appreciation' => 0,
                'integrity_appreciation' => 0,
                'confidentiality_appreciation' => 0,
                'export' => false,
                'active' => true,
                'version' => $this->discoveredHostVersionHint($discoveredHost),
            ]);

            AssetLog::query()->create([
                'asset_id' => $asset->id,
                'user_id' => Auth::id(),
                'ip' => request()->ip(),
                'operation_type' => AssetOperationType::CREATE,
            ]);

            return $asset;
        });

        $this->linkHostToAsset($discoveredHost, $asset, $discoveredHostThreatSyncService);
        $assetExternalExposureService->syncFromDiscoveredHost($asset, $discoveredHost);

        return redirect()->route('attack-surface-scopes.hosts.show', [$attackSurfaceScope, $discoveredHost])
            ->with('status', __('The discovered host was added to assets as ":asset" with inferred type ":type".', [
                'asset' => $asset->name,
                'type' => $assetType->name,
            ]));
    }

    private function payloadFromRequest(Request $request): array
    {
        $type = $request->input('type');
        $cidr = trim((string) data_get($request->input('scope_definition', []), 'cidr', ''));
        $hostname = trim((string) data_get($request->input('scope_definition', []), 'hostname', ''));
        $domain = trim((string) data_get($request->input('scope_definition', []), 'domain', ''));
        $portList = collect(explode(',', (string) data_get($request->input('settings', []), 'ports', '')))
            ->map(fn (string $port) => (int) trim($port))
            ->filter(fn (int $port) => $port > 0 && $port <= 65535)
            ->unique()
            ->values()
            ->all();

        $autoCreateAssets = $request->boolean('settings.auto_create_assets');

        return [
            'name' => $request->input('name'),
            'type' => $type,
            'justification' => $request->input('justification'),
            'scope_definition' => match ($type) {
                AttackSurfaceScopeType::CIDR_RANGE->value => ['cidr' => $cidr],
                AttackSurfaceScopeType::HOSTNAME_TARGET->value => ['hostname' => $hostname],
                AttackSurfaceScopeType::DOMAIN_EXPANSION->value => ['domain' => $domain],
                default => ['registered_assets' => true],
            },
            'settings' => [
                'ports' => !empty($portList) ? $portList : [80, 443, 22],
                'timeout_seconds' => (float) data_get($request->input('settings', []), 'timeout_seconds', 1.0),
                'auto_create_assets' => $autoCreateAssets,
                'auto_create_asset_type_id' => $autoCreateAssets ? Arr::get($request->input('settings', []), 'auto_create_asset_type_id') : null,
                'auto_create_manager_id' => $autoCreateAssets ? Arr::get($request->input('settings', []), 'auto_create_manager_id') : null,
                'enrichment_integration_id' => Arr::get($request->input('settings', []), 'enrichment_integration_id'),
                'scanners' => $this->parseScanners($request),
                'threat_rules' => $this->parseThreatRules($request),
            ],
        ];
    }

    private function parseScanners(Request $request): array
    {
        $scanners = data_get($request->input('settings', []), 'scanners', []);

        return [
            'nmap' => [
                'enabled' => (bool) data_get($scanners, 'nmap.enabled', false),
                'ssl_cert' => (bool) data_get($scanners, 'nmap.ssl_cert', true),
                'timeout_seconds' => max(15, min(300, (int) data_get($scanners, 'nmap.timeout_seconds', 90))),
            ],
            'nikto' => [
                'enabled' => (bool) data_get($scanners, 'nikto.enabled', false),
                'timeout_seconds' => max(30, min(900, (int) data_get($scanners, 'nikto.timeout_seconds', 240))),
                'max_time_seconds' => max(30, min(900, (int) data_get($scanners, 'nikto.max_time_seconds', 120))),
                'plugins' => trim((string) data_get($scanners, 'nikto.plugins', '')),
                'tuning' => trim((string) data_get($scanners, 'nikto.tuning', '')),
            ],
            'nuclei' => [
                'enabled' => (bool) data_get($scanners, 'nuclei.enabled', false),
                'timeout_seconds' => max(60, min(1800, (int) data_get($scanners, 'nuclei.timeout_seconds', 300))),
                'rate_limit' => max(1, min(50, (int) data_get($scanners, 'nuclei.rate_limit', 10))),
                'include_cves' => (bool) data_get($scanners, 'nuclei.include_cves', false),
                'severities' => trim((string) data_get($scanners, 'nuclei.severities', 'low,medium,high,critical')),
            ],
        ];
    }

    private function parseThreatRules(Request $request): array
    {
        $rules = data_get($request->input('settings', []), 'threat_rules', []);

        return [
            'enabled' => (bool) data_get($rules, 'enabled', false),
            'open_port' => [
                'enabled' => (bool) data_get($rules, 'open_port.enabled', false),
                'only_if_not_allowed' => (bool) data_get($rules, 'open_port.only_if_not_allowed', true),
                'probability' => $this->normalizeRiskValue(data_get($rules, 'open_port.probability', 3)),
                'availability_impact' => $this->normalizeRiskValue(data_get($rules, 'open_port.availability_impact', 2)),
                'integrity_impact' => $this->normalizeRiskValue(data_get($rules, 'open_port.integrity_impact', 3)),
                'confidentiality_impact' => $this->normalizeRiskValue(data_get($rules, 'open_port.confidentiality_impact', 3)),
            ],
            'cve_detected' => [
                'enabled' => (bool) data_get($rules, 'cve_detected.enabled', false),
                'min_cvss' => is_numeric(data_get($rules, 'cve_detected.min_cvss'))
                    ? (float) data_get($rules, 'cve_detected.min_cvss')
                    : null,
                'min_severity' => trim((string) data_get($rules, 'cve_detected.min_severity', '')),
                'probability' => $this->normalizeRiskValue(data_get($rules, 'cve_detected.probability', 4)),
                'availability_impact' => $this->normalizeRiskValue(data_get($rules, 'cve_detected.availability_impact', 3)),
                'integrity_impact' => $this->normalizeRiskValue(data_get($rules, 'cve_detected.integrity_impact', 4)),
                'confidentiality_impact' => $this->normalizeRiskValue(data_get($rules, 'cve_detected.confidentiality_impact', 3)),
            ],
            'web_issue' => [
                'enabled' => (bool) data_get($rules, 'web_issue.enabled', false),
                'min_severity' => trim((string) data_get($rules, 'web_issue.min_severity', 'medium')),
                'probability' => $this->normalizeRiskValue(data_get($rules, 'web_issue.probability', 3)),
                'availability_impact' => $this->normalizeRiskValue(data_get($rules, 'web_issue.availability_impact', 2)),
                'integrity_impact' => $this->normalizeRiskValue(data_get($rules, 'web_issue.integrity_impact', 3)),
                'confidentiality_impact' => $this->normalizeRiskValue(data_get($rules, 'web_issue.confidentiality_impact', 3)),
            ],
        ];
    }

    private function normalizeRiskValue(mixed $value): int
    {
        $value = (int) $value;

        return max(1, min(5, $value));
    }

    private function dispatchEnrichmentJobs(Collection $hostIds): int
    {
        $count = 0;

        foreach ($hostIds as $hostId) {
            EnrichDiscoveredHost::dispatch((int) $hostId);
            $count++;
        }

        return $count;
    }

    private function buildScanStages(AttackSurfaceScope $scope, DiscoveredHost $host): Collection
    {
        $runsByProvider = $host->enrichmentRuns
            ->groupBy('provider')
            ->map(fn (Collection $runs) => $runs->first());

        $stages = collect();
        $integrationId = data_get($scope->settings, 'enrichment_integration_id');
        $selectedIntegration = $integrationId ? Integration::query()->find($integrationId) : null;

        if ($selectedIntegration) {
            $provider = (string) $selectedIntegration->provider;
            $run = $runsByProvider->get($provider);

            $stages->push($this->formatStage(
                key: $provider,
                label: $selectedIntegration->name,
                purpose: __('External IP intelligence provider used to enrich network, ownership, geo, reputation and service data when available.'),
                run: $run,
            ));
        } else {
            $nonScannerRun = $host->enrichmentRuns
                ->first(fn ($run) => !in_array($run->provider, ['nmap', 'nikto', 'nuclei'], true));

            if ($nonScannerRun) {
                $stages->push($this->formatStage(
                    key: $nonScannerRun->provider,
                    label: \Illuminate\Support\Str::of($nonScannerRun->provider)->replace('_', ' ')->title(),
                    purpose: __('External IP intelligence provider used to enrich network, ownership, geo, reputation and service data when available.'),
                    run: $nonScannerRun,
                ));
            }
        }

        foreach ([
            'nmap' => __('Discovers open services, versions, banners, certificates and operating system hints.'),
            'nikto' => __('Checks web servers for exposed files, weak hardening and common web issues.'),
            'nuclei' => __('Runs curated web, SSL and exposure templates to detect known patterns and CVE signals.'),
        ] as $provider => $purpose) {
            if ((bool) data_get($scope->settings, 'scanners.'.$provider.'.enabled', false) || $runsByProvider->has($provider)) {
                $stages->push($this->formatStage(
                    key: $provider,
                    label: \Illuminate\Support\Str::of($provider)->replace('_', ' ')->title(),
                    purpose: $purpose,
                    run: $runsByProvider->get($provider),
                ));
            }
        }

        return $stages->values();
    }

    private function formatStage(string $key, string $label, string $purpose, mixed $run): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'purpose' => $purpose,
            'status' => $run?->status ?? 'not_run',
            'status_label' => $run
                ? \Illuminate\Support\Str::of((string) $run->status)->replace('_', ' ')->title()->toString()
                : __('Not Run Yet'),
            'run_id' => $run?->id,
            'synced_at' => $run?->synced_at,
            'finished_at' => $run?->finished_at,
            'open_ports' => $run?->open_ports ?? [],
            'vulnerabilities' => $run?->vulnerabilities ?? [],
            'error' => $run?->error,
        ];
    }

    private function buildTechnicalProfile(DiscoveredHost $host): array
    {
        $successfulRuns = $host->enrichmentRuns
            ->where('status', 'synced')
            ->groupBy('provider')
            ->map(fn (Collection $runs) => $runs->first());

        $identityRuns = $successfulRuns->filter(fn ($run, $provider) => in_array($provider, [
            IntegrationProvider::SHODAN->value,
            IntegrationProvider::GENERIC_IP_INTELLIGENCE->value,
        ], true));

        $nmapRun = $successfulRuns->get('nmap');

        $firstIdentityValue = function (array $keys) use ($identityRuns) {
            foreach ($identityRuns as $run) {
                foreach ($keys as $key) {
                    $value = data_get($run->normalized_payload, $key);

                    if ($this->isMeaningfulProfileValue($value)) {
                        return $value;
                    }
                }
            }

            return null;
        };

        $allRuns = $successfulRuns->values();

        return [
            'hostnames' => $this->mergeUniqueStringList($allRuns, 'hostnames'),
            'domains' => $this->mergeUniqueStringList($allRuns, 'domains'),
            'asn' => $firstIdentityValue(['asn']),
            'isp' => $firstIdentityValue(['isp']),
            'organization' => $firstIdentityValue(['organization']),
            'country' => $firstIdentityValue(['country']),
            'city' => $firstIdentityValue(['city']),
            'region' => $firstIdentityValue(['region']),
            'latitude' => $firstIdentityValue(['latitude']),
            'longitude' => $firstIdentityValue(['longitude']),
            'network' => $firstIdentityValue(['network']),
            'operating_system' => $this->firstMeaningfulValue([
                data_get($nmapRun?->normalized_payload, 'operating_system'),
                $firstIdentityValue(['operating_system']),
            ]),
            'services' => $this->mergeServices($allRuns),
            'technologies' => $this->mergeUniqueStringList($allRuns, 'technologies'),
            'certificates' => $this->mergeStructuredList($allRuns, 'certificates'),
            'vulnerabilities' => $this->mergeVulnerabilities($allRuns),
            'reputation' => [
                'score' => $firstIdentityValue(['reputation.score']),
                'tags' => $this->mergeUniqueStringList($identityRuns->values(), 'reputation.tags'),
            ],
        ];
    }

    private function mergeUniqueStringList(Collection $runs, string $path): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, $path, []))->all())
            ->filter(fn ($value) => $this->isMeaningfulProfileValue($value))
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();
    }

    private function mergeServices(Collection $runs): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, 'services', []))->all())
            ->filter(fn ($service) => is_array($service))
            ->filter(function (array $service) {
                return $this->isMeaningfulProfileValue(data_get($service, 'port'))
                    || $this->isMeaningfulProfileValue(data_get($service, 'service'))
                    || $this->isMeaningfulProfileValue(data_get($service, 'product'))
                    || $this->isMeaningfulProfileValue(data_get($service, 'version'));
            })
            ->map(function (array $service) {
                return [
                    'port' => $this->meaningfulStringOrNull(data_get($service, 'port')),
                    'protocol' => $this->meaningfulStringOrNull(data_get($service, 'protocol')),
                    'service' => $this->meaningfulStringOrNull(data_get($service, 'service')),
                    'state' => $this->meaningfulStringOrNull(data_get($service, 'state')),
                    'product' => $this->meaningfulStringOrNull(data_get($service, 'product')),
                    'version' => $this->meaningfulStringOrNull(data_get($service, 'version')),
                    'banner' => $this->meaningfulStringOrNull(data_get($service, 'banner')),
                ];
            })
            ->unique(fn (array $service) => implode('|', [
                $service['port'] ?? '',
                $service['protocol'] ?? '',
                $service['service'] ?? '',
                $service['product'] ?? '',
                $service['version'] ?? '',
            ]))
            ->sortBy(fn (array $service) => (int) ($service['port'] ?? 0))
            ->values()
            ->all();
    }

    private function mergeStructuredList(Collection $runs, string $path): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, $path, []))->all())
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                return collect($item)
                    ->map(fn ($value) => $this->meaningfulStringOrNull($value))
                    ->filter(fn ($value) => $value !== null)
                    ->all();
            })
            ->filter(fn (array $item) => $item !== [])
            ->unique(fn (array $item) => json_encode($item))
            ->values()
            ->all();
    }

    private function mergeVulnerabilities(Collection $runs): array
    {
        return $runs
            ->flatMap(fn ($run) => collect(data_get($run->normalized_payload, 'vulnerabilities', []))->all())
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                return [
                    'cve' => $this->meaningfulStringOrNull(data_get($item, 'cve')),
                    'severity' => $this->meaningfulStringOrNull(data_get($item, 'severity')),
                    'cvss' => $this->meaningfulStringOrNull(data_get($item, 'cvss')),
                    'description' => $this->meaningfulStringOrNull(data_get($item, 'description')),
                    'cwe' => $this->meaningfulStringOrNull(data_get($item, 'cwe')),
                    'cvss_vector' => $this->meaningfulStringOrNull(data_get($item, 'cvss_vector')),
                    'cisa_kev' => (bool) data_get($item, 'cisa_kev', false),
                    'cisa_exploit_added' => $this->meaningfulStringOrNull(data_get($item, 'cisa_exploit_added')),
                    'cisa_action_due' => $this->meaningfulStringOrNull(data_get($item, 'cisa_action_due')),
                    'cisa_required_action' => $this->meaningfulStringOrNull(data_get($item, 'cisa_required_action')),
                    'cisa_vulnerability_name' => $this->meaningfulStringOrNull(data_get($item, 'cisa_vulnerability_name')),
                    'epss' => $this->meaningfulStringOrNull(data_get($item, 'epss')),
                    'epss_percentile' => $this->meaningfulStringOrNull(data_get($item, 'epss_percentile')),
                    'epss_date' => $this->meaningfulStringOrNull(data_get($item, 'epss_date')),
                    'intelligence_source' => $this->meaningfulStringOrNull(data_get($item, 'intelligence_source')),
                    'last_enriched_at' => $this->meaningfulStringOrNull(data_get($item, 'last_enriched_at')),
                    'references' => collect(data_get($item, 'references', []))
                        ->filter(fn ($value) => $this->isMeaningfulProfileValue($value))
                        ->map(fn ($value) => trim((string) $value))
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $item) => $item['cve'] !== null)
            ->unique(fn (array $item) => $item['cve'])
            ->values()
            ->all();
    }

    private function firstMeaningfulValue(array $values): mixed
    {
        foreach ($values as $value) {
            if ($this->isMeaningfulProfileValue($value)) {
                return $value;
            }
        }

        return null;
    }

    private function meaningfulStringOrNull(mixed $value): ?string
    {
        return $this->isMeaningfulProfileValue($value) ? trim((string) $value) : null;
    }

    private function isMeaningfulProfileValue(mixed $value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' && $normalized !== 'Not Found';
    }

    private function formData(): array
    {
        return [
            'types' => AttackSurfaceScopeType::cases(),
            'assetTypes' => AssetType::query()->orderBy('name')->get(['id', 'name']),
            'managers' => User::query()->orderBy('name')->get(['id', 'name']),
            'enrichmentIntegrations' => Integration::query()
                ->active()
                ->where('provider', '!=', IntegrationProvider::MICROSOFT_GRAPH->value)
                ->orderBy('name')
                ->get(['id', 'name', 'provider']),
        ];
    }

    private function linkHostToAsset(
        DiscoveredHost $discoveredHost,
        Asset $asset,
        DiscoveredHostThreatSyncService $discoveredHostThreatSyncService
    ): void {
        $discoveredHost->forceFill([
            'asset_id' => $asset->id,
        ])->save();

        $discoveredHost->enrichmentRuns()->update([
            'asset_id' => $asset->id,
        ]);

        $discoveredHost->loadMissing(['asset', 'scope', 'findings']);
        $discoveredHostThreatSyncService->sync($discoveredHost);
    }

    private function discoveredHostAssetName(DiscoveredHost $discoveredHost, AssetType $assetType): string
    {
        if (filled($discoveredHost->fqdn)) {
            return $discoveredHost->fqdn;
        }

        return sprintf('%s %s', $assetType->name, $discoveredHost->ip_address);
    }

    private function discoveredHostAssetDescription(
        AttackSurfaceScope $attackSurfaceScope,
        DiscoveredHost $discoveredHost,
        array $inference
    ): string {
        $reasonText = collect($inference['reasons'] ?? [])
            ->filter(fn ($reason) => filled($reason))
            ->implode(' ');

        return trim(sprintf(
            'Created manually from attack surface discovered host %s in scope "%s". Inferred asset type: %s (%s confidence). %s',
            $discoveredHost->ip_address,
            $attackSurfaceScope->name,
            $inference['asset_type_name'] ?? 'Other',
            $inference['confidence'] ?? 'low',
            $reasonText
        ));
    }

    private function normalizeAllowedOpenPorts(array $ports): array
    {
        return collect($ports)
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => $port > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function discoveredHostVersionHint(DiscoveredHost $discoveredHost): ?string
    {
        return collect(data_get($discoveredHost->normalized_payload, 'services', []))
            ->filter(fn ($service) => is_array($service) && filled(data_get($service, 'version')))
            ->map(fn ($service) => trim((string) data_get($service, 'version')))
            ->filter()
            ->first();
    }
}
