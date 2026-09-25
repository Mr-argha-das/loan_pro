@php $customer = $lead->customer ?? null; @endphp

<x-section title="Customer Details" icon="bi-person-vcard" description="Start with the customer's basic identity details.">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="customer_id">Existing customer</label>
            <select class="form-select" id="customer_id" name="customer_id">
                <option value="">New customer</option>
                @foreach ($customers as $option)
                    <option value="{{ $option->id }}" @selected(($customer?->id ?? old('customer_id')) == $option->id)>
                        {{ $option->name }} — {{ $option->mobile }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Select a customer to reuse their profile, or leave blank to create a new one.</div>
        </div>

        <div class="col-md-4">
            <x-input name="name" label="Full name" :value="$customer?->name" required placeholder="e.g. Rohit Sharma" />
        </div>
        <div class="col-md-4">
            <x-input name="mobile" label="Mobile number" :value="$customer?->mobile" required maxlength="10"
                     help="A 6-digit OTP will be sent to this number in the next step." />
        </div>

        <div class="col-md-4">
            <x-input name="email" label="Email address" type="email" :value="$customer?->email" placeholder="name@example.com" />
        </div>
        <div class="col-md-4">
            <x-select name="customer_type" label="Customer type" required
                      :options="['new' => 'New customer', 'existing' => 'Existing customer']"
                      :value="$lead?->customer_type ?? 'new'" placeholder="Select type" />
        </div>
        <div class="col-md-4">
            <x-select name="assigned_to" label="Assign to" :options="$employees" :value="$lead?->assigned_to"
                      label-key="name" placeholder="Select relationship manager" />
        </div>
    </div>
</x-section>

<div class="d-flex justify-content-between">
    <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i> Cancel</a>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" data-save-draft><i class="bi bi-save"></i> Save as Draft</button>
        <button type="submit" class="btn btn-primary">Save &amp; Continue <i class="bi bi-arrow-right"></i></button>
    </div>
</div>
