<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('leads.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'customer_type' => ['required', 'in:new,existing'],
            'lead_type' => ['required', 'in:fresh,existing,priority'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            // The product is chosen in wizard step 2 (Basic Information); step 1 only captures the customer.
            'product_id' => ['nullable', 'exists:products,id'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'product_subcategory_id' => ['nullable', 'exists:product_subcategories,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'customer name', 'mobile' => 'mobile number', 'assigned_to' => 'assigned employee'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mobile' => preg_replace('/\D+/', '', (string) $this->input('mobile'))]);
    }
}
