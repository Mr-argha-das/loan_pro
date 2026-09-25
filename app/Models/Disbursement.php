<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disbursement extends Model
{
    use Filterable;
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    protected $filterDateColumn = 'disbursement_date';

    protected $fillable = [
        'disbursement_code', 'loan_application_id', 'customer_id', 'lender_id', 'loan_lender_id',
        'approved_amount', 'disbursed_amount', 'disbursement_date', 'reference_number', 'utr_number',
        'bank_name', 'bank_account_number', 'bank_ifsc', 'processing_fee', 'other_charges',
        'net_amount', 'status', 'mode', 'remarks', 'created_by', 'approved_by', 'completed_at',
    ];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'disbursement_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class, 'loan_application_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class);
    }

    public function loanLender(): BelongsTo
    {
        return $this->belongsTo(LoanLender::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function recalculateNet(): void
    {
        $this->net_amount = (float) $this->disbursed_amount - (float) $this->processing_fee - (float) $this->other_charges;
    }
}
