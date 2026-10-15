<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sequential, human readable business identifiers.
 *
 * Lead IDs follow the LD + YYYYMMDD + 4 digit sequence format,
 * e.g. LD202504160012. Uniqueness is guaranteed by a database unique index;
 * the generator retries on collision.
 */
class CodeGeneratorService
{
    public function lead(): string
    {
        return $this->generate('LD', 'leads', 'lead_code', 8, 'ym');
    }

    public function customer(): string
    {
        return $this->generate('CU', 'customers', 'customer_code', 4, 'ym');
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
        return $this->generate('INV', 'invoices', 'invoice_number', 4, 'ym');
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
     * Builds codes like LD + YYMM + sequence (LD26100001) when $dateFormat is 'ym',
     * or LD + YYYYMMDD + sequence (LD202610090001) for the default 'Ymd' format.
     */
    protected function generate(string $prefix, string $table, string $column, int $padLength = 4, string $dateFormat = 'Ymd'): string
    {
        $datePart = now()->format($dateFormat);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $sequence = $this->nextSequence($table, $column, $prefix.$datePart);
            $code = $prefix.$datePart.str_pad((string) $sequence, $padLength, '0', STR_PAD_LEFT);

            if (! DB::table($table)->where($column, $code)->exists()) {
                return $code;
            }
        }

        return $prefix.$datePart.str_pad((string) random_int(1000, 9999), $padLength, '0', STR_PAD_LEFT);
    }

    protected function nextSequence(string $table, string $column, string $startsWith): int
    {
        $latest = DB::table($table)
            ->where($column, 'like', $startsWith.'%')
            ->orderByDesc($column)
            ->value($column);

        if (! $latest) {
            return 1;
        }

        return ((int) substr($latest, strlen($startsWith))) + 1;
    }
}
