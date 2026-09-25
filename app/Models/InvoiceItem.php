<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'description', 'hsn_code', 'quantity', 'unit', 'unit_price', 'discount',
        'tax_rate', 'tax_amount', 'line_total', 'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:3',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function computeLineTotal(): void
    {
        $base = (float) $this->quantity * (float) $this->unit_price - (float) $this->discount;
        $this->tax_amount = round($base * (float) $this->tax_rate / 100, 2);
        $this->line_total = round($base + $this->tax_amount, 2);
    }
}
