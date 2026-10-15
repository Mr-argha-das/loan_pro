<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A lender offer = one product a lender funds, with its rates and the monthly
 * income band it is offered to (min_monthly_income .. max_monthly_income).
 * Used for both create and update.
 */
class StoreLenderOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('lenders.manage');
    }

    protected function prepareForValidation(): void
    {
        $required = $this->input('required_documents');

        $this->merge([
            // Textarea input, one document per line, stored as a JSON array.
            'required_documents' => is_string($required)
                ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $required))))
                : null,
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'product_subcategory_id' => ['nullable', 'integer', 'exists:product_subcategories,id'],
            'product_name' => ['required', 'string', 'max:180'],
            'code' => ['nullable', 'string', 'max:60'],
            'loan_type' => ['required', 'in:secured,unsecured,insurance'],

            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'min_tenure_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'max_tenure_months' => ['nullable', 'integer', 'min:1', 'max:600'],

            'roi' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'apr' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'processing_fee_type' => ['required', 'in:percent,fixed'],
            'penal_charge' => ['nullable', 'numeric', 'min:0'],
            'penal_charge_type' => ['required', 'in:percent,fixed'],

            'min_credit_score' => ['nullable', 'integer', 'between:300,900'],
            'min_monthly_income' => ['nullable', 'numeric', 'min:0'],
            'max_monthly_income' => ['nullable', 'numeric', 'min:0'],

            'eligibility' => ['nullable', 'string', 'max:2000'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['string', 'max:120'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'is_featured' => ['boolean'],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $ranges = [
                ['min_amount', 'max_amount', 'Loan amount'],
                ['min_tenure_months', 'max_tenure_months', 'Tenure'],
                ['min_monthly_income', 'max_monthly_income', 'Monthly income band'],
            ];

            foreach ($ranges as [$from, $to, $label]) {
                if ($this->filled($from) && $this->filled($to)
                    && (float) $this->input($to) < (float) $this->input($from)) {
                    $v->errors()->add($to, "{$label} maximum must be greater than or equal to the minimum.");
                }
            }
        });
    }
}
