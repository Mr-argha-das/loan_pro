<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo($this->route('customer') ? 'customers.update' : 'customers.create');
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/', Rule::unique('customers', 'mobile')->ignore($customerId)],
            'alternate_mobile' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($customerId)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'father_or_spouse_name' => ['nullable', 'string', 'max:120'],
            'pan_number' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/', Rule::unique('customers', 'pan_number')->ignore($customerId)],
            'aadhaar_number' => ['nullable', 'digits:12'],
            'customer_type' => ['required', Rule::in(['individual', 'company', 'proprietorship', 'partnership'])],
            'occupation' => ['nullable', 'string', 'max:100'],
            'employment_type_id' => ['nullable', 'exists:employment_types,id'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'designation' => ['nullable', 'string', 'max:100'],
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'annual_income' => ['nullable', 'numeric', 'min:0'],
            'work_experience_years' => ['nullable', 'integer', 'between:0,60'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'pincode' => ['nullable', 'digits:6'],
            'address' => ['nullable', 'string', 'max:255'],
            'office_address' => ['nullable', 'string', 'max:255'],
            'assigned_employee_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blacklisted'])],
            'kyc_status' => ['nullable', Rule::in(['pending', 'in_progress', 'verified', 'rejected'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'mobile' => $this->filled('mobile') ? preg_replace('/\D+/', '', (string) $this->input('mobile')) : null,
            'alternate_mobile' => $this->filled('alternate_mobile') ? preg_replace('/\D+/', '', (string) $this->input('alternate_mobile')) : null,
            'pan_number' => $this->filled('pan_number') ? strtoupper((string) $this->input('pan_number')) : null,
            'aadhaar_number' => $this->filled('aadhaar_number') ? preg_replace('/\D+/', '', (string) $this->input('aadhaar_number')) : null,
        ], fn ($value) => $value !== null));
    }
}
