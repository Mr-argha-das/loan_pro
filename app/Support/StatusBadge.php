<?php

namespace App\Support;

/**
 * Single source of truth for status colours across the whole product so every
 * module renders the same badge for the same business state.
 */
class StatusBadge
{
    public const MAP = [
        // generic
        'active' => 'success',
        'inactive' => 'secondary',
        'online' => 'success',
        'offline' => 'secondary',

        // leads / applications
        'draft' => 'secondary',
        'new' => 'primary',
        'created' => 'info',
        'contacted' => 'info',
        'verified' => 'success',
        'lender-selected' => 'primary',
        'application-created' => 'info',
        'under-review' => 'warning',
        'in-progress' => 'warning',
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'disbursement-pending' => 'warning',
        'disbursed' => 'success',
        'closed' => 'dark',
        'cancelled' => 'secondary',

        // documents
        'uploaded' => 'info',

        // disbursements
        'processing' => 'warning',
        'completed' => 'success',
        'failed' => 'danger',

        // invoices
        'issued' => 'primary',
        'partially-paid' => 'info',
        'partially_paid' => 'info',
        'paid' => 'success',
        'overdue' => 'danger',

        // payments
        'refunded' => 'secondary',

        // attendance
        'present' => 'success',
        'late' => 'warning',
        'half-day' => 'info',
        'half_day' => 'info',
        'absent' => 'danger',
        'leave' => 'primary',
        'holiday' => 'secondary',

        // severity
        'info' => 'info',
        'success' => 'success',
        'warning' => 'warning',
        'error' => 'danger',
    ];

    /** Lead priority scale (1 = highest) shared by every module. */
    public const PRIORITY = [
        '1' => ['label' => 'High', 'color' => 'danger'],
        '2' => ['label' => 'Medium', 'color' => 'warning'],
        '3' => ['label' => 'Low', 'color' => 'secondary'],
    ];

    public static function color(?string $status, string $fallback = 'secondary'): string
    {
        $key = strtolower(str_replace([' ', '_'], '-', (string) $status));

        if (isset(self::PRIORITY[$key])) {
            return self::PRIORITY[$key]['color'];
        }

        return self::MAP[$key] ?? $fallback;
    }

    public static function label(?string $status): string
    {
        if (! $status) {
            return '—';
        }

        $key = strtolower(str_replace([' ', '_'], '-', $status));

        if (isset(self::PRIORITY[$key])) {
            return self::PRIORITY[$key]['label'];
        }

        return ucwords(str_replace(['-', '_'], ' ', $status));
    }
}
