@php
    $existingCredentials = isset($integration) ? $integration->safeCredentials() : [];
    $selectedProvider = old('provider', $integration->provider ?? \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value);
    $analysisPolicyDefaults = [
        'severity_high_threshold' => data_get($integration ?? null, 'settings.analysis_policy.severity_high_threshold', 60),
        'severity_medium_threshold' => data_get($integration ?? null, 'settings.analysis_policy.severity_medium_threshold', 30),
        'successful_signin_points' => data_get($integration ?? null, 'settings.analysis_policy.successful_signin_points', 5),
        'successful_external_signin_points' => data_get($integration ?? null, 'settings.analysis_policy.successful_external_signin_points', 10),
        'ip_reputation_high_points' => data_get($integration ?? null, 'settings.analysis_policy.ip_reputation_high_points', 50),
        'ip_reputation_nonzero_points' => data_get($integration ?? null, 'settings.analysis_policy.ip_reputation_nonzero_points', 25),
        'unusual_country_points' => data_get($integration ?? null, 'settings.analysis_policy.unusual_country_points', 15),
        'sensitive_application_points' => data_get($integration ?? null, 'settings.analysis_policy.sensitive_application_points', 15),
        'single_factor_auth_points' => data_get($integration ?? null, 'settings.analysis_policy.single_factor_auth_points', 20),
        'conditional_access_not_applied_points' => data_get($integration ?? null, 'settings.analysis_policy.conditional_access_not_applied_points', 15),
        'missing_os_context_points' => data_get($integration ?? null, 'settings.analysis_policy.missing_os_context_points', 5),
        'missing_browser_context_points' => data_get($integration ?? null, 'settings.analysis_policy.missing_browser_context_points', 5),
        'failure_then_success_points' => data_get($integration ?? null, 'settings.analysis_policy.failure_then_success_points', 25),
        'graph_high_risk_points' => data_get($integration ?? null, 'settings.analysis_policy.graph_high_risk_points', 70),
        'graph_medium_risk_points' => data_get($integration ?? null, 'settings.analysis_policy.graph_medium_risk_points', 40),
        'graph_low_risk_points' => data_get($integration ?? null, 'settings.analysis_policy.graph_low_risk_points', 15),
        'account_at_risk_points' => data_get($integration ?? null, 'settings.analysis_policy.account_at_risk_points', 20),
        'confirmed_compromise_points' => data_get($integration ?? null, 'settings.analysis_policy.confirmed_compromise_points', 25),
    ];
    $settings = old('settings', [
        'trusted_countries' => implode(',', data_get($integration ?? null, 'settings.trusted_countries', [])),
        'trusted_networks' => implode(',', data_get($integration ?? null, 'settings.trusted_networks', [])),
        'detect_external_signins' => data_get($integration ?? null, 'settings.detect_external_signins', true),
        'detect_unusual_countries' => data_get($integration ?? null, 'settings.detect_unusual_countries', true),
        'notify_high_severity' => data_get($integration ?? null, 'settings.notify_high_severity', true),
        'analysis_policy' => $analysisPolicyDefaults,
    ]);
@endphp

<div x-data="{ provider: '{{ $selectedProvider }}', graphTab: 'connection' }">
<div class="mb-6">
    <label for="name"
           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Name") }}</label>
    <input type="text" id="name" name="name"
           value="{{ old('name', $integration->name ?? '') }}"
           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
           required>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="mb-6">
        <label for="provider"
               class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Provider") }}</label>
        <select id="provider" name="provider" x-model="provider"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                required>
            @foreach($providers as $provider)
                <option value="{{ $provider->value }}" @selected(old('provider', $integration->provider ?? '') === $provider->value)>
                    {{ \Illuminate\Support\Str::of($provider->value)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-6">
        <label for="status"
               class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Status") }}</label>
        <select id="status" name="status"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                required>
            <option value="active" @selected(old('status', $integration->status ?? 'active') === 'active')>{{ __("Active") }}</option>
            <option value="disabled" @selected(old('status', $integration->status ?? '') === 'disabled')>{{ __("Disabled") }}</option>
        </select>
    </div>
</div>

<div class="mb-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Scope") }}</label>
    <p class="text-sm text-gray-600">
        {{ __("Microsoft Graph integrations keep their own sync cursors and threat monitoring state.") }}
    </p>
</div>

<div class="mb-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::SHODAN->value }}'">
    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Scope") }}</label>
    <p class="text-sm text-gray-600">
        {{ __("This Shodan integration is currently applied globally to assets with an IP address. A dedicated organization scope can be added once the platform has a real organization/tenant model.") }}
    </p>
</div>

<div class="mb-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::GENERIC_IP_INTELLIGENCE->value }}'">
    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Scope") }}</label>
    <p class="text-sm text-gray-600">
        {{ __("This generic IP intelligence integration is intended for attack surface enrichment of discovered hosts and other external IP lookups.") }}
    </p>
