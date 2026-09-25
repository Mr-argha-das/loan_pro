<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfessionalDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'customer_id', 'employment_type_id', 'company_name', 'designation', 'industry',
        'monthly_income', 'annual_income', 'other_income', 'work_experience_years',
        'business_vintage_years', 'company_type', 'gst_number', 'office_address', 'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'monthly_income' => 'decimal:2',
        'annual_income' => 'decimal:2',
        'other_income' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }
}
