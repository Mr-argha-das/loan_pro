<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /** Header search across the modules the signed-in user can see. */
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if (strlen($term) < 2) {
            return response()->json(['success' => true, 'groups' => []]);
        }

        $user = $request->user();
        $groups = [];

        if ($user->hasPermissionTo('leads.view')) {
            $groups[] = [
                'label' => 'Leads',
                'icon' => 'bi-funnel',
                'items' => Lead::query()->ownedBy($user)->search($term)->with('customer')
                    ->limit(5)->get()
                    ->map(fn (Lead $lead) => [
                        'title' => $lead->lead_code,
                        'subtitle' => $lead->customer?->name.' • '.\App\Support\Format::money($lead->loan_amount),
                        'url' => route('leads.show', $lead),
                        'badge' => \App\Support\StatusBadge::label($lead->status),
                        'color' => $lead->leadStatus?->color ?? 'secondary',
                    ])->all(),
            ];
        }

        if ($user->hasPermissionTo('customers.view')) {
            $groups[] = [
                'label' => 'Customers',
                'icon' => 'bi-people',
                'items' => Customer::query()->ownedBy($user)->search($term)
                    ->limit(5)->get()
                    ->map(fn (Customer $customer) => [
                        'title' => $customer->name,
                        'subtitle' => $customer->mobile.' • '.($customer->city ?: 'N/A'),
                        'url' => route('customers.show', $customer),
                        'badge' => \App\Support\StatusBadge::label($customer->kyc_status),
                        'color' => \App\Support\StatusBadge::color($customer->kyc_status),
                    ])->all(),
            ];
        }

        if ($user->hasPermissionTo('applications.view')) {
            $groups[] = [
                'label' => 'Applications',
                'icon' => 'bi-clipboard-check',
                'items' => LoanApplication::query()->ownedBy($user)->search($term)->with('customer')
                    ->limit(5)->get()
                    ->map(fn (LoanApplication $application) => [
                        'title' => $application->application_code,
                        'subtitle' => $application->customer?->name.' • '.\App\Support\Format::money($application->loan_amount),
                        'url' => route('applications.show', $application),
                        'badge' => \App\Support\StatusBadge::label($application->status),
                        'color' => \App\Support\StatusBadge::color($application->status),
                    ])->all(),
            ];
        }

        if ($user->hasPermissionTo('invoices.view')) {
            $groups[] = [
                'label' => 'Invoices',
                'icon' => 'bi-receipt',
                'items' => Invoice::query()->ownedBy($user)
                    ->where(fn ($q) => $q->where('invoice_number', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")))
                    ->limit(5)->get()
                    ->map(fn (Invoice $invoice) => [
                        'title' => $invoice->invoice_number,
                        'subtitle' => $invoice->customer?->name.' • '.\App\Support\Format::money($invoice->total),
                        'url' => route('invoices.show', $invoice),
                        'badge' => \App\Support\StatusBadge::label($invoice->status),
                        'color' => $invoice->statusColor(),
                    ])->all(),
            ];
        }

        if ($user->hasPermissionTo('payments.view')) {
            $groups[] = [
                'label' => 'Payments',
                'icon' => 'bi-cash-stack',
                'items' => Payment::query()->ownedBy($user)
                    ->where(fn ($q) => $q->where('payment_code', 'like', "%{$term}%")
                        ->orWhere('transaction_id', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%")))
                    ->limit(5)->get()
                    ->map(fn (Payment $payment) => [
                        'title' => $payment->payment_code,
                        'subtitle' => $payment->customer?->name.' • '.\App\Support\Format::money($payment->amount),
                        'url' => route('payments.show', $payment),
                        'badge' => \App\Support\StatusBadge::label($payment->status),
                        'color' => $payment->statusColor(),
                    ])->all(),
            ];
        }

        return response()->json([
            'success' => true,
            'groups' => array_values(array_filter($groups, fn ($group) => ! empty($group['items']))),
        ]);
    }
}
