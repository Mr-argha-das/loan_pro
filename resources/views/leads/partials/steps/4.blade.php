@php $customer = $lead->customer; @endphp

<x-section title="Personal Information" icon="bi-person-lines-fill" description="Identity details as per the customer's official documents.">
    <div class="row g-3">
        <div class="col-md-4"><x-input name="date_of_birth" label="Date of birth" type="date" :value="$customer?->date_of_birth?->toDateString()" /></div>
        <div class="col-md-4">
            <x-select name="gender" label="Gender" :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']"
                      :value="$customer?->gender" placeholder="Select gender" />
        </div>
        <div class="col-md-4">
            <x-select name="marital_status" label="Marital status"
                      :options="['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed']"
                      :value="$customer?->marital_status" placeholder="Select status" />
        </div>

        <div class="col-md-4"><x-input name="father_or_spouse_name" label="Father / Spouse name" :value="$customer?->father_or_spouse_name" /></div>
        <div class="col-md-4"><x-input name="pan_number" label="PAN number" :value="$customer?->pan_number" maxlength="10" placeholder="ABCDE1234F" help="Format: ABCDE1234F" /></div>
        <div class="col-md-4"><x-input name="aadhaar_number" label="Aadhaar number" :value="$customer?->aadhaar_number" maxlength="12" placeholder="12 digit number" /></div>

        <div class="col-md-3"><x-input name="nationality" label="Nationality" :value="$customer?->nationality ?? 'Indian'" /></div>
        <div class="col-md-3"><x-input name="city" label="City" :value="$customer?->city" /></div>
        <div class="col-md-3"><x-input name="state" label="State" :value="$customer?->state" /></div>
        <div class="col-md-3"><x-input name="pincode" label="Pincode" :value="$customer?->pincode" maxlength="6" /></div>

        <div class="col-12">
            <x-textarea name="address" label="Residential address" rows="2" :value="$customer?->address" />
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
