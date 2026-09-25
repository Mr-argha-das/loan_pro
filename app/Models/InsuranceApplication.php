<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasStatusHistory;
use Illuminate\Database\Eloquent\Builder;
use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceApplication extends Model
{
    use Auditable;
    use Filterable;
    use HasFactory;
    use HasStatusHistory;
    use SoftDeletes;

    protected $fillable = [
        'application_code', 'lead_id', 'customer_id', 'product_id', 'product_category_id',
        'product_subcategory_id', 'application_status_id', 'status', 'policy_number', 'sum_assured',
        'premium_amount', 'premium_frequency', 'policy_term_years', 'policy_start_date',
        'policy_end_date', 'nominee_name', 'nominee_relation', 'nominee_dob', 'commission_amount',
        'insurer_id', 'rejection_reason', 'notes', 'assigned_to', 'created_by', 'submitted_at',
        'approved_at', 'issued_at',
    ];

    protected $casts = [
        'sum_assured' => 'decimal:2',
        'premium_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'policy_start_date' => 'date',
        'policy_end_date' => 'date',
        'nominee_dob' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
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

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Lender::class, 'insurer_id');
    }

    /** Selected insurance plan (sub-category). */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProductSubcategory::class, 'product_subcategory_id');
    }

    /** Life / Health / General insurance type. */
    public function insuranceType(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id')
            ->where('application_type', 'insurance')
            ->orderBy('created_at');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function remarks(): MorphMany
    {
        return $this->morphMany(Remark::class, 'remarkable')->latest();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('application_code', 'like', "%{$term}%")
                ->orWhere('policy_number', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%"));
        });
    }

    public function scopeOwnedBy($query, ?User $user)
    {
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('insurance_applications.created_by', $user->id)
                ->orWhere('insurance_applications.assigned_to', $user->id);
        });
    }

    public function isLoan(): bool
    {
        return false;
    }
}
