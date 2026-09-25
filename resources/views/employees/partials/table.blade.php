@php $items = $items ?? ($employees ?? collect()); @endphp

<div class="lp-table-wrap">
    <table class="lp-table">
        <thead>
            <tr>
                <th scope="col">Employee</th>
                <th scope="col">Code</th>
                <th scope="col">Department</th>
                <th scope="col">Designation</th>
                <th scope="col">Role</th>
                <th scope="col">Contact</th>
                <th scope="col">Joined</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $employee)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="lp-avatar">{{ $employee->user?->initials() }}</span>
                            <div>
                                <a href="{{ route('employees.show', $employee) }}" class="fw-semibold">{{ $employee->user?->name }}</a>
                                <div class="text-muted" style="font-size:.73rem">{{ $employee->user?->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="fw-semibold">{{ $employee->employee_code }}</td>
                    <td>{{ $employee->department?->name ?? '—' }}</td>
                    <td>{{ $employee->designation?->name ?? $employee->user?->designation ?? '—' }}</td>
                    <td>{{ $employee->user?->role?->name ?? '—' }}</td>
                    <td>
                        <div>{{ $employee->mobile ?? '—' }}</div>
                        <div class="text-muted" style="font-size:.73rem">{{ $employee->city ?? '' }}</div>
                    </td>
                    <td class="text-muted text-nowrap">{{ \App\Support\Format::date($employee->joining_date) }}</td>
                    <td><x-status-badge :status="$employee->employment_status" /></td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-light" title="Open"><i class="bi bi-eye"></i></a>
                            @canPermission('employees.manage')
                                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('employees.toggle-status', $employee) }}" class="d-inline"
                                      data-confirm="Change the employment status of {{ $employee->user?->name }}?">
                                    @csrf
                                    <button class="btn btn-sm btn-light" title="Toggle status"><i class="bi bi-arrow-repeat"></i></button>
                                </form>
                            @endcanPermission
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-empty-state icon="bi-people" title="No employees found" message="Adjust the filters or onboard a new team member." /></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination-bar', ['items' => $items])
