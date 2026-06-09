<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function __invoke(Request $request)
    {
        $tasks = array();
        /* @var $user User */
        $user = Auth::user();
        $incidentSummary = [];
        $recentIncidents = collect();
        $showSecurityDashboard = in_array($user->role, [
            UserRole::SECURITY_OFFICER,
            UserRole::DATA_PROTECTION_OFFICER,
            UserRole::ADMINISTRATOR,
        ], true);
        $grafanaDashboards = [];
        $pendingM365Accounts = collect();
        $pendingAssets = collect();

        if ($showSecurityDashboard && config('grafana.enabled')) {
            $baseUrl = rtrim((string) config('grafana.base_url'), '/');
            $grafanaDashboards = collect(config('grafana.dashboards', []))
                ->map(function (array $dashboard, string $key) use ($baseUrl) {
                    $uid = (string) ($dashboard['uid'] ?? '');
                    $slug = Str::slug((string) ($dashboard['slug'] ?? $dashboard['title'] ?? $key));
                    $timeRange = (string) ($dashboard['time_range'] ?? 'now-7d');
                    $panelDefinitions = collect($dashboard['panels'] ?? [])
                        ->map(function (array $panel) use ($baseUrl, $uid, $slug, $timeRange) {
                            $panelId = (int) ($panel['id'] ?? 0);
                            $size = (string) ($panel['size'] ?? 'chart');

                            if ($panelId <= 0 || $uid === '' || $slug === '') {
                                return null;
                            }

                            return [
                                'id' => $panelId,
                                'title' => (string) ($panel['title'] ?? "Panel {$panelId}"),
                                'size' => $size,
                                'embed_url' => "{$baseUrl}/d-solo/{$uid}/{$slug}?orgId=1&panelId={$panelId}&viewPanel={$panelId}&theme=light&kiosk=1&from={$timeRange}&to=now&refresh=5m",
                            ];
                        })
                        ->filter()
                        ->values()
                        ->all();

                    return [
                        'key' => $key,
                        'uid' => $uid,
                        'title' => (string) ($dashboard['title'] ?? $key),
                        'description' => (string) ($dashboard['description'] ?? ''),
                        'panels' => $panelDefinitions,
                    ];
                })
                ->filter(fn (array $dashboard) => !empty($dashboard['panels']))
                ->values()
                ->all();

            $pendingM365Accounts = $this->buildPendingM365AccountsQuery()
                ->limit(8)
                ->get()
                ->map(fn ($account) => $this->decoratePendingM365Account($account));

            $pendingAssets = $this->buildPendingAssetsQuery()
                ->limit(8)
                ->get()
                ->map(fn ($asset) => $this->decoratePendingAsset($asset));
        }
        /* @var $asset Asset */
        foreach ($user->assets()->get() as $asset) {
            //Check for non-existent asset valuation
            if ($asset->totalAppreciation() == 0) {
                $tasks[] = array(
                    "asset" => $asset,
                    "message" => __("The asset :name (:id) isn't valued yet. Please set its valuation.",
                        ["name" => $asset->name, "id" => $asset->id]),
                    "tab" => "details-tab"
                );
            }
            //Check for active asset in which the remaining risk isn't accepted
            elseif (!$asset->remainingRiskAccepted && $asset->active) {
                //Check if asset has threats
                if (!$asset->threats()->exists()) {
                    $tasks[] = array(
                        "asset" => $asset,
                        "message" => __("The asset :name (:id) has no threats. Please add some.",
                            ["name" => $asset->name, "id" => $asset->id]),
                        "tab" => "threats-controls-tab"
                    );
                }
                else {
                    foreach ($asset->threats()->get() as $threat) {
                        //Check if any threat of the asset lacks controls
                        if (!$threat->controls()->exists()) {
                            $tasks[] = array(
                                "asset" => $asset,
                                "message" => __("The threat :threat_name associated with asset :name (:id) has no controls. Please add some.",
                                    ["name" => $asset->name, "id" => $asset->id, "threat_name" => $threat->threat->name]),
                                "tab" => "threats-controls-tab"
                            );
                        }
                        else {
                            //Check if a threat that has controls doesn't have its residual/remaining risk accepted after applying them
                            if (!$threat->residual_risk_accepted) {
                                $tasks[] = array(
                                    "asset" => $asset,
                                    "message" => __("The remaining remaining risk associated with threat :threat_name associated with asset :name (:id) isn't accepted. Please accept it.",
                                        ["name" => $asset->name, "id" => $asset->id, "threat_name" => $threat->threat->name]),
                                    "tab" => "risk-summary-tab"
                                );
                            }
                        }
                    }
                }
            }
        }
        //In case the current user is a security officer, pass all the assets that have controls to validatel
        if ($user->role == UserRole::SECURITY_OFFICER) {
            $assetsWithControlsToValidate = Asset::all()->filter(function ($asset) {
                return $asset->hasUnvalidatedControls() && !$asset->remainingRiskAccepted && $asset->active;
            });

            $incidentQuery = Incident::query()->with('integration');

            $incidentSummary = [
                'open' => (clone $incidentQuery)->where('status', 'open')->count(),
                'in_progress' => (clone $incidentQuery)->where('status', 'in_progress')->count(),
                'resolved' => (clone $incidentQuery)->where('status', 'resolved')->count(),
                'dismissed' => (clone $incidentQuery)->where('status', 'dismissed')->count(),
                'high' => (clone $incidentQuery)->where('severity', 'high')->count(),
                'medium' => (clone $incidentQuery)->where('severity', 'medium')->count(),
            ];

            $recentIncidents = Incident::query()
                ->with('integration')
                ->select([
                    'id',
                    'integration_id',
                    'title',
                    'status',
                    'severity',
                    'event_count',
                    'affected_principal',
                    'affected_principal_display',
                    'last_seen_at',
                ])
                ->latest('last_seen_at')
                ->limit(12)
                ->get();
        }
        else {
            $assetsWithControlsToValidate = array();
        }

        return view('dashboard', [
            "assetsWithControlsToValidate" => $assetsWithControlsToValidate,
            "tasks" => $tasks,
            "incidentSummary" => $incidentSummary,
            "recentIncidents" => $recentIncidents,
            "showSecurityDashboard" => $showSecurityDashboard,
            "grafanaDashboards" => $grafanaDashboards,
            "pendingM365Accounts" => $pendingM365Accounts,
            "pendingAssets" => $pendingAssets,
        ]);
    }

    public function pendingM365Accounts(Request $request): Application|Factory|View|RedirectResponse
    {
        if (!$this->canViewSecurityDashboards()) {
            return redirect()->route('dashboard');
        }

        $accounts = $this->buildPendingM365AccountsQuery()
            ->paginate(20)
            ->through(fn ($account) => $this->decoratePendingM365Account($account))
            ->withQueryString();

        return view('dashboard.pending-m365-accounts', [
            'accounts' => $accounts,
        ]);
    }

    public function pendingAssets(Request $request): Application|Factory|View|RedirectResponse
    {
        if (!$this->canViewSecurityDashboards()) {
            return redirect()->route('dashboard');
        }

        $assets = $this->buildPendingAssetsQuery()
            ->paginate(20)
            ->through(fn ($asset) => $this->decoratePendingAsset($asset))
            ->withQueryString();

        return view('dashboard.pending-assets', [
            'assets' => $assets,
        ]);
    }

    private function canViewSecurityDashboards(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return in_array($user->role, [
            UserRole::SECURITY_OFFICER,
            UserRole::DATA_PROTECTION_OFFICER,
            UserRole::ADMINISTRATOR,
        ], true);
    }

    private function buildPendingM365AccountsQuery()
    {
        return DB::table('vw_grafana_m365_account_risk')
            ->select([
                'principal',
                'priority_score',
                'priority_reason',
                'incident_count',
                'risky_event_count',
                'failure_event_count',
                'max_score',
                'last_seen_at',
            ])
            ->where('priority_score', '>', 0)
            ->orderByDesc('priority_score')
            ->orderByDesc('incident_count')
            ->orderByDesc('max_score');
    }

    private function buildPendingAssetsQuery()
    {
        return DB::table('vw_grafana_asset_risk')
            ->select([
                'asset_id',
                'asset_name',
                'asset_type',
                'primary_identifier',
                'priority_score',
                'priority_reason',
                'high_risk_threat_count',
                'kev_cve_findings',
                'active_cve_findings',
                'active_external_hosts',
                'max_absolute_risk',
                'max_residual_risk',
            ])
            ->where('priority_score', '>', 0)
            ->orderByDesc('priority_score')
            ->orderByDesc('max_absolute_risk');
    }

    private function decoratePendingM365Account(object $account): object
    {
        $query = ['q' => $account->principal];
        $account->primary_href = (int) $account->incident_count > 0
            ? route('incidents.index', $query)
            : route('threat-events.index', $query);
        $account->primary_label = (int) $account->incident_count > 0
            ? __('Open alerts')
            : __('Investigate events');
        $account->events_href = route('threat-events.index', $query);

        return $account;
    }

    private function decoratePendingAsset(object $asset): object
    {
        $asset->primary_href = route('assets.show', $asset->asset_id);
        $asset->external_href = (int) $asset->active_external_hosts > 0
            ? route('assets.discovered-host-details', $asset->asset_id)
            : null;

        return $asset;
    }
}
