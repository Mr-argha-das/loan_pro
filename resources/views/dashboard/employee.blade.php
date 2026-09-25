@extends('layouts.app')

@section('title', 'My Workspace')
@section('page-header', true)
@section('page-title', 'My Workspace')
@section('page-subtitle', 'Everything assigned to you — leads, applications, collections and payouts.')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active" aria-current="page">My Workspace</li>
@endsection

@section('page-actions')
    <div class="d-flex gap-2">
        <a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create Lead</a>
        <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm"><i class="bi bi-speedometer2"></i> Full Dashboard</a>
    </div>
@endsection

@section('content')
    <div class="lp-welcome mb-4">
        <div>
            <div class="lp-welcome__title">
                <span class="lp-welcome__icon"><i class="bi bi-person-workspace"></i></span>
                Hello {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}
            </div>
            <div class="lp-welcome__sub">
                {{ $pendingTasks->count() }} task(s) need your attention today ·
                {{ number_format($kpis['total_customers']) }} customers in your book
            </div>
        </div>
        <a href="{{ route('leads.index', ['assigned_to' => auth()->id()]) }}" class="btn btn-light btn-sm">
            <i class="bi bi-list-task"></i> My leads
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <x-stat-card label="My Leads" :value="number_format($kpis['total_leads'])" icon="bi-funnel" tone="primary"
                         :trend="$kpis['total_leads_trend']" :href="route('leads.index', ['assigned_to' => auth()->id()])" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="My Customers" :value="number_format($kpis['total_customers'])" icon="bi-people" tone="blue" :href="route('customers.index')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="My Applications" :value="number_format($kpis['active_applications'])" icon="bi-clipboard-data" tone="green" :href="route('applications.index')" />
        </div>
        <div class="col-6 col-lg-3">
            <x-stat-card label="Pending Payments" :value="\App\Support\Format::compactInr($kpis['pending_payments'])" icon="bi-exclamation-circle" tone="orange" :href="route('payments.index')" />
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-card title="Pending tasks" icon="bi-list-check" :padding="false"
                    description="Leads in your queue that still need action.">
                @include('dashboard.partials.lead-rows', ['leads' => $pendingTasks, 'emptyTitle' => 'No pending tasks', 'emptyMessage' => 'Your queue is clear — great work.'])
            </x-card>

            <x-card title="My applications" icon="bi-clipboard-data" class="mt-4" :padding="false">
                @include('dashboard.partials.application-rows', ['applications' => $myApplications])
            </x-card>
        </div>

        <div class="col-lg-5">
            <x-card title="Quick actions" icon="bi-lightning-charge">
                <div class="row g-2">
                    <div class="col-6"><a href="{{ route('leads.create') }}" class="lp-quick-action"><i class="bi bi-person-plus"></i><span>New Lead</span></a></div>
                    <div class="col-6"><a href="{{ route('customers.index') }}" class="lp-quick-action"><i class="bi bi-people"></i><span>Customers</span></a></div>
                    <div class="col-6"><a href="{{ route('applications.index') }}" class="lp-quick-action"><i class="bi bi-file-earmark-text"></i><span>Applications</span></a></div>
                    <div class="col-6"><a href="{{ route('documents.index') }}" class="lp-quick-action"><i class="bi bi-cloud-arrow-up"></i><span>Documents</span></a></div>
                    <div class="col-6"><a href="{{ route('attendance.index') }}" class="lp-quick-action"><i class="bi bi-clock-history"></i><span>Attendance</span></a></div>
                    <div class="col-6"><a href="{{ route('notifications.index') }}" class="lp-quick-action"><i class="bi bi-bell"></i><span>Notifications</span></a></div>
                </div>
            </x-card>

            <x-card title="My disbursements" icon="bi-cash-stack" class="mt-4" :padding="false">
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr><th scope="col">Disbursement</th><th scope="col">Amount</th><th scope="col">Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($myDisbursements as $disbursement)
                                <tr>
                                    <td>
                                        <a href="{{ route('disbursements.show', $disbursement) }}" class="fw-semibold">{{ $disbursement->disbursement_code }}</a>
                                        <div class="text-muted" style="font-size:.73rem">{{ $disbursement->customer?->name }}</div>
                                    </td>
                                    <td class="fw-semibold">{{ \App\Support\Format::money($disbursement->disbursed_amount) }}</td>
                                    <td><x-status-badge :status="$disbursement->status" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><x-empty-state icon="bi-cash-stack" title="No disbursements yet" message="Payouts you process will appear here." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Recent invoices & payments" icon="bi-receipt" class="mt-4" :padding="false">
                <div class="lp-table-wrap">
                    <table class="lp-table">
                        <thead>
                            <tr><th scope="col">Reference</th><th scope="col">Amount</th><th scope="col">Date</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($myInvoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold">{{ $invoice->invoice_number }}</a>
                                        <div class="text-muted" style="font-size:.73rem">Invoice · {{ $invoice->customer?->name }}</div>
                                    </td>
                                    <td class="fw-semibold">{{ \App\Support\Format::money($invoice->total_amount) }}</td>
                                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($invoice->invoice_date) }}</td>
                                </tr>
                            @empty
                                @foreach ($myPayments as $payment)
                                    <tr>
                                        <td><a href="{{ route('payments.show', $payment) }}" class="fw-semibold">{{ $payment->payment_code }}</a>
                                            <div class="text-muted" style="font-size:.73rem">Payment · {{ \App\Support\Format::titleCase($payment->mode) }}</div>
                                        </td>
                                        <td class="fw-semibold">{{ \App\Support\Format::money($payment->amount) }}</td>
                                        <td class="text-muted text-nowrap">{{ \App\Support\Format::date($payment->payment_date) }}</td>
                                    </tr>
                                @endforeach
                            @endforelse
                            @if ($myInvoices->isEmpty() && $myPayments->isEmpty())
                                <tr><td colspan="3"><x-empty-state icon="bi-receipt" title="Nothing billed yet" message="Invoices and payments you create show up here." /></td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
@endsection
