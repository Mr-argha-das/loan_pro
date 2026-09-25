@extends('layouts.app')

@section('title', 'Reports')
@section('page-header', true)
@section('page-title', 'Reports & Analytics')
@section('page-subtitle', 'Ten operational reports with date, employee, product, category, lender, status and city filters.')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Reports</li>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="row g-3">
                @foreach ($reports as $key => $name)
                    <div class="col-md-6">
                        <a href="{{ route('reports.show', $key) }}" class="lp-report-card text-decoration-none">
                            <span class="lp-report-card__icon">
                                <i class="bi {{ ['lead-summary' => 'bi-funnel', 'lead-conversion' => 'bi-graph-up-arrow', 'customer' => 'bi-people', 'loan-application' => 'bi-clipboard-data', 'loan-disbursement' => 'bi-cash-stack', 'insurance' => 'bi-shield-check', 'invoice' => 'bi-receipt', 'payment-collection' => 'bi-wallet2', 'employee-performance' => 'bi-person-check', 'attendance' => 'bi-calendar-check'][$key] ?? 'bi-file-earmark-bar-graph' }}"></i>
                            </span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $name }}</div>
                                <div class="text-muted" style="font-size:.76rem">Filters, Excel &amp; PDF export, print ready</div>
                            </div>
                            <i class="bi bi-arrow-right text-muted"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-lg-4">
            <x-card title="Available filters" icon="bi-funnel">
                <div class="d-flex flex-wrap gap-1">
                    @foreach (['Date range', 'Employee', 'Product', 'Category', 'Lender', 'Status', 'City'] as $filter)
                        <span class="lp-badge bg-primary-subtle text-primary bg-opacity-10">{{ $filter }}</span>
                    @endforeach
                </div>
                <div class="lp-divider"></div>
                <ul class="small text-muted ps-3 mb-0">
                    <li class="mb-2">Every report respects your role — employees only see data they own.</li>
                    <li class="mb-2">Excel exports use the same filters you see on screen.</li>
                    <li>PDF output is print ready with company branding.</li>
                </ul>
            </x-card>
        </div>
    </div>
@endsection
