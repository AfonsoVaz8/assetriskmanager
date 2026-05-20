@php
    $scope = $scope ?? null;
    $selectedType = old('type', $scope?->type?->value ?? \App\Enums\AttackSurfaceScopeType::REGISTERED_ASSETS->value);
    $settings = old('settings', $scope->settings ?? []);
    $scopeDefinition = old('scope_definition', $scope->scope_definition ?? []);
@endphp

<div x-data="{ type: '{{ $selectedType }}', autoCreate: {{ old('settings.auto_create_assets', data_get($scope?->settings, 'auto_create_assets', false)) ? 'true' : 'false' }} }">
    <div class="mb-6">
        <label for="name" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Name') }}</label>
        <input type="text" id="name" name="name" value="{{ old('name', $scope->name ?? '') }}"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
    </div>

    <div class="mb-6">
        <label for="type" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Scope Type') }}</label>
        <select id="type" name="type" x-model="type"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            @foreach($types as $type)
                <option value="{{ $type->value }}">{{ \Illuminate\Support\Str::of($type->value)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-6" x-show="type === '{{ \App\Enums\AttackSurfaceScopeType::CIDR_RANGE->value }}'">
        <label for="cidr" class="block mb-2 text-sm font-medium text-gray-900">{{ __('CIDR Range') }}</label>
        <input type="text" id="cidr" name="scope_definition[cidr]" value="{{ data_get($scopeDefinition, 'cidr', '') }}"
               placeholder="203.0.113.0/24"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        <p class="mt-2 text-sm text-gray-500">{{ __('Current safe mode only allows IPv4 CIDR ranges between /24 and /32. Use the network base for ranges, such as 52.156.202.0/24, or use /32 for a single IP such as 52.156.202.75/32.') }}</p>
    </div>

    <div class="mb-6" x-show="type === '{{ \App\Enums\AttackSurfaceScopeType::HOSTNAME_TARGET->value }}'">
        <label for="hostname" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Hostname or Domain') }}</label>
        <input type="text" id="hostname" name="scope_definition[hostname]" value="{{ data_get($scopeDefinition, 'hostname', '') }}"
               placeholder="www.isep.portal.ipp.pt"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        <p class="mt-2 text-sm text-gray-500">{{ __('The platform will resolve the hostname/domain to one or more IPs and then probe those IPs to expand the external attack surface inventory.') }}</p>
    </div>

    <div class="mb-6" x-show="type === '{{ \App\Enums\AttackSurfaceScopeType::DOMAIN_EXPANSION->value }}'">
        <label for="domain" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Base Domain') }}</label>
        <input type="text" id="domain" name="scope_definition[domain]" value="{{ data_get($scopeDefinition, 'domain', '') }}"
               placeholder="ipp.pt"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
        <p class="mt-2 text-sm text-gray-500">{{ __('The platform will use passive subdomain discovery for the base domain, resolve the discovered hostnames to IPs and then probe those IPs to expand the external attack surface inventory more broadly than a single hostname lookup.') }}</p>
    </div>

    <div class="mb-6">
        <label for="justification" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Justification') }}</label>
        <textarea id="justification" name="justification" rows="4"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">{{ old('justification', $scope->justification ?? '') }}</textarea>
    </div>

    <div class="border-t pt-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('Safe Discovery Settings') }}</h3>
    </div>

    <div class="mb-6">
        <label for="ports" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Probe Ports') }}</label>
        <input type="text" id="ports" name="settings[ports]" value="{{ implode(',', data_get($settings, 'ports', [80, 443, 22])) }}"
               placeholder="80,443,22"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
    </div>

    <div class="mb-6">
        <label for="timeout_seconds" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Timeout Seconds') }}</label>
        <input type="number" step="0.1" min="0.2" max="3" id="timeout_seconds" name="settings[timeout_seconds]"
               value="{{ data_get($settings, 'timeout_seconds', 1.0) }}"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
    </div>

    <div class="mb-6">
        <label for="enrichment_integration_id" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Enrichment Integration') }}</label>
        <select id="enrichment_integration_id" name="settings[enrichment_integration_id]"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            <option value="">{{ __('Automatic fallback (current default)') }}</option>
            @foreach($enrichmentIntegrations as $integration)
                <option value="{{ $integration->id }}" @selected((string) data_get($settings, 'enrichment_integration_id') === (string) $integration->id)>
                    {{ $integration->name }} - {{ \Illuminate\Support\Str::of($integration->provider)->replace('_', ' ')->title() }}
                </option>
            @endforeach
        </select>
        <p class="mt-2 text-sm text-gray-500">{{ __('Graph is excluded here. Select the API integration that should enrich discovered hosts.') }}</p>
    </div>

    <div class="border-t pt-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('Host Scanners') }}</h3>
        <p class="mt-2 text-sm text-gray-500">{{ __('Select which scanners should run for active discovered hosts. Each scanner adds a different kind of visibility so the user can decide how much depth is appropriate for this scope.') }}</p>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 mb-6 space-y-4">
        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
            <div class="font-medium text-slate-900">{{ __('Nmap') }}</div>
            <p class="mt-2 text-sm text-slate-600">{{ __('Nmap is used for network and service discovery. It helps identify open ports, protocols, server products, versions, banners and, when applicable, TLS certificate data.') }}</p>
        </div>

        <div class="flex items-center">
            <input type="hidden" name="settings[scanners][nmap][enabled]" value="0">
            <input id="scanner_nmap_enabled" type="checkbox" name="settings[scanners][nmap][enabled]" value="1"
                   @checked(data_get($settings, 'scanners.nmap.enabled', false))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="scanner_nmap_enabled" class="ml-2 text-sm font-medium text-gray-900">{{ __('Enable Nmap scanner') }}</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="scanner_nmap_timeout_seconds" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nmap Timeout Seconds') }}</label>
                <input type="number" min="15" max="300" id="scanner_nmap_timeout_seconds" name="settings[scanners][nmap][timeout_seconds]"
                       value="{{ data_get($settings, 'scanners.nmap.timeout_seconds', 90) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div class="flex items-center pt-8">
                <input type="hidden" name="settings[scanners][nmap][ssl_cert]" value="0">
                <input id="scanner_nmap_ssl_cert" type="checkbox" name="settings[scanners][nmap][ssl_cert]" value="1"
                       @checked(data_get($settings, 'scanners.nmap.ssl_cert', true))
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                <label for="scanner_nmap_ssl_cert" class="ml-2 text-sm font-medium text-gray-900">{{ __('Collect TLS certificate data with the ssl-cert script when applicable') }}</label>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 mb-6 space-y-4">
        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
            <div class="font-medium text-slate-900">{{ __('Nikto') }}</div>
            <p class="mt-2 text-sm text-slate-600">{{ __('Nikto is focused on web servers and web applications. It is useful for detecting insecure defaults, risky files, missing hardening, outdated components and common web exposure issues on HTTP or HTTPS services.') }}</p>
        </div>

        <div class="flex items-center">
            <input type="hidden" name="settings[scanners][nikto][enabled]" value="0">
            <input id="scanner_nikto_enabled" type="checkbox" name="settings[scanners][nikto][enabled]" value="1"
                   @checked(data_get($settings, 'scanners.nikto.enabled', false))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="scanner_nikto_enabled" class="ml-2 text-sm font-medium text-gray-900">{{ __('Enable Nikto scanner') }}</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label for="scanner_nikto_timeout_seconds" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nikto Timeout Seconds') }}</label>
                <input type="number" min="30" max="900" id="scanner_nikto_timeout_seconds" name="settings[scanners][nikto][timeout_seconds]"
                       value="{{ data_get($settings, 'scanners.nikto.timeout_seconds', 240) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="scanner_nikto_max_time_seconds" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nikto Max Scan Time Seconds') }}</label>
                <input type="number" min="30" max="900" id="scanner_nikto_max_time_seconds" name="settings[scanners][nikto][max_time_seconds]"
                       value="{{ data_get($settings, 'scanners.nikto.max_time_seconds', 120) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="scanner_nikto_plugins" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nikto Plugins') }}</label>
                <input type="text" id="scanner_nikto_plugins" name="settings[scanners][nikto][plugins]"
                       value="{{ data_get($settings, 'scanners.nikto.plugins', '') }}"
                       placeholder="@@ALL"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="scanner_nikto_tuning" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nikto Tuning') }}</label>
                <input type="text" id="scanner_nikto_tuning" name="settings[scanners][nikto][tuning]"
                       value="{{ data_get($settings, 'scanners.nikto.tuning', '') }}"
                       placeholder="123b"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 mb-6 space-y-4">
        <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
            <div class="font-medium text-slate-900">{{ __('Nuclei') }}</div>
            <p class="mt-2 text-sm text-slate-600">{{ __('Nuclei is a template-based scanner that is useful for identifying exposed panels, risky web exposures, common misconfigurations, SSL/TLS issues and, optionally, known CVE checks. In this platform it is intentionally run with a cautious profile by default.') }}</p>
        </div>

        <div class="flex items-center">
            <input type="hidden" name="settings[scanners][nuclei][enabled]" value="0">
            <input id="scanner_nuclei_enabled" type="checkbox" name="settings[scanners][nuclei][enabled]" value="1"
                   @checked(data_get($settings, 'scanners.nuclei.enabled', false))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="scanner_nuclei_enabled" class="ml-2 text-sm font-medium text-gray-900">{{ __('Enable Nuclei scanner') }}</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div>
                <label for="scanner_nuclei_timeout_seconds" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nuclei Timeout Seconds') }}</label>
                <input type="number" min="60" max="1800" id="scanner_nuclei_timeout_seconds" name="settings[scanners][nuclei][timeout_seconds]"
                       value="{{ data_get($settings, 'scanners.nuclei.timeout_seconds', 300) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="scanner_nuclei_rate_limit" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nuclei Rate Limit (req/s)') }}</label>
                <input type="number" min="1" max="50" id="scanner_nuclei_rate_limit" name="settings[scanners][nuclei][rate_limit]"
                       value="{{ data_get($settings, 'scanners.nuclei.rate_limit', 10) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div class="xl:col-span-2">
                <label for="scanner_nuclei_severities" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Nuclei Severities') }}</label>
                <input type="text" id="scanner_nuclei_severities" name="settings[scanners][nuclei][severities]"
                       value="{{ data_get($settings, 'scanners.nuclei.severities', 'low,medium,high,critical') }}"
                       placeholder="low,medium,high,critical"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
        </div>

        <div class="flex items-center">
            <input type="hidden" name="settings[scanners][nuclei][include_cves]" value="0">
            <input id="scanner_nuclei_include_cves" type="checkbox" name="settings[scanners][nuclei][include_cves]" value="1"
                   @checked(data_get($settings, 'scanners.nuclei.include_cves', false))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="scanner_nuclei_include_cves" class="ml-2 text-sm font-medium text-gray-900">{{ __('Include HTTP CVE templates as part of the Nuclei profile') }}</label>
        </div>

        <p class="text-sm text-gray-500">{{ __('Default Nuclei coverage now uses a curated enrichment-safe template subset for exposures, misconfigurations, exposed panels, technology fingerprints and SSL/TLS checks. CVE templates remain optional and are handled in a more restricted profile because the full HTTP CVE corpus is too heavy for routine enrichment.') }}</p>
    </div>

    <div class="border-t pt-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('Threat Classification Rules') }}</h3>
        <p class="mt-2 text-sm text-gray-500">{{ __('Use these rules to decide which discovered host findings should automatically create asset threats. This is kept generic so future enrichers and scanners such as Nmap, Nikto, OpenVAS or Nuclei can feed the same pipeline.') }}</p>
    </div>

    <div class="mb-6 flex items-center">
        <input type="hidden" name="settings[threat_rules][enabled]" value="0">
        <input id="threat_rules_enabled" type="checkbox" name="settings[threat_rules][enabled]" value="1"
               @checked(data_get($settings, 'threat_rules.enabled', false))
               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
        <label for="threat_rules_enabled" class="ml-2 text-sm font-medium text-gray-900">{{ __('Enable automatic threat creation from discovered host findings') }}</label>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 mb-6 space-y-4">
        <div class="flex items-center">
            <input type="hidden" name="settings[threat_rules][open_port][enabled]" value="0">
            <input id="open_port_rule_enabled" type="checkbox" name="settings[threat_rules][open_port][enabled]" value="1"
                   @checked(data_get($settings, 'threat_rules.open_port.enabled', false))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="open_port_rule_enabled" class="ml-2 text-sm font-medium text-gray-900">{{ __('Classify open port findings as threats') }}</label>
        </div>

        <div class="flex items-center">
            <input type="hidden" name="settings[threat_rules][open_port][only_if_not_allowed]" value="0">
            <input id="open_port_only_if_not_allowed" type="checkbox" name="settings[threat_rules][open_port][only_if_not_allowed]" value="1"
                   @checked(data_get($settings, 'threat_rules.open_port.only_if_not_allowed', true))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="open_port_only_if_not_allowed" class="ml-2 text-sm font-medium text-gray-900">{{ __('Only create a threat if the port is not allowed on the linked asset') }}</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="open_port_probability" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Probability') }}</label>
                <input type="number" min="1" max="5" id="open_port_probability" name="settings[threat_rules][open_port][probability]"
                       value="{{ data_get($settings, 'threat_rules.open_port.probability', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="open_port_availability_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Availability Impact') }}</label>
                <input type="number" min="1" max="5" id="open_port_availability_impact" name="settings[threat_rules][open_port][availability_impact]"
                       value="{{ data_get($settings, 'threat_rules.open_port.availability_impact', 2) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="open_port_integrity_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Integrity Impact') }}</label>
                <input type="number" min="1" max="5" id="open_port_integrity_impact" name="settings[threat_rules][open_port][integrity_impact]"
                       value="{{ data_get($settings, 'threat_rules.open_port.integrity_impact', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="open_port_confidentiality_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Confidentiality Impact') }}</label>
                <input type="number" min="1" max="5" id="open_port_confidentiality_impact" name="settings[threat_rules][open_port][confidentiality_impact]"
                       value="{{ data_get($settings, 'threat_rules.open_port.confidentiality_impact', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 mb-6 space-y-4">
        <div class="flex items-center">
            <input type="hidden" name="settings[threat_rules][cve_detected][enabled]" value="0">
            <input id="cve_rule_enabled" type="checkbox" name="settings[threat_rules][cve_detected][enabled]" value="1"
                   @checked(data_get($settings, 'threat_rules.cve_detected.enabled', false))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="cve_rule_enabled" class="ml-2 text-sm font-medium text-gray-900">{{ __('Classify CVE findings as threats') }}</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="cve_min_cvss" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Minimum CVSS') }}</label>
                <input type="number" step="0.1" min="0" max="10" id="cve_min_cvss" name="settings[threat_rules][cve_detected][min_cvss]"
                       value="{{ data_get($settings, 'threat_rules.cve_detected.min_cvss', 7.0) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="cve_min_severity" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Minimum Severity') }}</label>
                <select id="cve_min_severity" name="settings[threat_rules][cve_detected][min_severity]"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">{{ __('No severity threshold') }}</option>
                    @foreach(['low', 'medium', 'high', 'critical'] as $severity)
                        <option value="{{ $severity }}" @selected((string) data_get($settings, 'threat_rules.cve_detected.min_severity', '') === $severity)>
                            {{ \Illuminate\Support\Str::of($severity)->title() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="cve_probability" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Probability') }}</label>
                <input type="number" min="1" max="5" id="cve_probability" name="settings[threat_rules][cve_detected][probability]"
                       value="{{ data_get($settings, 'threat_rules.cve_detected.probability', 4) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="cve_availability_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Availability Impact') }}</label>
                <input type="number" min="1" max="5" id="cve_availability_impact" name="settings[threat_rules][cve_detected][availability_impact]"
                       value="{{ data_get($settings, 'threat_rules.cve_detected.availability_impact', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="cve_integrity_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Integrity Impact') }}</label>
                <input type="number" min="1" max="5" id="cve_integrity_impact" name="settings[threat_rules][cve_detected][integrity_impact]"
                       value="{{ data_get($settings, 'threat_rules.cve_detected.integrity_impact', 4) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="cve_confidentiality_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Confidentiality Impact') }}</label>
                <input type="number" min="1" max="5" id="cve_confidentiality_impact" name="settings[threat_rules][cve_detected][confidentiality_impact]"
                       value="{{ data_get($settings, 'threat_rules.cve_detected.confidentiality_impact', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 p-4 mb-6 space-y-4">
        <div class="flex items-center">
            <input type="hidden" name="settings[threat_rules][web_issue][enabled]" value="0">
            <input id="web_issue_rule_enabled" type="checkbox" name="settings[threat_rules][web_issue][enabled]" value="1"
                   @checked(data_get($settings, 'threat_rules.web_issue.enabled', false))
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="web_issue_rule_enabled" class="ml-2 text-sm font-medium text-gray-900">{{ __('Classify web scanner findings as threats') }}</label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="web_issue_min_severity" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Minimum Severity') }}</label>
                <select id="web_issue_min_severity" name="settings[threat_rules][web_issue][min_severity]"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">{{ __('No severity threshold') }}</option>
                    @foreach(['low', 'medium', 'high', 'critical'] as $severity)
                        <option value="{{ $severity }}" @selected((string) data_get($settings, 'threat_rules.web_issue.min_severity', 'medium') === $severity)>
                            {{ \Illuminate\Support\Str::of($severity)->title() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="web_issue_probability" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Probability') }}</label>
                <input type="number" min="1" max="5" id="web_issue_probability" name="settings[threat_rules][web_issue][probability]"
                       value="{{ data_get($settings, 'threat_rules.web_issue.probability', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="web_issue_availability_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Availability Impact') }}</label>
                <input type="number" min="1" max="5" id="web_issue_availability_impact" name="settings[threat_rules][web_issue][availability_impact]"
                       value="{{ data_get($settings, 'threat_rules.web_issue.availability_impact', 2) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="web_issue_integrity_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Integrity Impact') }}</label>
                <input type="number" min="1" max="5" id="web_issue_integrity_impact" name="settings[threat_rules][web_issue][integrity_impact]"
                       value="{{ data_get($settings, 'threat_rules.web_issue.integrity_impact', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
            <div>
                <label for="web_issue_confidentiality_impact" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Confidentiality Impact') }}</label>
                <input type="number" min="1" max="5" id="web_issue_confidentiality_impact" name="settings[threat_rules][web_issue][confidentiality_impact]"
                       value="{{ data_get($settings, 'threat_rules.web_issue.confidentiality_impact', 3) }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>
        </div>
    </div>

    <div class="mb-6 flex items-center">
        <input type="hidden" name="settings[auto_create_assets]" value="0">
        <input id="auto_create_assets" type="checkbox" name="settings[auto_create_assets]" value="1" x-model="autoCreate"
               @checked(old('settings.auto_create_assets', data_get($scope?->settings, 'auto_create_assets', false)))
               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
        <label for="auto_create_assets" class="ml-2 text-sm font-medium text-gray-900">{{ __('Auto-create new assets when a host is confirmed active') }}</label>
    </div>

    <div x-show="autoCreate">
        <div class="mb-6">
            <label for="auto_create_asset_type_id" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Auto-created Asset Type') }}</label>
            <select id="auto_create_asset_type_id" name="settings[auto_create_asset_type_id]"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                <option value="">{{ __('Select asset type') }}</option>
                @foreach($assetTypes as $assetType)
                    <option value="{{ $assetType->id }}" @selected((string) data_get($settings, 'auto_create_asset_type_id') === (string) $assetType->id)>{{ $assetType->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-6">
            <label for="auto_create_manager_id" class="block mb-2 text-sm font-medium text-gray-900">{{ __('Auto-created Asset Manager') }}</label>
            <select id="auto_create_manager_id" name="settings[auto_create_manager_id]"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                <option value="">{{ __('Select manager') }}</option>
                @foreach($managers as $manager)
                    <option value="{{ $manager->id }}" @selected((string) data_get($settings, 'auto_create_manager_id') === (string) $manager->id)>{{ $manager->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
            {{ __('Save') }}
        </button>
        <a href="{{ route('attack-surface-scopes.index') }}"
           class="text-white bg-slate-700 hover:bg-slate-800 focus:ring-4 focus:ring-slate-300 font-medium rounded-lg text-sm px-5 py-2.5">
            {{ __('Cancel') }}
        </a>
    </div>
</div>
