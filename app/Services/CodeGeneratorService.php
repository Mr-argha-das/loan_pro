<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sequential, human readable business identifiers.
 *
 * Series (continuous counter, 3 digits: 001, 002, ... then 1000, 1001...):
 *   Lead      LD + NNN  e.g. LD001, LD002, LD043
 *   Customer  CM + NNN  e.g. CM001, CM002
 *   Invoice   IN + NNN  e.g. IN001, IN002
 * The counter never restarts, so a number is never handed out twice.
 * Uniqueness is guaranteed by a database unique index; the generator retries on collision.
 */
class CodeGeneratorService
{
    public function lead(): string
    {
        return $this->series('LD', 'leads', 'lead_code');
    }

    public function customer(): string
    {
        return $this->series('CM', 'customers', 'customer_code');
    }

    public function application(string $prefix = 'AP'): string
    {
        return $this->generate($prefix, 'loan_applications', 'application_code');
    }

    public function insuranceApplication(): string
    {
        return $this->generate('IN', 'insurance_applications', 'application_code');
    }

    public function invoice(): string
    {
        return $this->series('IN', 'invoices', 'invoice_number');
    }

    public function payment(): string
    {
        return $this->generate('PAY', 'payments', 'payment_code', 5);
    }

    public function payout(): string
    {
        return $this->generate('PO', 'employee_payouts', 'payout_code', 4);
    }

    public function disbursement(): string
    {
        return $this->generate('DIS', 'disbursements', 'disbursement_code', 5);
    }

    public function employee(): string
    {
        return $this->generate('EMP', 'employees', 'employee_code', 4);
    }

    /**
     * Builds a code from a continuous series: prefix + zero padded counter,
     * e.g. LD + 001 -> LD001. The counter is derived from the highest number
     * already used inside the series, so it never repeats and never restarts.
     */
    protected function series(string $prefix, string $table, string $column, int $padLength = 3): string
    {
        $start = $this->lastSeriesNumber($table, $column, $prefix, $padLength);

        for ($attempt = 0; $attempt < 25; $attempt++) {
            $code = $prefix.str_pad((string) ($start + 1 + $attempt), $padLength, '0', STR_PAD_LEFT);

            if (! DB::table($table)->where($column, $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) random_int(1000, 9999), $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * Last number used inside a series, so the next code continues the series.
     *
     * The highest code of the current format wins (LD042 -> 42). When the table
     * still holds codes of a retired format (LD2601, CU2601) they are only
     * counted, never read as a number, so the new series still starts at 001.
     */
    protected function lastSeriesNumber(string $table, string $column, string $prefix, int $padLength): int
    {
        $current = '/^'.preg_quote($prefix, '/').'([0-9]{'.$padLength.'})$/';
        $series = '/^'.preg_quote($prefix, '/').'[0-9]{'.$padLength.',}$/';

        $highest = 0;
        $total = 0;

        $codes = DB::table($table)
            ->where($column, 'like', $prefix.'%')
            ->whereRaw('LENGTH('.$column.') >= ?', [strlen($prefix) + $padLength])
            ->pluck($column);

        foreach ($codes as $code) {
            $code = (string) $code;

            if (preg_match($current, $code, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }

            if (preg_match($series, $code)) {
                $total++;
            }
        }

        return max($highest, $total);
    }

    /**
     * Builds codes for the modules that keep a date in their number, e.g.
     * AP + YYYYMMDD + 4 digits (AP202610090001) for the default 'Ymd' format.
     */
    protected function generate(string $prefix, string $table, string $column, int $padLength = 4, string $dateFormat = 'Ymd'): string
    {
        $datePart = now()->format($dateFormat);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $base = strlen($prefix.$datePart);
            // Sequences grow past their padding (e.g. 99 -> 100), so accept one extra digit of length.
            $sequence = $dateFormat === 'y'
                ? $this->nextSequence($table, $column, $prefix.$datePart, $base + $padLength, $base + $padLength + 1)
                : $this->nextSequence($table, $column, $prefix.$datePart, $base + $padLength);
            $code = $prefix.$datePart.str_pad((string) $sequence, $padLength, '0', STR_PAD_LEFT);

            if (! DB::table($table)->where($column, $code)->exists()) {
                return $code;
            }
        }

        return $prefix.$datePart.str_pad((string) random_int(1000, 9999), $padLength, '0', STR_PAD_LEFT);
    }

    /**
     * Only codes of the exact same length are considered, so a new series (e.g. LD26 + 4 digits)
     * never reads the sequence of an older, longer format that happens to share the prefix.
     */
    protected function nextSequence(string $table, string $column, string $startsWith, int $minLength, ?int $maxLength = null): int
    {
        $maxLength ??= $minLength;

        $latest = DB::table($table)
            ->where($column, 'like', $startsWith.'%')
            ->whereRaw('LENGTH('.$column.') BETWEEN ? AND ?', [$minLength, $maxLength])
            ->orderByRaw('LENGTH('.$column.') DESC')
            ->orderByDesc($column)
            ->value($column);

        if (! $latest) {
            return 1;
        }

        return ((int) substr($latest, strlen($startsWith))) + 1;
    }
}
