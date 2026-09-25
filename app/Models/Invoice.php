<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use Filterable;
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    protected $filterDateColumn = 'invoice_date';

    protected $fillable = [
        'invoice_number', 'customer_id', 'lead_id', 'loan_application_id', 'title', 'invoice_date',
        'due_date', 'subtotal', 'discount', 'tax_rate', 'tax_amount', 'total', 'paid_amount',
        'balance_amount', 'status', 'place_of_supply', 'notes', 'terms', 'created_by', 'issued_by',
        'issued_at', 'paid_at', 'cancelled_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:3',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeOwnedBy($query, ?User $user)
    {
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('invoice_number', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%"));
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && ! in_array($this->status, ['paid', 'cancelled'], true);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'partially_paid' => 'info',
            'issued' => 'primary',
            'overdue' => 'danger',
            'cancelled' => 'secondary',
            default => 'warning',
        };
    }

    public function recalculate(): void
    {
        $this->subtotal = round((float) $this->items()->sum('line_total'), 2);
        $taxable = max(0, $this->subtotal - (float) $this->discount);
        $this->tax_amount = round($taxable * (float) $this->tax_rate / 100, 2);
        $this->total = round($taxable + $this->tax_amount, 2);
        $this->paid_amount = round((float) $this->payments()->where('status', 'completed')->sum('amount'), 2);
        $this->balance_amount = round((float) $this->total - (float) $this->paid_amount, 2);

        if ($this->balance_amount <= 0 && (float) $this->total > 0) {
            $this->status = 'paid';
            $this->paid_at ??= now();
        } elseif ((float) $this->paid_amount > 0) {
            $this->status = 'partially_paid';
        } elseif ($this->status !== 'cancelled' && $this->status !== 'draft') {
            $this->status = $this->isOverdue() ? 'overdue' : 'issued';
        }
    }
}
