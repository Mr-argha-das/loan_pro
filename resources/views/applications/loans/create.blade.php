@extends('layouts.app')

@section('title', 'New Loan Application')
@section('page-header', true)
@section('page-title', 'New loan application')
@section('page-subtitle', 'Raise an application directly, or start from a verified lead.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('applications.index') }}">Loan Applications</a></li>
    <li class="breadcrumb-item active" aria-current="page">Create</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-12">
            <x-card title="Select customer" icon="bi-person-search" description="Type a name or mobile number to search. The application will be created for this customer.">
                <form method="GET" action="{{ route('applications.create') }}" id="customer-pick-form" class="row g-2 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label" for="customer-search">Customer</label>
                        <input type="text" class="form-control" id="customer-search" list="customer-options" autocomplete="off"
                               placeholder="Naam ya mobile number type karein..."
                               value="{{ $customer ? $customer->name.' · '.$customer->mobile : '' }}">
                        <datalist id="customer-options">
                            @foreach ($customers as $option)
                                <option value="{{ $option->name }} · {{ $option->mobile }}" data-id="{{ $option->id }}"></option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="customer_id" id="customer-id" value="{{ $customer?->id }}">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Show leads</button>
                    </div>
                </form>
            </x-card>
        </div>

        <div class="col-lg-7">
            <x-card title="Verified leads of this customer" icon="bi-funnel" description="Converting a lead creates the application and copies the customer, lender and document data.">
                @if (! $customer)
                    <x-empty-state icon="bi-person-search" title="Select a customer first" message="Search and choose a customer above to see their verified leads." />
                @else
                <div class="table-responsive">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Lead</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Amount</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leads as $lead)

                                <tr>
                                    <td class="fw-semibold">{{ $lead->lead_code }}</td>
                                    <td>{{ $lead->customer?->name }}</td>
                                    <td>{{ \App\Support\Format::money($lead->loan_amount) }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-primary">Convert <i class="bi bi-arrow-right"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4"><x-empty-state icon="bi-funnel" title="No verified leads waiting" message="Mark a lead as verified to convert it into an application." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @endif
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="How conversion works" icon="bi-info-circle">
                <ol class="small text-muted mb-0 ps-3">
                    <li class="mb-2">A lead must be <strong>verified</strong> with at least one lender shortlisted.</li>
                    <li class="mb-2">Converting creates the application, copies the customer snapshot and links shortlisted lenders.</li>
                    <li>Status history starts at <strong>Application Created</strong> and is never overwritten.</li>
                </ol>
                <div class="lp-divider"></div>
                <a href="{{ route('leads.index', ['verified_only' => 1]) }}" class="btn btn-light w-100">
                    <i class="bi bi-list-check"></i> View verified leads
                </a>
            </x-card>
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
    // Pick a customer from the searchable list: set its id and reload the page for that customer.
    const search = document.getElementById('customer-search');
    const hiddenId = document.getElementById('customer-id');
    const pickForm = document.getElementById('customer-pick-form');

    search?.addEventListener('change', () => {
        const option = [...document.querySelectorAll('#customer-options option')].find((o) => o.value === search.value);

        if (option) {
            hiddenId.value = option.dataset.id;
            pickForm.submit();
        } else if (search.value.trim() === '') {
            hiddenId.value = '';
        } else {
            LoanPro.toast('List mein se customer choose karein.', 'warning');
        }
    });
</script>
@endpush
