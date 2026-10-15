<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Lead;
use Carbon\CarbonImmutable;

/**
 * Works out the figures of a monthly employee payout from live data:
 *  - approved leads = leads assigned to the employee that reached the "approved"
 *    status during the month;
 *  - total coins    = approved leads x the employee's coins per lead;
 *  - salary         = the employee's monthly salary.
 */
class EmployeePayoutService
{
    /** @return array{approved_leads:int,coins_per_lead:float,total_coins:float,monthly_salary:float} */
    public function figures(Employee $employee, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $approved = Lead::query()
            ->where('assigned_to', $employee->user_id)
            ->whereHas('statusHistories', function ($history) use ($start, $end) {
                $history->whereBetween('created_at', [$start, $end])
                    ->whereHas('leadStatus', fn ($status) => $status->where('slug', 'approved'));
            })
            ->count();

        $coinsPerLead = (float) ($employee->coins_per_lead ?? 0);

        return [
            'approved_leads' => $approved,
            'coins_per_lead' => $coinsPerLead,
            'total_coins' => round($approved * $coinsPerLead, 2),
            'monthly_salary' => (float) ($employee->monthly_salary ?? 0),
        ];
    }
}
