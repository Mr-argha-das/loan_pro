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
        'check_in_lat', 'check_in_lng', 'check_out_lat', 'check_out_lng',
        'check_in_location', 'check_out_location',
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

    /**
     * Place name of the check-in ('in') or check-out ('out').
     *
     * Rows captured before the place name was stored fall back to an offline
     * lookup (configured office / closest known city), so the register always
     * shows a readable location.
     */
    public function place(string $moment = 'in'): ?string
    {
        $lat = $moment === 'out' ? $this->check_out_lat : $this->check_in_lat;
        $lng = $moment === 'out' ? $this->check_out_lng : $this->check_in_lng;
        $stored = $moment === 'out' ? $this->check_out_location : $this->check_in_location;

        if ($stored) {
            return $stored;
        }

        if ($lat === null || $lng === null) {
            return null;
        }

        return app(\App\Services\LocationService::class)->name((float) $lat, (float) $lng, allowGeocode: false);
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
