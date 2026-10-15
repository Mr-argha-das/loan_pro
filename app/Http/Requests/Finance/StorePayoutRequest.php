<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('payments.manage');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'payout_month' => ['required', 'date_format:Y-m'],
            'incentive_amount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::in(['unpaid', 'paid'])],
            'paid_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
