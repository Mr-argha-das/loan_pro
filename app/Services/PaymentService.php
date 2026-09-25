<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected CodeGeneratorService $codes,
        protected NotificationService $notifications,
    ) {
    }

    public function record(array $data, User $actor): Payment
    {
        return DB::transaction(function () use ($data, $actor) {
            $payment = Payment::query()->create([
                'payment_code' => $this->codes->payment(),
                'invoice_id' => $data['invoice_id'] ?? null,
                'customer_id' => $data['customer_id'],
                'loan_application_id' => $data['loan_application_id'] ?? null,
                'amount' => $data['amount'],
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'received_by' => $data['received_by'] ?? $actor->id,
                'status' => $data['status'] ?? 'completed',
                'reference_number' => $data['reference_number'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'cheque_number' => $data['cheque_number'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $actor->id,
            ]);

            if ($payment->invoice) {
                $this->refreshInvoiceStatus($payment->invoice);
            }

            $this->notifications->send(
                [$payment->customer->assignedEmployee, $payment->invoice?->creator],
                'payment-received',
                'Payment received',
                sprintf('%s received %s from %s', $actor->name, \App\Support\Format::money($payment->amount), $payment->customer->name),
                ['url' => route('payments.show', $payment), 'actor_id' => $actor->id]
            );

            return $payment->refresh();
        });
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->fill($data)->save();

        if ($payment->invoice) {
            $this->refreshInvoiceStatus($payment->invoice);
        }

        return $payment->refresh();
    }

    public function refreshInvoiceStatus(Invoice $invoice): void
    {
        $invoice->refresh();
        $invoice->recalculate();

        if ($invoice->status === 'paid') {
            $invoice->paid_at ??= now();
        }

        $invoice->save();
    }

    public function stats(): array
    {
        return [
            'today' => Payment::query()->whereDate('payment_date', today())->sum('amount'),
            'month' => Payment::query()->whereMonth('payment_date', now()->month)->sum('amount'),
            'total' => Payment::query()->sum('amount'),
        ];
    }

    public function customerOptions()
    {
        return Customer::query()->orderBy('name')->limit(500)->get(['id', 'name', 'mobile']);
    }
}
