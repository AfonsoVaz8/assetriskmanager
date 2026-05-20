@php
    $existingCredentials = isset($integration) ? $integration->safeCredentials() : [];
    $selectedProvider = old('provider', $integration->provider ?? \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value);
    $settings = old('settings', [
        'trusted_countries' => implode(',', data_get($integration ?? null, 'settings.trusted_countries', [])),
        'trusted_networks' => implode(',', data_get($integration ?? null, 'settings.trusted_networks', [])),
        'detect_external_signins' => data_get($integration ?? null, 'settings.detect_external_signins', true),
        'detect_unusual_countries' => data_get($integration ?? null, 'settings.detect_unusual_countries', true),
        'notify_high_severity' => data_get($integration ?? null, 'settings.notify_high_severity', true),
    ]);
@endphp

<div x-data="{ provider: '{{ $selectedProvider }}' }">
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

<div x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
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

<div class="border-t pt-6 mt-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <h3 class="text-lg font-semibold text-gray-900">{{ __("Threat Policy Settings") }}</h3>
</div>

<div class="mb-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <label for="settings_trusted_countries"
           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Trusted Countries") }}</label>
    <input type="text" id="settings_trusted_countries" name="settings[trusted_countries]"
           value="{{ $settings['trusted_countries'] }}"
           placeholder="PT,ES"
           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
</div>

<div class="mb-6" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <label for="settings_trusted_networks"
           class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">{{ __("Trusted Networks") }}</label>
    <input type="text" id="settings_trusted_networks" name="settings[trusted_networks]"
           value="{{ $settings['trusted_networks'] }}"
           placeholder="193.136.0.0/15,10.0.0.0/8"
           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
</div>

<div class="mb-6 flex items-center" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <input type="hidden" name="settings[detect_external_signins]" value="0">
    <input type="checkbox" id="settings_detect_external_signins" name="settings[detect_external_signins]" value="1"
           @checked((bool) $settings['detect_external_signins'])
           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
    <label for="settings_detect_external_signins" class="ml-2 text-sm text-gray-700">
        {{ __("Flag successful sign-ins from outside trusted networks") }}
    </label>
</div>

<div class="mb-6 flex items-center" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <input type="hidden" name="settings[detect_unusual_countries]" value="0">
    <input type="checkbox" id="settings_detect_unusual_countries" name="settings[detect_unusual_countries]" value="1"
           @checked((bool) $settings['detect_unusual_countries'])
           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
    <label for="settings_detect_unusual_countries" class="ml-2 text-sm text-gray-700">
        {{ __("Flag sign-ins from countries outside the trusted list") }}
    </label>
</div>

<div class="mb-6 flex items-center" x-show="provider === '{{ \App\Domain\ThreatMonitoring\Enums\IntegrationProvider::MICROSOFT_GRAPH->value }}'">
    <input type="hidden" name="settings[notify_high_severity]" value="0">
    <input type="checkbox" id="settings_notify_high_severity" name="settings[notify_high_severity]" value="1"
           @checked((bool) $settings['notify_high_severity'])
           class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
    <label for="settings_notify_high_severity" class="ml-2 text-sm text-gray-700">
        {{ __("Notify responsible users when severity is high") }}
    </label>
</div>

<button type="submit"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">
    {{ $submitLabel }}
</button>
</div>
