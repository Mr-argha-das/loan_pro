<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('disbursements.manage');
    }

    public function rules(): array
    {
        return [
            'loan_application_id' => ['required', 'exists:loan_applications,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'lender_id' => ['nullable', 'exists:lenders,id'],
            'loan_lender_id' => ['nullable', 'exists:loan_lenders,id'],
            'approved_amount' => ['required', 'numeric', 'min:1'],
            'disbursed_amount' => ['required', 'numeric', 'min:0'],
            'disbursement_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'utr_number' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:40'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'mode' => ['nullable', Rule::in(['neft', 'rtgs', 'imps', 'upi', 'cheque', 'cash', 'other'])],
            'status' => ['nullable', Rule::in(['pending', 'processing', 'completed', 'failed', 'cancelled'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
