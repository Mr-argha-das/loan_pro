<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ApplicationStatus;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\LeadService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

trait HandlesApplicationWorkflow
{
    /**
     * Applies a status transition, keeping the immutable history table, the
     * lead status and the notification feed in sync.
     */
    protected function applyStatus(LoanApplication $application, array $data, User $actor): LoanApplication
    {
        return DB::transaction(function () use ($application, $data, $actor) {
            $status = ApplicationStatus::query()->where('slug', $data['status_slug'])->firstOrFail();

            $attributes = [];

            if (! empty($data['rejection_reason'])) {
                $attributes['rejection_reason'] = $data['rejection_reason'];
            }

            if (! empty($data['sanctioned_amount'])) {
                $attributes['sanctioned_amount'] = $data['sanctioned_amount'];
            }

            if ($status->slug === 'approved') {
                $attributes['sanctioned_amount'] = $attributes['sanctioned_amount'] ?? $application->loan_amount;
                $attributes['sanction_date'] = $application->sanction_date ?? now()->toDateString();
            }

            if ($status->slug === 'disbursed' && ! $application->disbursed_amount) {
                $attributes['disbursed_amount'] = $application->loan_amount;
            }

            if ($attributes) {
                $application->forceFill($attributes)->save();
            }

            $application->recordApplicationStatus($status, $data['note'] ?? null, $actor);

            if ($application->lead) {
                app(LeadService::class)->changeStatus(
                    $application->lead,
                    $status->slug,
                    'Application '.$application->application_code.' is '.$status->name,
                    $actor
                );
            }

            app(NotificationService::class)->send(
                [$application->assignee, $application->creator],
                $status->slug === 'rejected' ? 'loan-rejected' : ($status->slug === 'approved' ? 'loan-approved' : 'application-status-changed'),
                'Application '.$status->name,
                $application->application_code.' for '.($application->customer?->name ?? 'customer').' is now '.$status->name.'.',
                ['url' => route('applications.show', $application), 'actor_id' => $actor->id]
            );

            return $application->refresh();
        });
    }
}