</div>

<div class="border-t pt-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900">{{ __("Provider Credentials") }}</h3>
</div>

<div class="mb-6 mt-4" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <div class="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
        <button type="button"
                @click="graphTab = 'connection'"
                :class="graphTab === 'connection' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                class="rounded-lg px-4 py-2 text-sm font-medium transition">
            {{ __("Connection") }}
        </button>
        <button type="button"
                @click="graphTab = 'policy'"
                :class="graphTab === 'policy' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                class="rounded-lg px-4 py-2 text-sm font-medium transition">
            {{ __("Threat Policy") }}
        </button>
    </div>
</div>

<div x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'connection'">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-6">
            <label for="credentials_tenant_id"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Tenant ID") }}</label>
            <input type="text" id="credentials_tenant_id" name="credentials[tenant_id]"
                   value="{{ old('credentials.tenant_id', data_get($existingCredentials, 'tenant_id', '')) }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        </div>

        <div class="mb-6">
            <label for="credentials_client_id"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Client ID") }}</label>
            <input type="text" id="credentials_client_id" name="credentials[client_id]"
                   value="{{ old('credentials.client_id', data_get($existingCredentials, 'client_id', '')) }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        </div>
    </div>

    <div class="mb-6">
        <label for="credentials_client_secret"
               class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Client Secret") }}</label>
        <input type="password" id="credentials_client_secret" name="credentials[client_secret]"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        @if(isset($integration))
            <p class="mt-2 text-sm text-gray-500">
                {{ $existingCredentials === null
                    ? __("The stored credentials can no longer be decrypted. Enter all credential fields again, including the secret.")
                    : __("Leave blank to keep the existing secret.") }}
            </p>
        @endif
    </div>
</div>

<div x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::SHODAN->value }}'">
    <div class="mb-6">
        <label for="credentials_api_key"
               class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Shodan API Key") }}</label>
        <input type="password" id="credentials_api_key" name="credentials[api_key]"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        @if(isset($integration))
            <p class="mt-2 text-sm text-gray-500">{{ __("Leave blank to keep the existing key.") }}</p>
        @endif
    </div>

    <div class="mb-6">
        <label for="credentials_base_url"
               class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Base URL") }}</label>
        <input type="url" id="credentials_base_url" name="credentials[base_url]"
               value="{{ old('credentials.base_url', data_get($existingCredentials, 'base_url', 'https://api.shodan.io')) }}"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        <p class="mt-2 text-sm text-gray-500">{{ __("Use the default unless you explicitly need a different Shodan endpoint.") }}</p>
    </div>
</div>

