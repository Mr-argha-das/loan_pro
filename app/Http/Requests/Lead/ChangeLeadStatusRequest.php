<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class ChangeLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('leads.update');
    }

    public function rules(): array
    {
        return [
            'lead_status_id' => ['required', 'exists:lead_statuses,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
