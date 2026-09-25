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
        <div class="col-lg-7">
            <x-card title="Start from a lead" icon="bi-funnel" description="Applications created from a lead inherit the customer, lender and document data.">
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
                                <tr><td colspan="4"><x-empty-state icon="bi-funnel" title="No verified leads waiting" message="Verify a lead via OTP to convert it into an application." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="How conversion works" icon="bi-info-circle">
                <ol class="small text-muted mb-0 ps-3">
                    <li class="mb-2">A lead must be <strong>OTP verified</strong> with at least one lender shortlisted.</li>
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
