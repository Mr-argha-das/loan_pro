<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class ChangeApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyPermission(['applications.update', 'applications.approve']);
    }

    public function rules(): array
    {
        return [
            'status_slug' => ['required', 'exists:application_statuses,slug'],
            'note' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'sanctioned_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
