<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100">
<div class="min-h-screen">
    @livewire('navigation-menu')

    <header class="bg-white shadow">
        <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ __('Integration Details') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ $integration->name }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('integrations.index') }}"
                       class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('Back') }}
                    </a>
                    <a href="{{ route('integrations.edit', $integration) }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        {{ __('Manage') }}
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

        </div>
    </header>

    <main class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">{{ $integration->name }}</h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ \Illuminate\Support\Str::of($integration->provider)->replace('_', ' ')->title() }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('integrations.sync', $integration) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
                                    {{ __('Sync Now') }}
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="grid gap-6 px-6 py-5 md:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Status') }}</p>
                            <p class="mt-1 text-sm text-slate-900">{{ ucfirst($integration->status) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Last Sync') }}</p>
                            <p class="mt-1 text-sm text-slate-900">{{ optional($integration->last_synced_at)->toDateTimeString() ?? __('Never') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Organization') }}</p>
                            <p class="mt-1 text-sm text-slate-900">
                                {{ $viewState['isShodan'] ? __('Global integration') : ($integration->tenant?->name ?? __('Global / Not configured')) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ __('Last Error') }}</p>
                            <p class="mt-1 text-sm text-slate-900">{{ $integration->last_error ?: __('None') }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Credential Summary') }}</h3>
                    </div>
                    <div class="grid gap-6 px-6 py-5 md:grid-cols-2">
                        @if($viewState['isShodan'])
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('API Key') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ data_get($viewState['credentials'], 'api_key') ? __('Stored securely') : __('Not configured') }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('Base URL') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ data_get($viewState['credentials'], 'base_url', 'https://api.shodan.io') }}</p>
                            </div>
                        @elseif($viewState['isGenericIp'])
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('Base URL') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ data_get($viewState['credentials'], 'base_url', __('Not configured')) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('Auth Mode') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ \Illuminate\Support\Str::of(data_get($viewState['credentials'], 'auth_mode', 'none'))->replace('_', ' ')->title() }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('Secret') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ data_get($viewState['credentials'], 'api_key') ? __('Stored securely') : __('Not configured') }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('IP Parameter') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ data_get($viewState['credentials'], 'ip_parameter_name', 'ip') }}</p>
                            </div>
                        @else
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('Tenant ID') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ data_get($viewState['credentials'], 'tenant_id', __('Not configured')) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('Client ID') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ data_get($viewState['credentials'], 'client_id', __('Not configured')) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ __('Client Secret') }}</p>
                                <p class="mt-1 text-sm text-slate-900">{{ __('Stored securely') }}</p>
                            </div>
                        @endif
                    </div>

                    @if($viewState['credentials'] === null)
                        <div class="border-t border-slate-200 px-6 py-4 text-sm text-amber-700">
                            {{ __('Stored credentials can no longer be decrypted with the current application key. Re-enter the integration credentials to restore sync.') }}
                        </div>
                    @endif
                </section>

                @if($viewState['isGraph'])
                    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <div class="flex items-center justify-between gap-4">
                                <h3 class="text-lg font-semibold text-slate-900">{{ __('Sync Progress') }}</h3>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $viewState['syncStatusClass'] }}">
                                    {{ \Illuminate\Support\Str::of($viewState['syncStatus'])->replace('_', ' ')->title() }}
                                </span>
                            </div>
                        </div>
                        <div class="grid gap-6 px-6 py-5 md:grid-cols-2 lg:grid-cols-3">
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Requested') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'last_requested_at', __('Never')) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Sync Started') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'last_sync_started_at', __('Never')) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Fetch Finished') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'last_sync_finished_at', __('Never')) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Processing Completed') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'last_processing_completed_at', __('Not yet')) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Sign-Ins Collected (Last Sync)') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'last_sync_sign_in_count', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Risk Detections Collected (Last Sync)') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'last_sync_risk_detection_count', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Threat Events Stored') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'total_threat_events', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Threat Events Processed') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'processed_threat_events', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Threat Events Pending Analysis (Current Sync)') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'pending_analysis', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Unprocessed Backlog (Older Events)') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'pending_backlog', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Alerts Created') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'incident_count', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Open / In Progress Alerts') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($syncProgress, 'open_incident_count', 0) }}</p></div>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('Policy Settings') }}</h3>
                        </div>
                        <div class="grid gap-6 px-6 py-5 md:grid-cols-2">
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Trusted Countries') }}</p><p class="mt-1 text-sm text-slate-900">{{ implode(', ', data_get($integration->settings, 'trusted_countries', [])) ?: __('Not configured') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Trusted Networks') }}</p><p class="mt-1 text-sm text-slate-900">{{ implode(', ', data_get($integration->settings, 'trusted_networks', [])) ?: __('Not configured') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('External Sign-In Detection') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->settings, 'detect_external_signins', true) ? __('Enabled') : __('Disabled') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Unusual Country Detection') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->settings, 'detect_unusual_countries', true) ? __('Enabled') : __('Disabled') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Notify on High Severity') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->settings, 'notify_high_severity', true) ? __('Yes') : __('No') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Retention Cleanup') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->settings, 'retention.enabled', false) ? __('Enabled') : __('Disabled') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Retention Period') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->settings, 'retention.days', 90) }} {{ __('days') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Cleanup Interval') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->settings, 'retention.cleanup_interval_hours', 24) }} {{ __('hours') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Retention Cleanup') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->sync_state, 'last_retention_cleanup_at', __('Never')) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Deleted Threat Events') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->sync_state, 'last_retention_cleanup_deleted_events', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Deleted Alerts') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->sync_state, 'last_retention_cleanup_deleted_alerts', 0) }}</p></div>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('Threat Analysis Policy') }}</h3>
                        </div>
                        <div class="grid gap-6 px-6 py-5 md:grid-cols-2 lg:grid-cols-3">
                            <div><p class="text-sm font-medium text-slate-500">{{ __('High Severity Threshold') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.severity_high_threshold', 60) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Medium Severity Threshold') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.severity_medium_threshold', 30) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('External Sign-In Points') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.successful_external_signin_points', 10) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Failure Then Success Points') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.failure_then_success_points', 25) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Graph High Risk Points') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.graph_high_risk_points', 70) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Graph Medium Risk Points') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.graph_medium_risk_points', 40) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Graph Low Risk Points') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.graph_low_risk_points', 15) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Account At Risk Points') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.account_at_risk_points', 20) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Confirmed Compromise Points') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($viewState, 'analysisPolicy.confirmed_compromise_points', 25) }}</p></div>
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div class="border-b border-slate-200 px-6 py-5">
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Sync State') }}</h3>
                    </div>
                    <div class="grid gap-6 px-6 py-5 md:grid-cols-2">
                        @if($viewState['isShodan'] || $viewState['isGenericIp'])
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Dispatch') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->sync_state, 'last_dispatched_at') ?: __('Never') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Jobs Dispatched') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->sync_state, 'last_job_dispatch_count', 0) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Asset Sync') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->sync_state, 'last_report_synced_at') ?: __('Never') }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Asset Status') }}</p><p class="mt-1 text-sm text-slate-900">{{ data_get($integration->sync_state, 'last_report_status', __('Unknown')) }}</p></div>
                        @else
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Sign-Ins Cursor') }}</p><p class="mt-1 break-all text-sm text-slate-900">{{ data_get($integration->sync_state, 'sign_ins_last_seen_at', __('Not available')) }}</p></div>
                            <div><p class="text-sm font-medium text-slate-500">{{ __('Last Risk Detections Cursor') }}</p><p class="mt-1 break-all text-sm text-slate-900">{{ data_get($integration->sync_state, 'risk_detections_last_seen_at', __('Not available')) }}</p></div>
                        @endif
                    </div>
                </section>

                @if($viewState['isShodan'])
                    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('Recent Asset Reports') }}</h3>
                        </div>
                        <div class="overflow-x-auto px-6 py-5">
                            <table class="min-w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Asset') }}</th>
                                    <th class="px-4 py-3">{{ __('IP Address') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Synced At') }}</th>
                                    <th class="px-4 py-3">{{ __('Error') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($recentAssetReports as $report)
                                    <tr class="border-b border-slate-100">
                                        <td class="px-4 py-3">{{ $report->asset?->name ?? __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ $report->ip_address }}</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($report->status)->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3">{{ optional($report->synced_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ $report->error ?: __('None') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-3 text-slate-500">{{ __('No asset reports have been created yet.') }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @elseif($viewState['isGenericIp'])
                    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 px-6 py-5">
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Usage') }}</h3>
                        <p class="mt-3 text-sm text-slate-700">
                            {{ __('This integration is used by attack surface enrichment for discovered hosts. Review the selected attack surface scopes to control when it is used.') }}
                        </p>
                    </section>
                @else
                    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('Recent Threat Events') }}</h3>
                        </div>
                        <div class="overflow-x-auto px-6 py-5">
                            <table class="min-w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Occurred At') }}</th>
                                    <th class="px-4 py-3">{{ __('Type') }}</th>
                                    <th class="px-4 py-3">{{ __('Principal') }}</th>
                                    <th class="px-4 py-3">{{ __('Severity') }}</th>
                                    <th class="px-4 py-3">{{ __('Score') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($integration->threatEvents as $event)
                                    <tr class="border-b border-slate-100">
                                        <td class="px-4 py-3">{{ optional($event->occurred_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($event->event_type)->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3">{{ $event->principal_display ?: $event->principal ?: __('Unknown') }}</td>
                                        <td class="px-4 py-3">{{ strtoupper($event->severity) }}</td>
                                        <td class="px-4 py-3">{{ $event->score }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-3 text-slate-500">{{ __('No threat events synced yet.') }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h3 class="text-lg font-semibold text-slate-900">{{ __('Recent Alerts') }}</h3>
                        </div>
                        <div class="overflow-x-auto px-6 py-5">
                            <table class="min-w-full text-left text-sm text-slate-600">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Title') }}</th>
                                    <th class="px-4 py-3">{{ __('Severity') }}</th>
                                    <th class="px-4 py-3">{{ __('Status') }}</th>
                                    <th class="px-4 py-3">{{ __('Last Seen') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($integration->incidents as $incident)
                                    <tr class="border-b border-slate-100">
                                        <td class="px-4 py-3">{{ $incident->title }}</td>
                                        <td class="px-4 py-3">{{ strtoupper($incident->severity) }}</td>
                                        <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($incident->status->value ?? $incident->status)->replace('_', ' ')->title() }}</td>
                                        <td class="px-4 py-3">{{ optional($incident->last_seen_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-3 text-slate-500">{{ __('No alerts created yet.') }}</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 px-6 py-5">
                    <form method="POST" action="{{ route('integrations.destroy', $integration) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800"
                                onclick="return confirm('{{ __('Delete this integration?') }}')">
                            {{ __('Delete') }}
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>
</div>

@livewireScripts
</body>
</html>
