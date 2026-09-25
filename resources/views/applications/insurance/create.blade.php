@extends('layouts.app')

@section('title', 'New Insurance Application')
@section('page-header', true)
@section('page-title', 'New insurance application')
@section('page-subtitle', 'Capture the policy requirement; categories and plans come from the product master.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('insurance.index') }}">Insurance</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('insurance.store') }}" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <x-section title="Policy holder" icon="bi-person-vcard">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="customer_id">Customer<span class="req">*</span></label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">Select customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected((int) old('customer_id', $lead?->customer_id) === $customer->id)>
                                        {{ $customer->name }} — {{ $customer->mobile }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lead_id">Linked lead</label>
                            <select class="form-select" id="lead_id" name="lead_id">
                                <option value="">None</option>
                                @if ($lead)
                                    <option value="{{ $lead->id }}" selected>{{ $lead->lead_code }} — {{ $lead->customer?->name }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </x-section>

                <x-section title="Policy details" icon="bi-shield-check">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="product_category_id">Insurance type<span class="req">*</span></label>
                            <select class="form-select" id="product_category_id" name="product_category_id" required>
                                <option value="">Select type</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected((int) old('product_category_id') === $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="product_subcategory_id">Plan</label>
                            <select class="form-select" id="product_subcategory_id" name="product_subcategory_id">
                                <option value="">Select plan</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}" data-category="{{ $plan->product_category_id }}" @selected((int) old('product_subcategory_id') === $plan->id)>
                                        {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="insurer_id">Insurer</label>
                            <select class="form-select" id="insurer_id" name="insurer_id">
                                <option value="">Select insurer</option>
                                @foreach ($insurers as $insurer)
                                    <option value="{{ $insurer->id }}" @selected((int) old('insurer_id') === $insurer->id)>{{ $insurer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><x-input name="sum_assured" label="Sum assured" type="number" step="10000" required icon="bi-currency-rupee" /></div>
                        <div class="col-md-3"><x-input name="premium_amount" label="Premium" type="number" step="100" required icon="bi-currency-rupee" /></div>

                        <div class="col-md-4">
                            <x-select name="premium_frequency" label="Premium frequency" required
                                      :options="['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'half_yearly' => 'Half yearly', 'yearly' => 'Yearly', 'single' => 'Single premium']"
                                      :value="old('premium_frequency', 'yearly')" />
                        </div>
                        <div class="col-md-4"><x-input name="policy_term_years" label="Policy term (years)" type="number" min="1" max="50" required :value="old('policy_term_years', 20)" /></div>
                        <div class="col-md-4">
                            <label class="form-label" for="assigned_to">Assign to</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Me</option>
                                @foreach (App\Models\User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']) as $employee)
                                    <option value="{{ $employee->id }}" @selected((int) old('assigned_to') === $employee->id)>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-section>

                <x-section title="Nominee" icon="bi-people">
                    <div class="row g-3">
                        <div class="col-md-4"><x-input name="nominee_name" label="Nominee name" :value="old('nominee_name')" /></div>
                        <div class="col-md-4"><x-input name="nominee_relation" label="Relationship" :value="old('nominee_relation')" /></div>
                        <div class="col-md-4"><x-input name="nominee_dob" label="Nominee date of birth" type="date" :value="old('nominee_dob')" /></div>
                        <div class="col-12"><x-textarea name="notes" label="Notes" rows="2" :value="old('notes')" /></div>
                    </div>
                </x-section>
            </div>

            <div class="col-lg-4">
                <x-card title="Insurance product catalogue" icon="bi-diagram-3" description="Categories and plans are managed under Master Management.">
                    <div class="d-flex flex-column gap-2">
                        @foreach ($types as $type)
                            <div class="lp-kv mb-0">
                                <span class="lp-kv__label">{{ $type->name }}</span>
                                <span class="lp-kv__value">{{ $plans->where('product_category_id', $type->id)->count() }} plans</span>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('insurance.index') }}" class="btn btn-outline-secondary flex-grow-1">Cancel</a>
                    <button class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> Create application</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script type="module">
    const category = document.getElementById('product_category_id');
    const plan = document.getElementById('product_subcategory_id');

    function filterPlans() {
        const id = category.value;
        [...plan.options].forEach((option) => {
            if (!option.value) return;
            option.hidden = option.dataset.category !== id && id !== '';
        });
    }

    category?.addEventListener('change', filterPlans);
    filterPlans();
</script>
@endpush
