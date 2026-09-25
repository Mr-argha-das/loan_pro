<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasStatusHistory;
use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanApplication extends Model
{
    use Filterable;
    use Auditable;
    use HasFactory;
    use HasStatusHistory;
    use SoftDeletes;
    protected $filterDateColumn = 'created_at';

    protected $fillable = [
        'application_code', 'lead_id', 'customer_id', 'product_id', 'product_category_id',
        'product_subcategory_id', 'application_status_id', 'status', 'loan_amount', 'tenure_months',
        'roi', 'apr', 'emi', 'processing_fee', 'sanctioned_amount', 'disbursed_amount', 'sanction_date',
        'expected_disbursement_date', 'primary_lender_id', 'preferred_bank', 'employment_type',
        'monthly_income', 'existing_emi', 'credit_score', 'is_document_verified', 'verification_status',
        'rejection_reason', 'notes', 'assigned_to', 'created_by', 'submitted_at', 'verified_at',
        'approved_at', 'rejected_at', 'disbursed_at', 'closed_at',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'roi' => 'decimal:3',
        'apr' => 'decimal:3',
        'emi' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'sanctioned_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'monthly_income' => 'decimal:2',
        'existing_emi' => 'decimal:2',
        'is_document_verified' => 'boolean',
        'sanction_date' => 'date',
        'expected_disbursement_date' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id');
    }

    public function applicationStatus(): BelongsTo
    {
        return $this->belongsTo(ApplicationStatus::class);
    }

    public function primaryLender(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'primary_lender_id');
    }

    /** Shorthand for the sanctioned lender. */
    public function lender(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'primary_lender_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id')
            ->where('application_type', 'loan')
            ->orderBy('created_at');
    }

    public function lenders(): HasMany
    {
        return $this->hasMany(LoanLender::class);
    }

    public function lenderSubmissions(): HasMany
    {
        return $this->hasMany(LenderApplicationDetail::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function remarks(): MorphMany
    {
        return $this->morphMany(Remark::class, 'remarkable')->latest();
    }

    public function scopeOwnedBy($query, ?User $user)
    {
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('loan_applications.created_by', $user->id)
                ->orWhere('loan_applications.assigned_to', $user->id);
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('application_code', 'like', "%{$term}%")
                ->orWhereHas('customer', function (Builder $c) use ($term) {
                    $c->where('name', 'like', "%{$term}%")->orWhere('mobile', 'like', "%{$term}%");
                });
        });
    }

    public function isLoan(): bool
    {
        return true;
    }
}
