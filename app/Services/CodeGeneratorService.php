<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sequential, human readable business identifiers.
 *
 * Series (sequence restarts every year, 2-digit counter: 01..99, then 100, 101...):
 *   Lead      LD + YY + NN  e.g. LD2601, LD2699, LD26100
 *   Customer  CU + YY + NN  e.g. CU2601
 *   Invoice   IN + YY + NN  e.g. IN2601
 * Codes issued before this change (LD260001, CU2600001, IN2600001) are left as they are.
 * Uniqueness is guaranteed by a database unique index; the generator retries on collision.
 */
class CodeGeneratorService
{
    public function lead(): string
    {
        return $this->generate('LD', 'leads', 'lead_code', 2, 'y');
    }

    public function customer(): string
    {
        return $this->generate('CU', 'customers', 'customer_code', 2, 'y');
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
        return $this->generate('IN', 'invoices', 'invoice_number', 2, 'y');
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
     * Builds codes like prefix + date part + zero-padded sequence, e.g. LD + YY + 4 digits (LD260001)
     * when $dateFormat is 'y', or LD + YYYYMMDD + sequence (LD202610090001) for the default 'Ymd' format.
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
