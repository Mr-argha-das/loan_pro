@php
    $user = auth()->user();
    $route = request()->route()?->getName() ?? '';
    $is = fn (array $patterns) => collect($patterns)->contains(fn ($pattern) => str_starts_with($route, $pattern));
@endphp

<aside class="lp-sidebar" id="lp-sidebar">
    <a href="{{ route('dashboard') }}" class="lp-sidebar__brand text-decoration-none">
        <span class="lp-sidebar__mark">LP</span>
        <span>
            <span class="lp-sidebar__title d-block">LoanPro</span>
            <span class="lp-sidebar__subtitle">Finance &amp; Insurance</span>
        </span>
    </a>

    <nav class="lp-sidebar__nav" aria-label="Main navigation">
        <div class="lp-nav__section">Overview</div>

        <a href="{{ route('dashboard') }}" class="lp-nav__link {{ $route === 'dashboard' ? 'active' : '' }}">
            <i class="bi bi-grid-1x2"></i><span class="lp-nav__label">Dashboard</span>
        </a>

        @if ($user->hasPermissionTo('masters.view') || $user->hasPermissionTo('leads.view'))
            <a href="{{ route('products.index') }}" class="lp-nav__link {{ $is(['products.']) ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i><span class="lp-nav__label">Products</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('lenders.view'))
            <a href="{{ route('lenders.index') }}" class="lp-nav__link {{ $is(['lenders.']) ? 'active' : '' }}">
                <i class="bi bi-bank"></i><span class="lp-nav__label">Lenders &amp; Insurers</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('leads.view'))
            <div class="lp-nav__section">Lead Management</div>

            <a class="lp-nav__link lp-nav__group-toggle"
               data-bs-toggle="collapse" href="#nav-leads" role="button"
               aria-expanded="{{ $is(['leads.']) ? 'true' : 'false' }}" aria-controls="nav-leads">
                <i class="bi bi-funnel"></i><span class="lp-nav__label">Leads</span>
                <i class="bi bi-chevron-right lp-nav__chevron"></i>
            </a>

            <div class="collapse {{ $is(['leads.']) ? 'show' : '' }}" id="nav-leads">
                <div class="lp-nav__sub">
                    @canPermission('leads.create')
                        <a href="{{ route('leads.create') }}" class="lp-nav__link {{ $route === 'leads.create' ? 'active' : '' }}">
                            <i class="bi bi-plus-circle"></i><span class="lp-nav__label">Create Lead</span>
                        </a>
                    @endcanPermission

                    <a href="{{ route('leads.index') }}" class="lp-nav__link {{ in_array($route, ['leads.index', 'leads.show', 'leads.wizard'], true) ? 'active' : '' }}">
                        <i class="bi bi-list-ul"></i><span class="lp-nav__label">Lead List</span>
                    </a>

                    <a href="{{ route('leads.drafts') }}" class="lp-nav__link {{ $route === 'leads.drafts' ? 'active' : '' }}">
                        <i class="bi bi-pencil-square"></i><span class="lp-nav__label">Saved Drafts</span>
                        @if (($navDraftLeads ?? 0) > 0)
                            <span class="lp-nav__badge">{{ $navDraftLeads }}</span>
                        @endif
                    </a>

                    <a href="{{ route('masters.show', 'lead-statuses') }}" class="lp-nav__link {{ $route === 'masters.show' && request()->route('master') === 'lead-statuses' ? 'active' : '' }}">
                        <i class="bi bi-diagram-3"></i><span class="lp-nav__label">Lead Status</span>
                    </a>
                </div>
            </div>
        @endif

        @if ($user->hasPermissionTo('customers.view'))
            <a href="{{ route('customers.index') }}" class="lp-nav__link {{ $is(['customers.']) ? 'active' : '' }}">
                <i class="bi bi-people"></i><span class="lp-nav__label">Customers</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('applications.view'))
            <div class="lp-nav__section">Lending</div>

            <a href="{{ route('applications.index') }}" class="lp-nav__link {{ $is(['applications.']) ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i><span class="lp-nav__label">Loan Applications</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('insurance.view'))
            <a href="{{ route('insurance.index') }}" class="lp-nav__link {{ $is(['insurance.']) ? 'active' : '' }}">
                <i class="bi bi-umbrella"></i><span class="lp-nav__label">Insurance</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('disbursements.view'))
            <a href="{{ route('disbursements.index') }}" class="lp-nav__link {{ $is(['disbursements.']) ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i><span class="lp-nav__label">Disbursements</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('invoices.view') || $user->hasPermissionTo('payments.view'))
            <div class="lp-nav__section">Finance</div>
        @endif

        @if ($user->hasPermissionTo('invoices.view'))
            <a href="{{ route('invoices.index') }}" class="lp-nav__link {{ $is(['invoices.']) ? 'active' : '' }}">
                <i class="bi bi-receipt"></i><span class="lp-nav__label">Invoices</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('payments.view'))
            <a href="{{ route('payments.index') }}" class="lp-nav__link {{ $is(['payments.']) ? 'active' : '' }}">
                <i class="bi bi-cash-coin"></i><span class="lp-nav__label">Payments</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('documents.upload'))
            <a href="{{ route('documents.index') }}" class="lp-nav__link {{ $is(['documents.']) ? 'active' : '' }}">
                <i class="bi bi-folder2-open"></i><span class="lp-nav__label">Documents</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('attendance.view') || $user->hasPermissionTo('attendance.mark'))
            <div class="lp-nav__section">Workplace</div>

            <a href="{{ route('attendance.index') }}" class="lp-nav__link {{ $is(['attendance.']) ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i><span class="lp-nav__label">Attendance</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('reports.view'))
            <a href="{{ route('reports.index') }}" class="lp-nav__link {{ $is(['reports.']) ? 'active' : '' }}">
                <i class="bi bi-graph-up-arrow"></i><span class="lp-nav__label">Reports</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('employees.view'))
            <a href="{{ route('employees.index') }}" class="lp-nav__link {{ $is(['employees.']) ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i><span class="lp-nav__label">Employees</span>
            </a>
        @endif

        <div class="lp-nav__section">System</div>

        <a href="{{ route('notifications.index') }}" class="lp-nav__link {{ $is(['notifications.']) ? 'active' : '' }}">
            <i class="bi bi-bell"></i><span class="lp-nav__label">Notifications</span>
            @if (($navUnreadCount ?? 0) > 0)
                <span class="lp-nav__badge">{{ $navUnreadCount }}</span>
            @endif
        </a>

        @if ($user->hasPermissionTo('masters.view'))
            <a href="{{ route('masters.index') }}" class="lp-nav__link {{ $is(['masters.']) ? 'active' : '' }}">
                <i class="bi bi-sliders2"></i><span class="lp-nav__label">Master Management</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('settings.manage'))
            <a href="{{ route('settings.index') }}" class="lp-nav__link {{ $is(['settings.']) ? 'active' : '' }}">
                <i class="bi bi-gear"></i><span class="lp-nav__label">Settings</span>
            </a>
        @endif

        @if ($user->hasPermissionTo('audit.view'))
            <a href="{{ route('audit.index') }}" class="lp-nav__link {{ $is(['audit.']) ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i><span class="lp-nav__label">Audit Log</span>
            </a>
        @endif
    </nav>

    <div class="lp-sidebar__footer">
        <span class="lp-avatar">{{ $user->initials() }}</span>
        <div class="flex-grow-1 overflow-hidden">
            <div class="text-white small fw-semibold text-truncate">{{ $user->name }}</div>
            <div class="text-uppercase" style="font-size:.66rem;color:rgba(255,255,255,.5)">{{ $user->designationLabel() }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
            @csrf
            <button class="btn btn-sm btn-link text-white-50 p-0" data-bs-toggle="tooltip" title="Sign out" aria-label="Sign out">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</aside>
