<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NormalizeIpIntelligenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ip' => ['required', 'ip'],
            'raw_response' => ['required', 'array'],
            'source' => ['nullable', 'string', 'max:255'],
            'collected_at' => ['nullable', 'date'],
        ];
    }
}
