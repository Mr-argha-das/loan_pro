@php $applications = $applications ?? collect(); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Application</th>
                <th scope="col">Customer</th>
                <th scope="col">Category</th>
                <th scope="col">Amount</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $application)
                <tr>
                    <td>
                        <a href="{{ route('applications.show', $application) }}" class="fw-semibold">{{ $application->application_code }}</a>
                        <div class="text-muted" style="font-size:.73rem">{{ \App\Support\Format::date($application->created_at) }}</div>
                    </td>
                    <td>{{ $application->customer?->name }}</td>
                    <td>{{ $application->category?->name ?? '—' }}</td>
                    <td class="fw-semibold">{{ \App\Support\Format::money($application->loan_amount) }}</td>
                    <td><x-status-badge :status="$application->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5"><x-empty-state icon="bi-clipboard-data" title="No applications yet" message="Convert one of your leads to start an application." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
