<?php

namespace App\Http\Requests;

use App\Domain\IncidentManagement\Enums\IncidentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                IncidentStatus::IN_PROGRESS->value,
                IncidentStatus::RESOLVED->value,
                IncidentStatus::DISMISSED->value,
            ])],
            'resolution_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
