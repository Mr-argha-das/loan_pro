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

class Customer extends Model
{
    use Filterable;
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    protected $filterDateColumn = 'created_at';

    protected $fillable = [
        'customer_code', 'name', 'mobile', 'alternate_mobile', 'email', 'date_of_birth', 'gender',
        'marital_status', 'father_or_spouse_name', 'pan_number', 'aadhaar_number', 'nationality',
        'customer_type', 'occupation', 'city', 'state', 'pincode', 'address', 'employment_type_id',
        'company_name', 'designation', 'monthly_income', 'annual_income', 'work_experience_years',
        'office_address', 'assigned_employee_id', 'created_by', 'status', 'kyc_status', 'notes',
        'last_contacted_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'monthly_income' => 'decimal:2',
        'annual_income' => 'decimal:2',
        'last_contacted_at' => 'datetime',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function professionalDetails(): HasMany
    {
        return $this->hasMany(CustomerProfessionalDetail::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** Alias used by the CRM profile tabs. */
    public function applications(): HasMany
    {
        return $this->hasMany(LoanApplication::class, 'customer_id');
    }

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }

    public function insuranceApplications(): HasMany
    {
        return $this->hasMany(InsuranceApplication::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function remarks(): MorphMany
    {
        return $this->morphMany(Remark::class, 'remarkable')->latest();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOwnedBy($query, ?User $user)
    {
        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('created_by', $user->id)->orWhere('assigned_employee_id', $user->id);
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('mobile', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('customer_code', 'like', "%{$term}%")
                ->orWhere('pan_number', 'like', "%{$term}%");
        });
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name));

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    public function applicationCount(): int
    {
        return $this->loanApplications()->count() + $this->insuranceApplications()->count();
    }
}
