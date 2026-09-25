<?php

namespace App\Services;

use App\Models\ApplicationStatus;
use App\Models\Disbursement;
use App\Models\LeadStatus;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DisbursementService
{
    public function __construct(
        protected CodeGeneratorService $codes,
        protected NotificationService $notifications,
    ) {
    }

    public function create(array $data, User $actor): Disbursement
    {
        return DB::transaction(function () use ($data, $actor) {
            $disbursement = new Disbursement($data);
            $disbursement->disbursement_code = $this->codes->disbursement();
            $disbursement->created_by = $actor->id;
            $disbursement->recalculateNet();
            $disbursement->save();

            return $disbursement->refresh();
        });
    }

    public function update(Disbursement $disbursement, array $data): Disbursement
    {
        $disbursement->fill($data);
        $disbursement->recalculateNet();
        $disbursement->save();

        return $disbursement->refresh();
    }

    public function changeStatus(Disbursement $disbursement, string $status, User $actor, array $extra = []): Disbursement
    {
        return DB::transaction(function () use ($disbursement, $status, $actor, $extra) {
            $disbursement->fill(array_merge($extra, ['status' => $status]));

            if ($status === 'completed') {
                $disbursement->completed_at = now();
                $disbursement->approved_by ??= $actor->id;
            }

            $disbursement->recalculateNet();
            $disbursement->save();

            if ($status === 'completed' && $disbursement->application) {
                $application = $disbursement->application;

                $application->forceFill([
                    'disbursed_amount' => $application->disbursements()->where('status', 'completed')->sum('disbursed_amount'),
                ])->save();

                if ($statusModel = ApplicationStatus::query()->where('slug', 'disbursed')->first()) {
                    $application->recordApplicationStatus($statusModel, 'Disbursement '.$disbursement->disbursement_code.' completed');
                }

                if ($application->lead && ($leadStatus = LeadStatus::query()->where('slug', 'disbursed')->first())) {
                    $application->lead->recordLeadStatus($leadStatus, 'Loan disbursed');
                }
            }

            $this->notifications->send(
                [$disbursement->customer->assignedEmployee, $disbursement->application?->assignee, $disbursement->creator],
                'disbursement-completed',
                'Disbursement '.ucfirst($status),
                sprintf('%s for %s is now %s', $disbursement->disbursement_code, $disbursement->customer->name, $status),
                ['url' => route('disbursements.show', $disbursement), 'actor_id' => $actor->id]
            );

            return $disbursement->refresh();
        });
    }

    public function approve(LoanApplication $application, User $actor, array $data = []): LoanApplication
    {
        $application->fill($data);

        if ($status = ApplicationStatus::query()->where('slug', $data['status_slug'] ?? 'approved')->first()) {
            $application->recordApplicationStatus($status, $data['note'] ?? 'Application approved');
        }

        if ($application->lead) {
            $leadStatus = LeadStatus::query()->where('slug', $status->slug ?? 'approved')->first();

            if ($leadStatus) {
                $application->lead->recordLeadStatus($leadStatus, 'Application '.$status->name);
            }
        }

        $this->notifications->send(
            [$application->assignee, $application->creator],
            ($status->slug ?? '') === 'rejected' ? 'loan-rejected' : 'loan-approved',
            'Application '.($status->name ?? 'updated'),
            sprintf('%s for %s is %s', $application->application_code, $application->customer->name, $status->name ?? ''),
            ['url' => route('applications.show', $application), 'actor_id' => $actor->id]
        );

        return $application->refresh();
    }
}
