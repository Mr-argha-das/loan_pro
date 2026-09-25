<?php

namespace App\Models;

use App\Concerns\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Filterable;
    use HasFactory;
    protected $filterDateColumn = 'attendance_date';

    protected $table = 'attendances';

    protected $fillable = [
        'employee_id', 'attendance_date', 'check_in_at', 'check_out_at', 'worked_minutes',
        'late_minutes', 'status', 'work_mode', 'remarks', 'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function workedHours(): string
    {
        return sprintf('%dh %02dm', intdiv($this->worked_minutes, 60), $this->worked_minutes % 60);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'late' => 'warning',
            'half_day' => 'info',
            'leave' => 'primary',
            'holiday' => 'secondary',
            default => 'danger',
        };
    }
}
