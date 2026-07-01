<?php

namespace App\Http\Requests;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $provider = $this->input('provider');

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'provider' => ['required', Rule::in(array_column(IntegrationProvider::cases(), 'value'))],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'settings.trusted_countries' => ['nullable', 'string', 'max:255'],
            'settings.trusted_networks' => ['nullable', 'string', 'max:2000'],
            'settings.detect_external_signins' => ['nullable', 'boolean'],
            'settings.detect_unusual_countries' => ['nullable', 'boolean'],
            'settings.notify_high_severity' => ['nullable', 'boolean'],
            'settings.retention.enabled' => ['nullable', 'boolean'],
            'settings.retention.days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'settings.retention.cleanup_interval_hours' => ['nullable', 'integer', 'min:1', 'max:8760'],
        ];

        if ($provider === IntegrationProvider::MICROSOFT_GRAPH->value) {
            $rules['credentials.tenant_id'] = ['required', 'string', 'max:255'];
            $rules['credentials.client_id'] = ['required', 'string', 'max:255'];
            $rules['credentials.client_secret'] = ['nullable', 'string', 'max:4000'];
            foreach ($this->analysisPolicyRules() as $key => $rule) {
                $rules[$key] = $rule;
            }
        }

        if ($provider === IntegrationProvider::SHODAN->value) {
            $rules['credentials.api_key'] = ['nullable', 'string', 'max:4000'];
            $rules['credentials.base_url'] = ['nullable', 'url', 'max:255'];
        }

        if ($provider === IntegrationProvider::GENERIC_IP_INTELLIGENCE->value) {
            $rules['credentials.base_url'] = ['required', 'url', 'max:255'];
            $rules['credentials.api_key'] = ['nullable', 'string', 'max:4000'];
            $rules['credentials.auth_mode'] = ['required', Rule::in(['none', 'bearer', 'header', 'query'])];
            $rules['credentials.auth_parameter_name'] = ['nullable', 'string', 'max:100'];
            $rules['credentials.ip_parameter_name'] = ['nullable', 'string', 'max:100'];
            $rules['credentials.response_root_path'] = ['nullable', 'string', 'max:255'];
            $rules['credentials.source_label'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    private function analysisPolicyRules(): array
    {
        $keys = [
            'severity_high_threshold',
            'severity_medium_threshold',
            'successful_signin_points',
            'successful_external_signin_points',
            'ip_reputation_high_points',
            'ip_reputation_nonzero_points',
            'unusual_country_points',
            'sensitive_application_points',
            'single_factor_auth_points',
            'conditional_access_not_applied_points',
            'missing_os_context_points',
            'missing_browser_context_points',
            'failure_then_success_points',
            'graph_high_risk_points',
            'graph_medium_risk_points',
            'graph_low_risk_points',
            'account_at_risk_points',
            'confirmed_compromise_points',
        ];

        return collect($keys)
            ->mapWithKeys(fn (string $key): array => [
                "settings.analysis_policy.{$key}" => ['nullable', 'integer', 'min:0', 'max:1000'],
            ])
            ->all();
    }
}
