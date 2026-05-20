<?php

namespace App\Http\Requests;

use App\Domain\ThreatMonitoring\Enums\IntegrationProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationRequest extends FormRequest
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
        ];

        if ($provider === IntegrationProvider::MICROSOFT_GRAPH->value) {
            $rules['credentials.tenant_id'] = ['required', 'string', 'max:255'];
            $rules['credentials.client_id'] = ['required', 'string', 'max:255'];
            $rules['credentials.client_secret'] = ['required', 'string', 'max:4000'];
        }

        if ($provider === IntegrationProvider::SHODAN->value) {
            $rules['credentials.api_key'] = ['required', 'string', 'max:4000'];
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
}
