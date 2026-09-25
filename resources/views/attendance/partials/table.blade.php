@php $items = $items ?? ($records ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Date</th>
                <th scope="col">Employee</th>
                <th scope="col">Check in</th>
                <th scope="col">Check out</th>
                <th scope="col">Hours</th>
                <th scope="col">Late</th>
                <th scope="col">Work mode</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $record)
                <tr>
                    <td class="fw-semibold text-nowrap">{{ \App\Support\Format::date($record->attendance_date) }}</td>
                    <td>{{ $record->employee?->user?->name ?? '—' }}</td>
                    <td>{{ \App\Support\Format::time($record->check_in_at) }}</td>
                    <td>{{ \App\Support\Format::time($record->check_out_at) }}</td>
                    <td>{{ $record->workedHours() ?? '—' }}</td>
                    <td>
                        @if ((int) $record->late_minutes > 0)
                            <span class="lp-badge bg-warning-subtle text-warning bg-opacity-10">{{ $record->late_minutes }} min</span>
                        @else — @endif
                    </td>
                    <td>{{ \App\Support\Format::titleCase($record->work_mode ?? 'office') }}</td>
                    <td><x-status-badge :status="$record->status" /></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty-state icon="bi-calendar-x" title="No attendance records" message="Check in to start recording attendance for this month." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
