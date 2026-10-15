<?php

namespace App\Services;

use App\Models\Invoice;

class PaymentService
{
    /**
     * Recalculate an invoice's totals and paid status from its payments.
     */
    public function refreshInvoiceStatus(Invoice $invoice): void
    {
        $invoice->refresh();
        $invoice->recalculate();

        if ($invoice->status === 'paid') {
            $invoice->paid_at ??= now();
        }

        $invoice->save();
    }
}
