<?php

namespace App\Http\Controllers;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Http\Requests\StoreIntegrationRequest;
use App\Http\Requests\UpdateIntegrationRequest;
use App\Jobs\SyncAssetFromShodan;
use App\Jobs\ThreatMonitoring\SyncThreatIntegration;
use App\Models\AssetShodanReport;
use App\Models\Department;
use App\Models\Integration;
use App\Services\ShodanIntegrationResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IntegrationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Integration::class, 'integration');
    }

    public function index(Request $request): Application|Factory|View
    {
        $filter = $request->input('filter', '');

        $integrations = Integration::query()
            ->when($filter, function ($query, $filter) {
                $query->whereRaw(lowerLike('name'), [caseInsensitiveMatch($filter)])
                    ->orWhereRaw(lowerLike('provider'), [caseInsensitiveMatch($filter)])
                    ->orWhereRaw(lowerLike('status'), [caseInsensitiveMatch($filter)]);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('integrations.index', [
            'integrations' => $integrations,
            'filter' => $filter,
        ]);
    }

    public function create(): Application|Factory|View
    {
        return view('integrations.create', [
            'providers' => IntegrationProvider::cases(),
        ]);
    }

    public function store(StoreIntegrationRequest $request): RedirectResponse
    {
        $provider = $request->input('provider');

        $integration = Integration::query()->create(
            $this->payloadFromRequest($request) + ['sync_state' => $this->initialSyncState($provider)]
        );

        Log::channel('application')->info(sprintf(
            'Create Integration %d (Provider: %s, Name: %s)',
            $integration->id,
            $integration->provider,
            $integration->name
        ));

        return redirect()->route('integrations.index')->with('status', __('Integration Created'));
    }

    public function show(Integration $integration): Application|Factory|View
    {
        $recentAssetReports = collect();

        if ($integration->provider === IntegrationProvider::MICROSOFT_GRAPH->value) {
            $integration->load([
                'threatEvents' => fn ($query) => $query->latest('occurred_at')->limit(10),
                'incidents' => fn ($query) => $query->latest('last_seen_at')->limit(10),
            ]);
        } elseif ($integration->provider === IntegrationProvider::SHODAN->value) {
            $assetIds = app(ShodanIntegrationResolver::class)
                ->eligibleAssetsQuery($integration)
                ->pluck('id');

            $recentAssetReports = AssetShodanReport::query()
                ->whereIn('asset_id', $assetIds)
                ->with('asset:id,name')
                ->latest('synced_at')
                ->limit(10)
                ->get();
        }

        return view('integrations.show', [
            'integration' => $integration,
            'recentAssetReports' => $recentAssetReports,
        ]);
    }

    public function edit(Integration $integration): Application|Factory|View
    {
        return view('integrations.edit', [
            'integration' => $integration,
            'providers' => IntegrationProvider::cases(),
        ]);
    }

    public function update(UpdateIntegrationRequest $request, Integration $integration): RedirectResponse
    {
        $payload = $this->payloadFromRequest($request, $integration);

        if ($this->credentialsChanged($integration, $payload) || $integration->provider !== $payload['provider']) {
            $payload['sync_state'] = $this->initialSyncState($payload['provider']);
            $payload['last_synced_at'] = null;
            $payload['last_error'] = null;
            $payload['last_error_at'] = null;
        }

        $integration->forceFill($payload);

        $rawPayload = collect(array_keys($payload))
            ->mapWithKeys(fn (string $key): array => [$key => $integration->getAttributes()[$key] ?? null])
            ->all();

        $integration->newQuery()->whereKey($integration->getKey())->update($rawPayload);
        $integration->refresh();

        Log::channel('application')->info(sprintf(
            'Update Integration %d (Provider: %s, Name: %s)',
            $integration->id,
            $integration->provider,
            $integration->name
        ));

        return redirect()->route('integrations.index')->with('status', __('Integration Updated'));
    }

    public function destroy(Integration $integration): RedirectResponse
    {
        Log::channel('application')->info(sprintf(
            'Delete Integration %d (Provider: %s, Name: %s)',
            $integration->id,
            $integration->provider,
            $integration->name
        ));

        $integration->delete();

        return redirect()->route('integrations.index')->with('status', __('Integration Deleted'));
    }

    public function sync(Integration $integration): RedirectResponse
    {
        $this->authorize('update', $integration);

        if ($integration->provider === IntegrationProvider::SHODAN->value) {
            $assets = app(ShodanIntegrationResolver::class)
                ->eligibleAssetsQuery($integration)
                ->get(['id']);

            $integration->forceFill([
                'sync_state' => $this->buildShodanSyncState(
                    $integration,
                    now()->toIso8601String(),
                    $assets->count()
                ),
                'last_error' => $assets->isEmpty()
                    ? __('No assets with an IP address are currently eligible for this Shodan integration.')
                    : null,
                'last_error_at' => $assets->isEmpty() ? now() : null,
            ])->save();

            if ($assets->isEmpty()) {
                return redirect()
                    ->route('integrations.show', $integration)
                    ->with('status', __('No eligible assets were found for this Shodan integration.'));
            }

            foreach ($assets as $asset) {
                SyncAssetFromShodan::dispatch($asset->id);
            }

            return redirect()->route('integrations.show', $integration)->with('status', __('Integration sync dispatched'));
        }

        if ($integration->provider === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value) {
            return redirect()->route('integrations.show', $integration)
                ->with('status', __('This integration is used by attack surface enrichment and does not have a direct sync action.'));
        }

        SyncThreatIntegration::dispatch($integration->id);

        return redirect()->route('integrations.show', $integration)->with('status', __('Integration sync dispatched'));
    }

    private function payloadFromRequest(Request $request, ?Integration $integration = null): array
    {
        $provider = $request->input('provider');
        $credentials = $request->input('credentials', []);
        $existingCredentials = $integration?->safeCredentials() ?? [];

        if ($provider === IntegrationProvider::MICROSOFT_GRAPH->value && $integration && blank($credentials['client_secret'] ?? null)) {
            $credentials['client_secret'] = data_get($existingCredentials, 'client_secret');
        }

        if ($provider === IntegrationProvider::SHODAN->value && $integration && blank($credentials['api_key'] ?? null)) {
            $credentials['api_key'] = data_get($existingCredentials, 'api_key');
        }

        if ($provider === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value && $integration && blank($credentials['api_key'] ?? null)) {
            $credentials['api_key'] = data_get($existingCredentials, 'api_key');
        }

        return [
            'tenant_type' => $this->tenantTypeForRequest($request),
            'tenant_id' => $this->tenantIdForRequest($request),
            'name' => $request->input('name'),
            'provider' => $provider,
            'status' => $request->input('status'),
            'credentials' => $this->credentialsPayload($provider, $credentials),
            'settings' => $this->settingsPayload($provider, $request),
        ];
    }

    private function csvToArray(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function credentialsChanged(Integration $integration, array $payload): bool
    {
        $existingCredentials = $integration->safeCredentials() ?? [];

        return $existingCredentials !== ($payload['credentials'] ?? []);
    }

    private function initialSyncState(?string $provider): array
    {
        if ($provider === IntegrationProvider::SHODAN->value) {
            return [
                'last_dispatched_at' => null,
                'last_job_dispatch_count' => 0,
                'last_report_synced_at' => null,
                'last_report_status' => null,
                'last_report_error' => null,
            ];
        }

        if ($provider === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value) {
            return [
                'last_dispatched_at' => null,
                'last_job_dispatch_count' => 0,
                'last_report_synced_at' => null,
                'last_report_status' => null,
                'last_report_error' => null,
            ];
        }

        $cursor = CarbonImmutable::now()->subDay()->toIso8601String();

        return [
            'sign_ins_last_seen_at' => $cursor,
            'risk_detections_last_seen_at' => $cursor,
        ];
    }

    private function tenantTypeForRequest(Request $request): ?string
    {
        if ($request->input('provider') === IntegrationProvider::SHODAN->value) {
            return null;
        }

        if ($request->input('provider') === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value) {
            return null;
        }

        $departmentId = $request->user()?->department_id;

        if ($departmentId) {
            return Department::class;
        }

        return null;
    }

    private function tenantIdForRequest(Request $request): ?int
    {
        if ($request->input('provider') === IntegrationProvider::SHODAN->value) {
            return null;
        }

        if ($request->input('provider') === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value) {
            return null;
        }

        return $request->user()?->department_id;
    }

    private function buildShodanSyncState(Integration $integration, ?string $dispatchedAt, int $jobCount): array
    {
        $existing = $integration->sync_state ?? [];

        return [
            'last_dispatched_at' => $dispatchedAt,
            'last_job_dispatch_count' => $jobCount,
            'last_report_synced_at' => data_get($existing, 'last_report_synced_at'),
            'last_report_status' => data_get($existing, 'last_report_status'),
            'last_report_error' => data_get($existing, 'last_report_error'),
        ];
    }

    private function credentialsPayload(?string $provider, array $credentials): array
    {
        if ($provider === IntegrationProvider::SHODAN->value) {
            return [
                'api_key' => $credentials['api_key'] ?? null,
                'base_url' => $credentials['base_url'] ?? null,
            ];
        }

        if ($provider === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value) {
            return [
                'base_url' => $credentials['base_url'] ?? null,
                'api_key' => $credentials['api_key'] ?? null,
                'auth_mode' => $credentials['auth_mode'] ?? 'none',
                'auth_parameter_name' => $credentials['auth_parameter_name'] ?? 'X-API-Key',
                'ip_parameter_name' => $credentials['ip_parameter_name'] ?? 'ip',
                'response_root_path' => $credentials['response_root_path'] ?? null,
                'source_label' => $credentials['source_label'] ?? null,
            ];
        }

        return [
            'tenant_id' => $credentials['tenant_id'] ?? null,
            'client_id' => $credentials['client_id'] ?? null,
            'client_secret' => $credentials['client_secret'] ?? null,
        ];
    }

    private function settingsPayload(?string $provider, Request $request): array
    {
        if ($provider === IntegrationProvider::SHODAN->value) {
            return [];
        }

        if ($provider === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value) {
            return [];
        }

        return [
            'trusted_countries' => $this->csvToArray($request->input('settings.trusted_countries')),
            'trusted_networks' => $this->csvToArray($request->input('settings.trusted_networks')),
            'detect_external_signins' => $request->boolean('settings.detect_external_signins', true),
            'detect_unusual_countries' => $request->boolean('settings.detect_unusual_countries', true),
            'notify_high_severity' => $request->boolean('settings.notify_high_severity', true),
        ];
    }
}
