<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('applications.create');
    }

    public function rules(): array
    {
        return [
            'lead_id' => ['required', 'exists:leads,id'],
            'primary_lender_id' => ['nullable', 'exists:lenders,id'],
            'loan_amount' => ['nullable', 'numeric', 'min:1000'],
            'tenure_months' => ['nullable', 'integer', 'between:3,480'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
