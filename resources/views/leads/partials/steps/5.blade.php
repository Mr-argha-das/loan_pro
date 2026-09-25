@php $customer = $lead->customer; @endphp

<x-section title="Professional Information" icon="bi-briefcase" description="Employment and income details used for eligibility checks.">
    <div class="row g-3">
        <div class="col-md-4">
            <x-select name="employment_type_id" label="Employment type" :options="$employmentTypes" :value="$customer?->employment_type_id"
                      placeholder="Select employment type" />
        </div>
        <div class="col-md-4"><x-input name="company_name" label="Employer / Business name" :value="$customer?->company_name" /></div>
        <div class="col-md-4"><x-input name="designation" label="Designation" :value="$customer?->designation" /></div>

        <div class="col-md-3">
            <x-input name="monthly_income" label="Monthly income" type="number" step="1000" :value="$customer?->monthly_income" icon="bi-currency-rupee" />
        </div>
        <div class="col-md-3">
            <x-input name="work_experience_years" label="Total experience (years)" type="number" :value="$customer?->work_experience_years" />
        </div>
        <div class="col-md-3">
            <x-input name="existing_emi" label="Existing EMI obligations" type="number" step="500" :value="$lead?->existing_emi" icon="bi-currency-rupee" />
        </div>
        <div class="col-md-3">
            <x-input name="credit_score" label="Credit score" type="number" min="300" max="900" :value="$lead?->credit_score" />
        </div>

        <div class="col-12">
            <x-textarea name="office_address" label="Office address" rows="2" :value="$customer?->office_address" />
        </div>
    </div>
</x-section>

<div class="d-flex justify-content-between">
    <button type="button" class="btn btn-outline-secondary" data-wizard-back><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </div>
</div>
