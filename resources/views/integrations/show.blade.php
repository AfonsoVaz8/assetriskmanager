<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Integration Details') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 space-y-8">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-semibold text-gray-900">{{ $integration->name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ \Illuminate\Support\Str::of($integration->provider)->replace('_', ' ')->title() }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('integrations.sync', $integration) }}">
                                @csrf
                                <button type="submit" class="text-white bg-emerald-700 hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                    {{ __('Sync Now') }}
                                </button>
                            </form>
                            <a href="{{ route('integrations.edit', $integration) }}"
                               class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                                {{ __('Edit') }}
                            </a>
                        </div>
                    </div>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($integration->status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Last Sync') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ optional($integration->last_synced_at)->toDateTimeString() ?? __('Never') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Organization') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $viewState['isShodan'] ? __('Global integration') : ($integration->tenant?->name ?? __('Global / Not configured')) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Last Error') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $integration->last_error ?: __('None') }}</dd>
                        </div>
                    </dl>

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Credential Summary') }}</h4>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($viewState['isShodan'])
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('API Key') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState['credentials'], 'api_key') ? __('Stored securely') : __('Not configured') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Base URL') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState['credentials'], 'base_url', 'https://api.shodan.io') }}</dd>
                                </div>
                            @elseif($viewState['isGenericIp'])
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Base URL') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState['credentials'], 'base_url', __('Not configured')) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Auth Mode') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ \Illuminate\Support\Str::of(data_get($viewState['credentials'], 'auth_mode', 'none'))->replace('_', ' ')->title() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Secret') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState['credentials'], 'api_key') ? __('Stored securely') : __('Not configured') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('IP Parameter') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState['credentials'], 'ip_parameter_name', 'ip') }}</dd>
                                </div>
                            @else
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Tenant ID') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState['credentials'], 'tenant_id', __('Not configured')) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Client ID') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState['credentials'], 'client_id', __('Not configured')) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">{{ __('Client Secret') }}</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ __('Stored securely') }}</dd>
                                </div>
                            @endif
                        </dl>

                        @if($viewState['credentials'] === null)
                            <p class="mt-4 text-sm text-amber-700">
                                {{ __('Stored credentials can no longer be decrypted with the current application key. Re-enter the integration credentials to restore sync.') }}
                            </p>
                        @endif
                    </div>

                    @if($viewState['isGraph'])
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Sync Progress') }}</h4>
                            <div class="mb-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $viewState['syncStatusClass'] }}">
                                    {{ \Illuminate\Support\Str::of($viewState['syncStatus'])->replace('_', ' ')->title() }}
                                </span>
                            </div>
                            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Last Requested') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'last_requested_at', __('Never')) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Sync Started') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'last_sync_started_at', __('Never')) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Fetch Finished') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'last_sync_finished_at', __('Never')) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Processing Completed') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'last_processing_completed_at', __('Not yet')) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Sign-Ins Collected (Last Sync)') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'last_sync_sign_in_count', 0) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Risk Detections Collected (Last Sync)') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'last_sync_risk_detection_count', 0) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Threat Events Stored') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'total_threat_events', 0) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Threat Events Processed') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'processed_threat_events', 0) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Threat Events Pending Analysis') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'pending_analysis', 0) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Incidents Created') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'incident_count', 0) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Open / In Progress Incidents') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($syncProgress, 'open_incident_count', 0) }}</dd></div>
                            </dl>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Policy Settings') }}</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Trusted Countries') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ implode(', ', data_get($integration->settings, 'trusted_countries', [])) ?: __('Not configured') }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Trusted Networks') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ implode(', ', data_get($integration->settings, 'trusted_networks', [])) ?: __('Not configured') }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('External Sign-In Detection') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($integration->settings, 'detect_external_signins', true) ? __('Enabled') : __('Disabled') }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Unusual Country Detection') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($integration->settings, 'detect_unusual_countries', true) ? __('Enabled') : __('Disabled') }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Notify on High Severity') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($integration->settings, 'notify_high_severity', true) ? __('Yes') : __('No') }}</dd></div>
                            </dl>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Threat Analysis Policy') }}</h4>
                            <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('High Severity Threshold') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.severity_high_threshold', 60) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Medium Severity Threshold') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.severity_medium_threshold', 30) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('External Sign-In Points') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.successful_external_signin_points', 10) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Failure Then Success Points') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.failure_then_success_points', 25) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Graph High Risk Points') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.graph_high_risk_points', 70) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Graph Medium Risk Points') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.graph_medium_risk_points', 40) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Graph Low Risk Points') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.graph_low_risk_points', 15) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Account At Risk Points') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.account_at_risk_points', 20) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Confirmed Compromise Points') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($viewState, 'analysisPolicy.confirmed_compromise_points', 25) }}</dd></div>
                            </dl>
                        </div>
                    @endif

                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Sync State') }}</h4>
                        @if($viewState['isShodan'] || $viewState['isGenericIp'])
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Last Dispatch') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($integration->sync_state, 'last_dispatched_at') ?: __('Never') }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Jobs Dispatched') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($integration->sync_state, 'last_job_dispatch_count', 0) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Last Asset Sync') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($integration->sync_state, 'last_report_synced_at') ?: __('Never') }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Last Asset Status') }}</dt><dd class="mt-1 text-sm text-gray-900">{{ data_get($integration->sync_state, 'last_report_status', __('Unknown')) }}</dd></div>
                            </dl>
                        @else
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Last Sign-Ins Cursor') }}</dt><dd class="mt-1 text-sm text-gray-900 break-all">{{ data_get($integration->sync_state, 'sign_ins_last_seen_at', __('Not available')) }}</dd></div>
                                <div><dt class="text-sm font-medium text-gray-500">{{ __('Last Risk Detections Cursor') }}</dt><dd class="mt-1 text-sm text-gray-900 break-all">{{ data_get($integration->sync_state, 'risk_detections_last_seen_at', __('Not available')) }}</dd></div>
                            </dl>
                        @endif
                    </div>

                    @if($viewState['isShodan'])
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Recent Asset Reports') }}</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
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
                                        <tr class="bg-white border-b">
                                            <td class="px-4 py-3">{{ $report->asset?->name ?? __('Unknown') }}</td>
                                            <td class="px-4 py-3">{{ $report->ip_address }}</td>
                                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($report->status)->replace('_', ' ')->title() }}</td>
                                            <td class="px-4 py-3">{{ optional($report->synced_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                            <td class="px-4 py-3">{{ $report->error ?: __('None') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-4 py-3 text-gray-500">{{ __('No asset reports have been created yet.') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @elseif($viewState['isGenericIp'])
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Usage') }}</h4>
                            <p class="text-sm text-gray-700">
                                {{ __('This integration is used by attack surface enrichment for discovered hosts. Review the selected attack surface scopes to control when it is used.') }}
                            </p>
                        </div>
                    @else
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Recent Threat Events') }}</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
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
                                        <tr class="bg-white border-b">
                                            <td class="px-4 py-3">{{ optional($event->occurred_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($event->event_type)->replace('_', ' ')->title() }}</td>
                                            <td class="px-4 py-3">{{ $event->principal_display ?: $event->principal ?: __('Unknown') }}</td>
                                            <td class="px-4 py-3">{{ strtoupper($event->severity) }}</td>
                                            <td class="px-4 py-3">{{ $event->score }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="px-4 py-3 text-gray-500">{{ __('No threat events synced yet.') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Recent Incidents') }}</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3">{{ __('Title') }}</th>
                                        <th class="px-4 py-3">{{ __('Severity') }}</th>
                                        <th class="px-4 py-3">{{ __('Status') }}</th>
                                        <th class="px-4 py-3">{{ __('Last Seen') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($integration->incidents as $incident)
                                        <tr class="bg-white border-b">
                                            <td class="px-4 py-3">{{ $incident->title }}</td>
                                            <td class="px-4 py-3">{{ strtoupper($incident->severity) }}</td>
                                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::of($incident->status->value ?? $incident->status)->replace('_', ' ')->title() }}</td>
                                            <td class="px-4 py-3">{{ optional($incident->last_seen_at)->toDateTimeString() ?? __('Unknown') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-3 text-gray-500">{{ __('No incidents created yet.') }}</td></tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('integrations.destroy', $integration) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5"
                                    onclick="return confirm('{{ __('Delete this integration?') }}')">
                                {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
