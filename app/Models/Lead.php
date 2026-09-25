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

class Lead extends Model
{
    use Filterable;
    use Auditable;
    use HasFactory;
    use HasStatusHistory;
    use SoftDeletes;
    protected $filterDateColumn = 'created_at';

    public const STEPS = [
        1 => 'Customer Details',
        2 => 'Basic Information',
        3 => 'OTP Verification',
        4 => 'Personal Information',
        5 => 'Professional Information',
        6 => 'KYC & Documents',
        7 => 'Loan & Insurance',
        8 => 'Lender Selection',
        9 => 'Selected Lenders',
        10 => 'Lead Summary',
    ];

    protected $fillable = [
        'lead_code', 'customer_id', 'product_id', 'product_category_id', 'product_subcategory_id',
        'lead_source_id', 'lead_status_id', 'employment_type_id', 'assigned_to', 'created_by',
        'lead_type', 'customer_type', 'preferred_contact_method', 'preferred_contact_time', 'priority',
        'loan_amount', 'tenure_months', 'preferred_bank', 'monthly_income', 'existing_emi',
        'credit_score', 'is_otp_verified', 'otp_verified_at', 'otp_channel', 'otp_attempts',
        'current_step', 'is_draft', 'is_converted', 'status', 'verification_status',
        'expected_commission', 'notes', 'last_activity_at', 'converted_at',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'monthly_income' => 'decimal:2',
        'existing_emi' => 'decimal:2',
        'expected_commission' => 'decimal:2',
        'is_otp_verified' => 'boolean',
        'is_draft' => 'boolean',
        'is_converted' => 'boolean',
        'otp_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /* ------------------------------------------------------------------ relations */

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

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function leadStatus(): BelongsTo
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
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
        return $this->hasMany(LeadStatusHistory::class)->orderBy('created_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LeadAssignment::class)->latest('assigned_at');
    }

    public function selectedLenders(): HasMany
    {
        return $this->hasMany(LoanLender::class)->where('is_selected', true);
    }

    public function lenders(): HasMany
    {
        return $this->hasMany(LoanLender::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function remarks(): MorphMany
    {
        return $this->morphMany(Remark::class, 'remarkable')->latest();
    }

    /* ------------------------------------------------------------------ scopes */

    public function scopeOwnedBy($query, ?User $user)
    {
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('leads.created_by', $user->id)->orWhere('leads.assigned_to', $user->id);
        });
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('is_draft', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('lead_code', 'like', "%{$term}%")
                ->orWhereHas('customer', function (Builder $c) use ($term) {
                    $c->where('name', 'like', "%{$term}%")
                        ->orWhere('mobile', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }

    /* ------------------------------------------------------------------ helpers */

    public function stepName(): string
    {
        return self::STEPS[$this->current_step] ?? 'Lead Summary';
    }

    public function progressPercent(): int
    {
        return (int) round(($this->current_step / count(self::STEPS)) * 100);
    }

    public function stageStateFor(string $stage): string
    {
        return $this->statusHistories->firstWhere('stage', $stage)?->state ?? 'upcoming';
    }
}
