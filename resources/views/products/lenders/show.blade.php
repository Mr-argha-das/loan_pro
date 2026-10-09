@extends('layouts.app')

@section('title', $lender->name)
@section('page-header', true)
@section('page-title', $lender->name)
@section('page-subtitle', \App\Support\Format::titleCase($lender->lender_type).' · '.($lender->city ?? 'India').' · '.$lender->products->count().' offers')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('lenders.index') }}">Lenders</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $lender->name }}</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <x-card title="Lender profile" icon="bi-bank">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="lp-lender-card__logo" style="width:52px;height:52px;font-size:1rem">{{ $lender->code ?? strtoupper(substr($lender->name, 0, 2)) }}</span>
                    <div>
                        <div class="fw-semibold">{{ $lender->name }}</div>
                        <div class="text-muted small">{{ \App\Support\Format::titleCase($lender->lender_type) }}</div>
                        <div class="mt-1"><x-status-badge :status="$lender->status" /></div>
                    </div>
                </div>

                <div class="lp-kv"><span class="lp-kv__label">Online status</span><span class="lp-kv__value">{{ ucfirst($lender->online_status ?? 'offline') }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Contact person</span><span class="lp-kv__value">{{ $lender->contact_person ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Phone</span><span class="lp-kv__value">{{ $lender->contact_number ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Email</span><span class="lp-kv__value">{{ $lender->contact_email ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Website</span><span class="lp-kv__value">{{ $lender->website ?? '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Ticket size</span><span class="lp-kv__value">{{ \App\Support\Format::compactInr($lender->min_ticket_size) }} – {{ \App\Support\Format::compactInr($lender->max_ticket_size) }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Processing time</span><span class="lp-kv__value">{{ $lender->processing_time_days ? $lender->processing_time_days.' days' : '—' }}</span></div>
                <div class="lp-kv"><span class="lp-kv__label">Address</span><span class="lp-kv__value">{{ $lender->address ?? '—' }}</span></div>
                @if ($lender->remarks)
                    <div class="lp-divider"></div>
                    <p class="small text-muted mb-0">{{ $lender->remarks }}</p>
                @endif
            </x-card>
        </div>

        <div class="col-lg-8">
            <x-card title="Product offers" icon="bi-box-seam" description="Rates, amounts and the monthly income band each offer is shown to." :padding="false">
                @if (auth()->user()->hasPermissionTo('lenders.manage'))
                    <x-slot:actions>
                        <a href="{{ route('lenders.offers.create', $lender) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add offer</a>
                    </x-slot:actions>
                @endif
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">Category</th>
                                <th scope="col">Loan type</th>
                                <th scope="col">Amount range</th>
                                <th scope="col">Income band</th>
                                <th scope="col">ROI</th>
                                <th scope="col">Max tenor</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lender->products as $offer)
                                <tr>
                                    <td class="fw-semibold">{{ $offer->product_name }}</td>
                                    <td>{{ $offer->category?->name ?? '—' }}</td>
                                    <td>{{ \App\Support\Format::titleCase($offer->loan_type) }}</td>
                                    <td>{{ \App\Support\Format::compactInr($offer->min_amount) }} – {{ \App\Support\Format::compactInr($offer->max_amount) }}</td>
                                    <td>
                                        @if ($offer->min_monthly_income || $offer->max_monthly_income)
                                            {{ $offer->min_monthly_income ? \App\Support\Format::money($offer->min_monthly_income) : 'Any' }}
                                            –
                                            {{ $offer->max_monthly_income ? \App\Support\Format::money($offer->max_monthly_income) : 'Any' }}
                                        @else
                                            <span class="text-muted">No limit</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold">{{ $offer->roi !== null ? $offer->roi.'%' : '—' }}{{ $offer->apr !== null ? ' / APR '.$offer->apr.'%' : '' }}</td>
                                    <td>{{ $offer->max_tenure_months }} mo</td>
                                    <td><x-status-badge :status="$offer->status" /></td>
                                    <td class="text-end">
                                        @if (auth()->user()->hasPermissionTo('lenders.manage'))
                                            <a href="{{ route('lenders.offers.edit', [$lender, $offer]) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                            <form method="POST" action="{{ route('lenders.offers.destroy', [$lender, $offer]) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-light text-danger" title="Remove" data-confirm="Remove the {{ $offer->product_name }} offer?"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9"><x-empty-state icon="bi-box-seam" title="No offers configured" message="Add the products this lender funds, with the income band for each." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
@endsection
