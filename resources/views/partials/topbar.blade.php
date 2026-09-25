@php
    $user = auth()->user();
    $todayAttendance = $user->employee
        ? \App\Models\Attendance::query()
            ->where('employee_id', $user->employee->id)
            ->whereDate('attendance_date', today())
            ->first()
        : null;
@endphp

<header class="lp-header">
    <button class="btn btn-icon btn-light lp-sidebar-toggle" data-sidebar-toggle aria-label="Toggle navigation" aria-controls="lp-sidebar" aria-expanded="false">
        <i class="bi bi-list"></i>
    </button>

    <button class="btn btn-icon btn-light d-none d-lg-inline-flex" data-sidebar-collapse aria-label="Collapse sidebar">
        <i class="bi bi-layout-sidebar-inset"></i>
    </button>

    <div class="lp-header__search">
        <i class="bi bi-search"></i>
        <label for="global-search" class="visually-hidden">Global search</label>
        <input type="search" id="global-search" data-global-search placeholder="Search leads, customers, applications, invoices..." autocomplete="off">
        <div class="lp-search-results" id="lp-search-results" role="listbox" aria-label="Search results"></div>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">
        @if ($user->employee && $user->hasPermissionTo('attendance.mark'))
            <form method="POST" action="{{ route($todayAttendance?->check_in_at ? 'attendance.check-out' : 'attendance.check-in') }}" class="d-none d-md-block">
                @csrf
                <button class="btn btn-sm {{ $todayAttendance?->check_in_at ? 'btn-outline-secondary' : 'btn-success' }}">
                    <i class="bi {{ $todayAttendance?->check_in_at ? 'bi-box-arrow-right' : 'bi-box-arrow-in-right' }}"></i>
                    {{ $todayAttendance?->check_in_at ? 'Check out' : 'Check in' }}
                </button>
            </form>
        @endif

        @canPermission('leads.create')
            <a href="{{ route('leads.create') }}" class="btn btn-sm btn-primary d-none d-sm-inline-flex">
                <i class="bi bi-plus-lg"></i> New Lead
            </a>
        @endcanPermission

        <div class="dropdown">
            <button class="btn btn-icon btn-light position-relative" data-notification-bell data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ ($navUnreadCount ?? 0) ? '' : 'd-none' }}" data-notification-count>
                    {{ $navUnreadCount ?? 0 }}
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0" style="width:380px">
                <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <span class="fw-semibold">Notifications</span>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button class="btn btn-sm btn-link p-0">Mark all read</button>
                    </form>
                </div>
                <div id="lp-notification-feed">
                    @include('notifications.partials.feed', ['notifications' => $navNotifications ?? collect()])
                </div>
                <div class="border-top text-center py-2">
                    <a href="{{ route('notifications.index') }}" class="small fw-semibold">View all notifications</a>
                </div>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 pe-2" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="lp-avatar">{{ $user->initials() }}</span>
                <span class="text-start d-none d-md-block lh-sm">
                    <span class="d-block fw-semibold" style="font-size:.83rem">{{ $user->name }}</span>
                    <span class="d-block text-muted" style="font-size:.7rem">{{ $user->designationLabel() }}</span>
                </span>
                <i class="bi bi-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2">
                    <div class="fw-semibold">{{ $user->name }}</div>
                    <div class="text-muted small">{{ $user->email }}</div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.show') }}"><i class="bi bi-person"></i> My Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('password.change') }}"><i class="bi bi-key"></i> Change Password</a></li>
                @if ($user->hasPermissionTo('settings.manage'))
                    <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="bi bi-gear"></i> Settings</a></li>
                @endif
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item text-danger w-100 text-start"><i class="bi bi-box-arrow-right"></i> Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
