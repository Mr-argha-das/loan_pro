@extends('layouts.app')

@php
    $isEdit = $customer->exists;
    $action = $isEdit ? route('customers.update', $customer) : route('customers.store');
    $primaryAddress = $customer->addresses->firstWhere('is_primary', true) ?? $customer->addresses->first();
@endphp

@section('title', $isEdit ? 'Edit '.$customer->name : 'Add Customer')
@section('page-header', true)
@section('page-title', $isEdit ? 'Edit customer' : 'Add new customer')
@section('page-subtitle', $isEdit ? $customer->customer_code : 'Create a customer profile used across leads, applications and invoices.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
    <form method="POST" action="{{ $action }}" novalidate>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-section title="Identity details" icon="bi-person-vcard" description="Primary KYC identity of the customer.">
                    <div class="row g-3">
                        <div class="col-md-6"><x-input name="name" label="Full name" :value="$customer->name" required /></div>
                        <div class="col-md-3"><x-input name="mobile" label="Mobile number" :value="$customer->mobile" required maxlength="10" /></div>
                        <div class="col-md-3"><x-input name="alternate_mobile" label="Alternate mobile" :value="$customer->alternate_mobile" maxlength="10" /></div>

                        <div class="col-md-4"><x-input name="email" label="Email address" type="email" :value="$customer->email" /></div>
                        <div class="col-md-4"><x-input name="date_of_birth" label="Date of birth" type="date" :value="$customer->date_of_birth?->toDateString()" /></div>
                        <div class="col-md-4">
                            <x-select name="gender" label="Gender" :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']"
                                      :value="$customer->gender" placeholder="Select gender" />
                        </div>

                        <div class="col-md-4">
                            <x-select name="marital_status" label="Marital status"
                                      :options="['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed']"
                                      :value="$customer->marital_status" placeholder="Select status" />
                        </div>
                        <div class="col-md-4"><x-input name="father_or_spouse_name" label="Father / Spouse name" :value="$customer->father_or_spouse_name" /></div>
                        <div class="col-md-4"><x-input name="occupation" label="Occupation" :value="$customer->occupation" /></div>

                        <div class="col-md-4"><x-input name="pan_number" label="PAN number" :value="$customer->pan_number" maxlength="10" placeholder="ABCDE1234F" /></div>
                        <div class="col-md-4"><x-input name="aadhaar_number" label="Aadhaar number" :value="$customer->aadhaar_number" maxlength="12" /></div>
                        <div class="col-md-4"><x-input name="nationality" label="Nationality" :value="$customer->nationality ?? 'Indian'" /></div>
                    </div>
                </x-section>

                <x-section title="Professional &amp; income" icon="bi-briefcase" description="Employment details used for eligibility and credit checks.">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-select name="employment_type_id" label="Employment type" :options="$employmentTypes" :value="$customer->employment_type_id"
                                      label-key="name" placeholder="Select type" />
                        </div>
                        <div class="col-md-4"><x-input name="company_name" label="Employer / Business" :value="$customer->company_name" /></div>
                        <div class="col-md-4"><x-input name="designation" label="Designation" :value="$customer->designation" /></div>

                        <div class="col-md-3"><x-input name="monthly_income" label="Monthly income" type="number" step="1000" :value="$customer->monthly_income" icon="bi-currency-rupee" /></div>
                        <div class="col-md-3"><x-input name="annual_income" label="Annual income" type="number" step="1000" :value="$customer->annual_income" icon="bi-currency-rupee" /></div>
                        <div class="col-md-3"><x-input name="work_experience_years" label="Experience (years)" type="number" :value="$customer->work_experience_years" /></div>
                        <div class="col-md-3"><x-input name="pincode" label="Pincode" :value="$customer->pincode" maxlength="6" /></div>

                        <div class="col-12"><x-textarea name="office_address" label="Office address" rows="2" :value="$customer->office_address" /></div>
                    </div>
                </x-section>

                <x-section title="Address" icon="bi-geo-alt" description="Residential address on record.">
                    <div class="row g-3">
                        <div class="col-md-4"><x-input name="city" label="City" :value="$customer->city" /></div>
                        <div class="col-md-4"><x-input name="state" label="State" :value="$customer->state" /></div>
                        <div class="col-md-4"><x-input name="pincode" label="Pincode" :value="$customer->pincode" maxlength="6" /></div>
                        <div class="col-12">
                            <x-textarea name="address" label="Residential address" rows="2"
                                        :value="$customer->address ?? $primaryAddress?->address_line" />
                        </div>
                    </div>
                </x-section>
            </div>

            <div class="col-lg-4">
                <x-section title="Ownership" icon="bi-person-badge" description="Who owns this relationship internally.">
                    <div class="mb-3">
                        <label class="form-label" for="assigned_employee_id">Relationship manager</label>
                        <select class="form-select" id="assigned_employee_id" name="assigned_employee_id">
                            <option value="">Assign to me</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((int) $customer->assigned_employee_id === $employee->id)>
                                    {{ $employee->name }}{{ ($employee->designation ?? null) ? ' — '.$employee->designation : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="customer_type">Customer type<span class="req">*</span></label>
                        <select class="form-select" id="customer_type" name="customer_type" required>
                            @foreach (['individual' => 'Individual', 'company' => 'Company', 'proprietorship' => 'Proprietorship', 'partnership' => 'Partnership'] as $value => $label)
                                <option value="{{ $value }}" @selected($customer->customer_type === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive', 'blacklisted' => 'Blacklisted']"
                                      :value="$customer->status ?? 'active'" />
                        </div>
                        <div class="col-6">
                            <x-select name="kyc_status" label="KYC" :options="['pending' => 'Pending', 'in_progress' => 'In progress', 'verified' => 'Verified', 'rejected' => 'Rejected']"
                                      :value="$customer->kyc_status ?? 'pending'" />
                        </div>
                    </div>

                    <div class="mt-3">
                        <x-textarea name="notes" label="Internal notes" rows="3" :value="$customer->notes" />
                    </div>
                </x-section>

                <div class="d-flex gap-2">
                    <a href="{{ $isEdit ? route('customers.show', $customer) : route('customers.index') }}" class="btn btn-outline-secondary flex-grow-1">Cancel</a>
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2"></i> {{ $isEdit ? 'Update customer' : 'Save customer' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection
