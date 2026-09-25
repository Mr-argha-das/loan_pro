@php $leads = $leads ?? collect(); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Lead</th>
                <th scope="col">Customer</th>
                <th scope="col">Amount</th>
                <th scope="col">Priority</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leads as $lead)
                <tr>
                    <td>
                        <a href="{{ route('leads.show', $lead) }}" class="fw-semibold">{{ $lead->lead_code }}</a>
                        <div class="text-muted" style="font-size:.73rem">{{ \App\Support\Format::date($lead->created_at) }}</div>
                    </td>
                    <td>{{ $lead->customer?->name }}<div class="text-muted" style="font-size:.73rem">{{ $lead->customer?->mobile }}</div></td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($lead->loan_amount) }}</td>
                    <td><x-status-badge :status="$lead->priority" /></td>
                    <td><x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" /></td>
                    <td class="text-end">
                        <a href="{{ route('leads.show', $lead) }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-right"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="bi-check2-circle" :title="$emptyTitle ?? 'Nothing pending'" :message="$emptyMessage ?? 'You are all caught up.'" /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
