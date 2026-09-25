<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected CodeGeneratorService $codes,
        protected NotificationService $notifications,
    ) {
    }

    public function create(array $data, array $items, User $actor): Invoice
    {
        return DB::transaction(function () use ($data, $items, $actor) {
            $invoice = Invoice::query()->create([
                'invoice_number' => $this->codes->invoice(),
                'customer_id' => $data['customer_id'],
                'lead_id' => $data['lead_id'] ?? null,
                'loan_application_id' => $data['loan_application_id'] ?? null,
                'title' => $data['title'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(15)->toDateString(),
                'discount' => $data['discount'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 18,
                'status' => $data['status'] ?? 'draft',
                'place_of_supply' => $data['place_of_supply'] ?? null,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncItems($invoice, $items);

            if ($invoice->status === 'issued') {
                $this->markIssued($invoice, $actor);
            }

            return $invoice->refresh();
        });
    }

    public function update(Invoice $invoice, array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($invoice, $data, $items) {
            $invoice->fill([
                'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                'lead_id' => $data['lead_id'] ?? $invoice->lead_id,
                'loan_application_id' => $data['loan_application_id'] ?? $invoice->loan_application_id,
                'title' => $data['title'] ?? $invoice->title,
                'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'discount' => $data['discount'] ?? $invoice->discount,
                'tax_rate' => $data['tax_rate'] ?? $invoice->tax_rate,
                'status' => $data['status'] ?? $invoice->status,
                'place_of_supply' => $data['place_of_supply'] ?? $invoice->place_of_supply,
                'notes' => $data['notes'] ?? $invoice->notes,
                'terms' => $data['terms'] ?? $invoice->terms,
            ])->save();

            $this->syncItems($invoice, $items);

            return $invoice->refresh();
        });
    }

    public function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();
        $sort = 0;

        foreach ($items as $item) {
            if (empty($item['description'])) {
                continue;
            }

            $line = new InvoiceItem([
                'description' => $item['description'],
                'hsn_code' => $item['hsn_code'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? 'nos',
                'unit_price' => $item['unit_price'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'tax_rate' => $item['tax_rate'] ?? 0,
                'sort_order' => $sort++,
            ]);

            $line->computeLineTotal();
            $invoice->items()->save($line);
        }

        $invoice->recalculate();
        $invoice->save();

        app(PaymentService::class)->refreshInvoiceStatus($invoice);
    }

    public function markIssued(Invoice $invoice, User $actor): Invoice
    {
        $invoice->forceFill([
            'status' => $invoice->status === 'draft' ? 'issued' : $invoice->status,
            'issued_by' => $actor->id,
            'issued_at' => now(),
        ])->save();

        $this->notifications->send(
            [$invoice->customer->assignedEmployee, $invoice->creator],
            'invoice-generated',
            'Invoice generated',
            sprintf('Invoice %s raised for %s', $invoice->invoice_number, $invoice->customer->name),
            ['url' => route('invoices.show', $invoice), 'actor_id' => $actor->id]
        );

        return $invoice->refresh();
    }

    /**
     * Invoice KPIs for the list header. Employees only ever see their own book.
     */
    public function stats(?User $user = null): array
    {
        $scope = fn () => Invoice::query()->when($user && ! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id));

        return [
            'count' => $scope()->count(),
            'invoiced' => (float) $scope()->sum('total'),
            'collected' => (float) $scope()->sum('paid_amount'),
            'outstanding' => (float) $scope()->sum('balance_amount'),
            'overdue_count' => $scope()->where('status', 'overdue')->count(),
        ];
    }

    public function defaultTerms(): string
    {
        return "1. Payment is due within 15 days of the invoice date.\n2. Please quote the invoice number with your payment.\n3. Services once rendered are non-refundable.";
    }

    public function customerOptions()
    {
        return Customer::query()->orderBy('name')->limit(500)->get(['id', 'name', 'mobile']);
    }
}
