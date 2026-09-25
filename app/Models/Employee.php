<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use Filterable;
    use Auditable;
    use HasFactory;
    use SoftDeletes;
    protected $filterDateColumn = 'joining_date';

    protected $fillable = [
        'user_id', 'employee_code', 'department_id', 'designation_id', 'reporting_to', 'joining_date',
        'employment_status', 'profile_photo_path', 'mobile', 'alternate_mobile', 'address', 'city',
        'state', 'pincode', 'emergency_contact_name', 'emergency_contact_number', 'bank_name',
        'bank_account_number', 'bank_ifsc', 'monthly_target', 'remarks',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'monthly_target' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_to');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('employment_status', 'active');
    }

    public function photoUrl(): ?string
    {
        return $this->profile_photo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }

    public function todayAttendance(): ?Attendance
    {
        return $this->attendances()->whereDate('attendance_date', today())->first();
    }
}
