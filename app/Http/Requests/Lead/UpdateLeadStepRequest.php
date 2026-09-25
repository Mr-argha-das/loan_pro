<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates one wizard step at a time. Rules are resolved from the step number
 * so the flow can be extended without touching the controller.
 */
class UpdateLeadStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('leads.update');
    }

    public function rules(): array
    {
        $step = (int) $this->route('step', 1);

        return match ($step) {
            1 => [
                'name' => ['required', 'string', 'max:120'],
                'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
                'email' => ['nullable', 'email', 'max:150'],
            ],
            2 => [
                'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
                'product_id' => ['required', 'exists:products,id'],
                'product_category_id' => ['nullable', 'exists:product_categories,id'],
                'product_subcategory_id' => ['nullable', 'exists:product_subcategories,id'],
                'assigned_to' => ['nullable', 'exists:users,id'],
                'priority' => ['nullable', 'integer', 'between:1,3'],
                'preferred_contact_method' => ['nullable', Rule::in(['call', 'sms', 'whatsapp', 'email'])],
                'preferred_contact_time' => ['nullable', 'string', 'max:60'],
                'lead_type' => ['nullable', Rule::in(['fresh', 'existing', 'priority'])],
                'customer_type' => ['nullable', Rule::in(['new', 'existing'])],
            ],
            3 => [
                'otp' => ['nullable', 'digits:6'],
            ],
            4 => [
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
                'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
                'father_or_spouse_name' => ['nullable', 'string', 'max:120'],
                'pan_number' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
                'aadhaar_number' => ['nullable', 'digits:12'],
                'nationality' => ['nullable', 'string', 'max:60'],
                'city' => ['nullable', 'string', 'max:80'],
                'state' => ['nullable', 'string', 'max:80'],
                'pincode' => ['nullable', 'digits:6'],
                'address' => ['nullable', 'string', 'max:255'],
            ],
            5 => [
                'employment_type_id' => ['nullable', 'exists:employment_types,id'],
                'company_name' => ['nullable', 'string', 'max:150'],
                'designation' => ['nullable', 'string', 'max:100'],
                'monthly_income' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
                'annual_income' => ['nullable', 'numeric', 'min:0'],
                'work_experience_years' => ['nullable', 'integer', 'between:0,60'],
                'office_address' => ['nullable', 'string', 'max:255'],
                'existing_emi' => ['nullable', 'numeric', 'min:0'],
            ],
            6 => [
                // Documents are optional at this step ("upload what you already have");
                // rows without a file are ignored by the controller.
                'documents' => ['nullable', 'array'],
                'documents.*.document_type_id' => ['nullable', 'exists:document_types,id'],
                'documents.*.file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
                'documents.*.issued_number' => ['nullable', 'string', 'max:60'],
                'documents.*.expires_at' => ['nullable', 'date'],
            ],
            7 => [
                'loan_amount' => ['required', 'numeric', 'min:1000', 'max:200000000'],
                'tenure_months' => ['required', 'integer', 'between:3,480'],
                'credit_score' => ['nullable', 'integer', 'between:300,900'],
                'preferred_bank' => ['nullable', 'string', 'max:100'],
                'product_category_id' => ['nullable', 'exists:product_categories,id'],
                'product_subcategory_id' => ['nullable', 'exists:product_subcategories,id'],
            ],
            8, 9 => [
                'lenders' => ['nullable', 'array'],
                'lenders.*.lender_id' => ['required_with:lenders', 'exists:lenders,id'],
                'lenders.*.lender_product_id' => ['nullable', 'exists:lender_products,id'],
                'lenders.*.loan_amount' => ['nullable', 'numeric', 'min:0'],
                'lenders.*.roi' => ['nullable', 'numeric', 'between:0,60'],
                'lenders.*.tenure_months' => ['nullable', 'integer', 'between:1,480'],
                'lenders.*.processing_fee' => ['nullable', 'numeric', 'min:0'],
                'lenders.*.penal_charge' => ['nullable', 'numeric', 'min:0'],
                'lenders.*.required_documents' => ['nullable', 'array'],
            ],
            10 => [
                'notes' => ['nullable', 'string', 'max:2000'],
                'is_draft' => ['nullable', 'boolean'],
            ],
            default => [],
        };
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('pan_number') && $this->filled('pan_number')) {
            $this->merge(['pan_number' => strtoupper((string) $this->input('pan_number'))]);
        }

        if ($this->filled('aadhaar_number')) {
            $this->merge(['aadhaar_number' => preg_replace('/\D+/', '', (string) $this->input('aadhaar_number'))]);
        }
    }
}