<div x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::GENERIC_IP_INTELLIGENCE->value }}'">
    <div class="mb-6">
        <label for="generic_base_url"
               class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Base URL") }}</label>
        <input type="url" id="generic_base_url" name="credentials[base_url]"
               value="{{ old('credentials.base_url', data_get($existingCredentials, 'base_url', '')) }}"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        <p class="mt-2 text-sm text-gray-500">{{ __("Example: https://provider.example/api/ip") }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-6">
            <label for="generic_auth_mode"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Auth Mode") }}</label>
            <select id="generic_auth_mode" name="credentials[auth_mode]"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                @foreach(['none', 'bearer', 'header', 'query'] as $authMode)
                    <option value="{{ $authMode }}" @selected(old('credentials.auth_mode', data_get($existingCredentials, 'auth_mode', 'none')) === $authMode)>{{ \Illuminate\Support\Str::of($authMode)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-6">
            <label for="generic_api_key"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Secret / API Key") }}</label>
            <input type="password" id="generic_api_key" name="credentials[api_key]"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            @if(isset($integration))
                <p class="mt-2 text-sm text-gray-500">{{ __("Leave blank to keep the existing secret.") }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-6">
            <label for="generic_auth_parameter_name"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Auth Header / Query Name") }}</label>
            <input type="text" id="generic_auth_parameter_name" name="credentials[auth_parameter_name]"
                   value="{{ old('credentials.auth_parameter_name', data_get($existingCredentials, 'auth_parameter_name', 'X-API-Key')) }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        </div>

        <div class="mb-6">
            <label for="generic_ip_parameter_name"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("IP Parameter Name") }}</label>
            <input type="text" id="generic_ip_parameter_name" name="credentials[ip_parameter_name]"
                   value="{{ old('credentials.ip_parameter_name', data_get($existingCredentials, 'ip_parameter_name', 'ip')) }}"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-6">
            <label for="generic_response_root_path"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Response Root Path") }}</label>
            <input type="text" id="generic_response_root_path" name="credentials[response_root_path]"
                   value="{{ old('credentials.response_root_path', data_get($existingCredentials, 'response_root_path', '')) }}"
                   placeholder="data.result"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        </div>

        <div class="mb-6">
            <label for="generic_source_label"
                   class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Source Label") }}</label>
            <input type="text" id="generic_source_label" name="credentials[source_label]"
                   value="{{ old('credentials.source_label', data_get($existingCredentials, 'source_label', '')) }}"
                   placeholder="My External IP API"
                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        </div>
    </div>
</div>

<div class="border-t pt-6 mt-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    <h3 class="text-lg font-semibold text-gray-900">{{ __("Threat Policy Settings") }}</h3>
    <p class="mt-2 text-sm text-gray-600">{{ __("Adjust these values to reflect how your organization interprets Microsoft 365 risk. The current defaults match the existing analysis behavior.") }}</p>
</div>

<div class="mb-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    <label for="settings_trusted_countries"
           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Trusted Countries") }}</label>
    <input type="text" id="settings_trusted_countries" name="settings[trusted_countries]"
           value="{{ $settings['trusted_countries'] }}"
           placeholder="PT,ES"
           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
</div>

<div class="mb-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    <label for="settings_trusted_networks"
           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Trusted Networks") }}</label>
    <input type="text" id="settings_trusted_networks" name="settings[trusted_networks]"
           value="{{ $settings['trusted_networks'] }}"
           placeholder="193.136.0.0/15,10.0.0.0/8"
           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
</div>

<div class="mb-6 flex items-center" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    <input type="hidden" name="settings[detect_external_signins]" value="0">
    <input type="checkbox" id="settings_detect_external_signins" name="settings[detect_external_signins]" value="1"
           @checked((bool) $settings['detect_external_signins'])
           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
    <label for="settings_detect_external_signins" class="ml-2 text-sm text-gray-700">
        {{ __("Flag successful sign-ins from outside trusted networks") }}
    </label>
</div>

<div class="mb-6 flex items-center" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    <input type="hidden" name="settings[detect_unusual_countries]" value="0">
    <input type="checkbox" id="settings_detect_unusual_countries" name="settings[detect_unusual_countries]" value="1"
           @checked((bool) $settings['detect_unusual_countries'])
           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
    <label for="settings_detect_unusual_countries" class="ml-2 text-sm text-gray-700">
        {{ __("Flag sign-ins from countries outside the trusted list") }}
    </label>
</div>

<div class="mb-6 flex items-center" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    <input type="hidden" name="settings[notify_high_severity]" value="0">
    <input type="checkbox" id="settings_notify_high_severity" name="settings[notify_high_severity]" value="1"
           @checked((bool) $settings['notify_high_severity'])
           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
    <label for="settings_notify_high_severity" class="ml-2 text-sm text-gray-700">
        {{ __("Notify responsible users when severity is high") }}
    </label>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    <div class="mb-6">
        <label class="block mb-2 text-sm font-medium text-gray-900">{{ __("High Severity Threshold") }}</label>
        <input type="number" name="settings[analysis_policy][severity_high_threshold]" value="{{ data_get($settings, 'analysis_policy.severity_high_threshold', 60) }}" min="0" max="1000" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
    </div>
    <div class="mb-6">
        <label class="block mb-2 text-sm font-medium text-gray-900">{{ __("Medium Severity Threshold") }}</label>
        <input type="number" name="settings[analysis_policy][severity_medium_threshold]" value="{{ data_get($settings, 'analysis_policy.severity_medium_threshold', 30) }}" min="0" max="1000" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}' && graphTab === 'policy'">
    @foreach([
        'successful_signin_points' => __('Successful Sign-In'),
        'successful_external_signin_points' => __('Successful External Sign-In'),
        'ip_reputation_high_points' => __('High IP Reputation Risk'),
        'ip_reputation_nonzero_points' => __('Non-Zero IP Reputation Risk'),
        'unusual_country_points' => __('Unusual Country'),
        'sensitive_application_points' => __('Sensitive Application'),
        'single_factor_auth_points' => __('Single-Factor Authentication'),
        'conditional_access_not_applied_points' => __('Conditional Access Not Applied'),
        'missing_os_context_points' => __('Missing Operating System Context'),
        'missing_browser_context_points' => __('Missing Browser Context'),
        'failure_then_success_points' => __('Failures Followed By Success'),
        'graph_high_risk_points' => __('Graph High Risk'),
        'graph_medium_risk_points' => __('Graph Medium Risk'),
        'graph_low_risk_points' => __('Graph Low Risk'),
        'account_at_risk_points' => __('Account At Risk'),
        'confirmed_compromise_points' => __('Confirmed Compromise Signal'),
    ] as $policyKey => $policyLabel)
        <div class="mb-6">
            <label class="block mb-2 text-sm font-medium text-gray-900">{{ $policyLabel }}</label>
            <input type="number" name="settings[analysis_policy][{{ $policyKey }}]" value="{{ data_get($settings, 'analysis_policy.' . $policyKey) }}" min="0" max="1000" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        </div>
    @endforeach
</div>

<button type="submit"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">
    {{ $submitLabel }}
</button>
</div>
