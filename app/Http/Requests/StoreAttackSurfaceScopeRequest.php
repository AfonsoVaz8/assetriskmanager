<?php

namespace App\Http\Requests;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use App\Models\Integration;
use App\Enums\AttackSurfaceScopeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttackSurfaceScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_column(AttackSurfaceScopeType::cases(), 'value'))],
            'justification' => ['nullable', 'string', 'max:5000'],
            'scope_definition.cidr' => ['nullable', 'string', 'max:32'],
            'scope_definition.hostname' => ['nullable', 'string', 'max:255'],
            'scope_definition.domain' => ['nullable', 'string', 'max:255'],
            'settings.discovery.method' => ['nullable', Rule::in(['tcp_only', 'icmp_only', 'tcp_icmp'])],
            'settings.discovery.icmp_timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.ports' => ['nullable', 'string', 'max:255'],
            'settings.timeout_seconds' => ['nullable', 'numeric', 'min:0.2', 'max:3'],
            'settings.auto_create_assets' => ['nullable', 'boolean'],
            'settings.auto_create_asset_type_id' => ['nullable', 'integer', 'exists:asset_types,id'],
            'settings.auto_create_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'settings.enrichment_integration_id' => ['nullable', 'integer', 'exists:integrations,id'],
            'settings.scanners.nmap.enabled' => ['nullable', 'boolean'],
            'settings.scanners.nmap.ssl_cert' => ['nullable', 'boolean'],
            'settings.scanners.nmap.timeout_seconds' => ['nullable', 'integer', 'min:15', 'max:300'],
            'settings.scanners.nikto.enabled' => ['nullable', 'boolean'],
            'settings.scanners.nikto.timeout_seconds' => ['nullable', 'integer', 'min:30', 'max:900'],
            'settings.scanners.nikto.max_time_seconds' => ['nullable', 'integer', 'min:30', 'max:900'],
            'settings.scanners.nikto.plugins' => ['nullable', 'string', 'max:255'],
            'settings.scanners.nikto.tuning' => ['nullable', 'string', 'max:50'],
            'settings.scanners.nuclei.enabled' => ['nullable', 'boolean'],
            'settings.scanners.nuclei.timeout_seconds' => ['nullable', 'integer', 'min:60', 'max:1800'],
            'settings.scanners.nuclei.rate_limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'settings.scanners.nuclei.include_cves' => ['nullable', 'boolean'],
            'settings.scanners.nuclei.severities' => ['nullable', 'string', 'max:100'],
            'settings.threat_rules.enabled' => ['nullable', 'boolean'],
            'settings.threat_rules.open_port.enabled' => ['nullable', 'boolean'],
            'settings.threat_rules.open_port.only_if_not_allowed' => ['nullable', 'boolean'],
            'settings.threat_rules.open_port.probability' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.open_port.availability_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.open_port.integrity_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.open_port.confidentiality_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.cve_detected.enabled' => ['nullable', 'boolean'],
            'settings.threat_rules.cve_detected.min_cvss' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'settings.threat_rules.cve_detected.min_severity' => ['nullable', Rule::in(['', 'low', 'medium', 'moderate', 'high', 'critical'])],
            'settings.threat_rules.cve_detected.probability' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.cve_detected.availability_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.cve_detected.integrity_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.cve_detected.confidentiality_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.web_issue.enabled' => ['nullable', 'boolean'],
            'settings.threat_rules.web_issue.min_severity' => ['nullable', Rule::in(['', 'low', 'medium', 'moderate', 'high', 'critical'])],
            'settings.threat_rules.web_issue.probability' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.web_issue.availability_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.web_issue.integrity_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
            'settings.threat_rules.web_issue.confidentiality_impact' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') === AttackSurfaceScopeType::CIDR_RANGE->value) {
                $cidr = trim((string) data_get($this->input('scope_definition', []), 'cidr', ''));

                if ($cidr === '') {
                    $validator->errors()->add('scope_definition.cidr', __('A CIDR range is required for CIDR range scopes.'));
                } elseif (!$this->isValidIpv4Cidr($cidr)) {
                    $validator->errors()->add('scope_definition.cidr', __('Use a valid IPv4 CIDR between /24 and /32. Example: 203.0.113.0/24 or 203.0.113.25/32.'));
                } elseif (!$this->isCanonicalNetworkCidr($cidr)) {
                    $validator->errors()->add('scope_definition.cidr', __('Use the network base address for the chosen prefix. Example: use 52.156.202.0/24 for the full /24 range, or 52.156.202.75/32 for a single IP.'));
                }
            }

            if ($this->input('type') === AttackSurfaceScopeType::HOSTNAME_TARGET->value) {
                $hostname = trim((string) data_get($this->input('scope_definition', []), 'hostname', ''));

                if ($hostname === '') {
                    $validator->errors()->add('scope_definition.hostname', __('A hostname or domain is required for hostname target scopes.'));
                } elseif (!$this->isValidHostname($hostname)) {
                    $validator->errors()->add('scope_definition.hostname', __('Use a valid hostname or domain, such as www.isep.portal.ipp.pt.'));
                }
            }

            if ($this->input('type') === AttackSurfaceScopeType::DOMAIN_EXPANSION->value) {
                $domain = trim((string) data_get($this->input('scope_definition', []), 'domain', ''));

                if ($domain === '') {
                    $validator->errors()->add('scope_definition.domain', __('A base domain is required for domain expansion scopes.'));
                } elseif (!$this->isValidHostname($domain)) {
                    $validator->errors()->add('scope_definition.domain', __('Use a valid base domain, such as ipp.pt or portal.ipp.pt.'));
                }
            }

            if ($this->boolean('settings.auto_create_assets')) {
                if (blank(data_get($this->input('settings', []), 'auto_create_asset_type_id'))) {
                    $validator->errors()->add('settings.auto_create_asset_type_id', __('An asset type is required when auto-creation is enabled.'));
                }

                if (blank(data_get($this->input('settings', []), 'auto_create_manager_id'))) {
                    $validator->errors()->add('settings.auto_create_manager_id', __('A manager is required when auto-creation is enabled.'));
                }
            }

            $integrationId = data_get($this->input('settings', []), 'enrichment_integration_id');

            if (filled($integrationId)) {
                $integration = Integration::query()->find($integrationId);

                if (!$integration || $integration->provider === IntegrationProvider::MICROSOFT_GRAPH->value) {
                    $validator->errors()->add('settings.enrichment_integration_id', __('Select a valid non-Graph integration for enrichment.'));
                }
            }
        });
    }

    private function isValidIpv4Cidr(string $cidr): bool
    {
        if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $cidr)) {
            return false;
        }

        [$ip, $prefix] = explode('/', $cidr);
        $prefix = (int) $prefix;

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            && $prefix >= 24
            && $prefix <= 32;
    }

    private function isCanonicalNetworkCidr(string $cidr): bool
    {
        [$ip, $prefix] = explode('/', $cidr);
        $prefix = (int) $prefix;

        if ($prefix === 32) {
            return true;
        }

        $networkLong = ip2long($ip);

        if ($networkLong === false) {
            return false;
        }

        $mask = -1 << (32 - $prefix);
        $network = long2ip($networkLong & $mask);

        return $network === $ip;
    }

    private function isValidHostname(string $hostname): bool
    {
        $hostname = rtrim(strtolower($hostname), '.');

        if ($hostname === '' || strlen($hostname) > 253) {
            return false;
        }

        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return false;
        }

        return str_contains($hostname, '.');
    }
}
