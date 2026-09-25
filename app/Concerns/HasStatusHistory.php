<?php

namespace App\Concerns;

use App\Models\ApplicationStatus;
use App\Models\ApplicationStatusHistory;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use Illuminate\Support\Facades\Auth;

/**
 * Records immutable status transitions for leads and applications.
 * History rows are only ever appended - a status change never overwrites
 * a previous event.
 */
trait HasStatusHistory
{
    public function recordLeadStatus(LeadStatus $status, ?string $note = null, ?string $stage = null): void
    {
        $previous = $this->lead_status_id;

        $this->forceFill([
            'lead_status_id' => $status->id,
            'status' => $status->slug,
            'last_activity_at' => now(),
        ])->save();

        LeadStatusHistory::query()->create([
            'lead_id' => $this->getKey(),
            'lead_status_id' => $status->id,
            'from_status_id' => $previous,
            'stage' => $stage ?? $status->name,
            'state' => 'completed',
            'note' => $note,
            'changed_by' => Auth::id(),
        ]);
    }

    public function recordApplicationStatus(ApplicationStatus $status, ?string $note = null, ?string $stage = null): void
    {
        $previous = $this->application_status_id;
        $type = $this instanceof \App\Models\InsuranceApplication ? 'insurance' : 'loan';

        $timestamps = match ($status->slug) {
            'verified' => ['verified_at' => now()],
            'approved' => ['approved_at' => now()],
            'rejected' => ['rejected_at' => now()],
            'disbursed' => ['disbursed_at' => now()],
            'closed' => ['closed_at' => now()],
            'application-created', 'created' => ['submitted_at' => $this->submitted_at ?? now()],
            default => [],
        };

        $this->forceFill(array_merge([
            'application_status_id' => $status->id,
            'status' => $status->slug,
        ], $timestamps))->save();

        ApplicationStatusHistory::query()->create([
            'application_type' => $type,
            'application_id' => $this->getKey(),
            'application_status_id' => $status->id,
            'from_status_id' => $previous,
            'stage' => $stage ?? $status->name,
            'state' => 'completed',
            'note' => $note,
            'changed_by' => Auth::id(),
        ]);
    }
}
