@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-header', true)
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Business performance across leads, applications, disbursements and collections.')

@section('page-actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bar-chart"></i> Reports</a>
    @canPermission('leads.create')
        <a href="{{ route('leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create Lead</a>
    @endcanPermission
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Leads" :value="number_format($kpis['total_leads'])" icon="bi-funnel" tone="primary"
                         :trend="$kpis['total_leads_trend']" meta="vs last year" :href="route('leads.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="New Leads" :value="number_format($kpis['new_leads'])" icon="bi-person-plus" tone="info"
                         meta="this year" :href="route('leads.index', ['from_date' => now()->startOfYear()->toDateString()])" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Active Applications" :value="number_format($kpis['active_applications'])" icon="bi-clipboard-check" tone="warning"
                         meta="in progress" :href="route('applications.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Approved" :value="number_format($kpis['approved_applications'])" icon="bi-patch-check" tone="success"
                         meta="ready to disburse" :href="route('applications.index', ['status' => 'approved'])" />
        </div>

        <div class="col-6 col-xl-3">
            <x-stat-card label="Pending Applications" :value="number_format($kpis['pending_applications'])" icon="bi-hourglass-split" tone="secondary"
                         meta="awaiting action" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Disbursed Loans" :value="number_format($kpis['disbursed_loans'])" icon="bi-cash-stack" tone="success"
                         meta="lifetime" :href="route('disbursements.index')" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Total Disbursement" :value="\App\Support\Format::compactInr($kpis['total_disbursement'])" icon="bi-graph-up-arrow" tone="primary"
                         meta="completed disbursals" />
        </div>
        <div class="col-6 col-xl-3">
            <x-stat-card label="Pending Payments" :value="\App\Support\Format::compactInr($kpis['pending_payments'])" icon="bi-exclamation-circle" tone="danger"
                         meta="outstanding invoices" :href="route('invoices.index', ['status' => 'overdue'])" />
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <x-card title="Lead Conversion Funnel" icon="bi-funnel" subtitle="From capture to disbursal">
                <div style="height:280px"><canvas id="chart-funnel"></canvas></div>
            </x-card>
        </div>

        <div class="col-lg-7">
            <x-card title="Monthly Loan Applications" icon="bi-graph-up" subtitle="Applications and value over the last 12 months">
                <div style="height:280px"><canvas id="chart-monthly"></canvas></div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Loan Category Mix" icon="bi-pie-chart" subtitle="Applications by category">
                <div style="height:260px"><canvas id="chart-category"></canvas></div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Application Status" icon="bi-clipboard-data" subtitle="Current pipeline distribution">
                <div style="height:260px"><canvas id="chart-status"></canvas></div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Monthly Disbursement" icon="bi-cash-stack" subtitle="Completed disbursals (₹)">
                <div style="height:260px"><canvas id="chart-disbursement"></canvas></div>
            </x-card>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-card title="Recent Leads" icon="bi-clock-history" subtitle="Latest activity across your pipeline" padding="false">
                <x-slot:actions>
                    <a href="{{ route('leads.index') }}" class="btn btn-sm btn-light">View all <i class="bi bi-arrow-right"></i></a>
                </x-slot:actions>

                <div class="table-responsive">
                    <table class="lp-table">
                        <thead>
                            <tr>
                                <th scope="col">Lead ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Category</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Status</th>
                                <th scope="col">Owner</th>
                                <th scope="col" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLeads as $lead)
                                <tr>
                                    <td>
                                        <a href="{{ route('leads.show', $lead) }}" class="fw-semibold">{{ $lead->lead_code }}</a>
                                        <div class="text-muted" style="font-size:.72rem">{{ $lead->created_at?->diffForHumans() }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $lead->customer?->name }}</div>
                                        <div class="text-muted" style="font-size:.72rem">{{ $lead->customer?->mobile }}</div>
                                    </td>
                                    <td>{{ $lead->category?->name ?? '—' }}</td>
                                    <td class="fw-semibold">{{ \App\Support\Format::money($lead->loan_amount) }}</td>
                                    <td><x-status-badge :status="$lead->status" :label="$lead->leadStatus?->name" /></td>
                                    <td>{{ $lead->assignee?->name ?? 'Unassigned' }}</td>
                                    <td class="text-end">
                                        <x-dropdown>
                                            <li><a class="dropdown-item" href="{{ route('leads.show', $lead) }}"><i class="bi bi-eye"></i> View</a></li>
                                            @can('update', $lead)
                                                <li><a class="dropdown-item" href="{{ route('leads.wizard', ['lead' => $lead, 'step' => $lead->current_step]) }}"><i class="bi bi-pencil"></i> Continue</a></li>
                                            @endcan
                                        </x-dropdown>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-empty-state icon="bi-funnel" title="No leads yet" message="Create your first lead to see it here." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <div class="col-xl-4">
            <x-card title="Product Catalogue" icon="bi-box-seam" subtitle="Live master data">
                @foreach ($products as $product)
                    <a href="{{ route('products.show', $product['slug']) }}" class="d-flex align-items-center gap-3 py-2 border-bottom text-decoration-none text-body">
                        <span class="lp-tone-{{ $product['theme'] }} rounded-3 d-grid" style="width:40px;height:40px;place-items:center">
                            <i class="bi {{ $product['icon'] }}"></i>
                        </span>
                        <span class="flex-grow-1">
                            <span class="d-block fw-semibold">{{ $product['name'] }}</span>
                            <span class="d-block text-muted" style="font-size:.75rem">{{ $product['tagline'] }}</span>
                        </span>
                        <span class="lp-badge bg-{{ $product['theme'] }}-subtle text-{{ $product['theme'] }} bg-opacity-10">
                            {{ $product['categories'] }} {{ $product['category_label'] }}
                        </span>
                    </a>
                @endforeach
            </x-card>

            <div class="mt-3">
                <x-card title="Quick Actions" icon="bi-lightning">
                    <div class="d-grid gap-2">
                        <a href="{{ route('reports.index') }}" class="btn btn-light text-start"><i class="bi bi-file-earmark-bar-graph"></i> Generate report</a>
                        <a href="{{ route('notifications.index') }}" class="btn btn-light text-start"><i class="bi bi-bell"></i> Notification centre</a>
                        @canPermission('masters.manage')
                            <a href="{{ route('masters.index') }}" class="btn btn-light text-start"><i class="bi bi-sliders2"></i> Manage master data</a>
                        @endcanPermission
                    </div>
                </x-card>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
    const data = {
        funnel: @json($funnel),
        monthly: @json($monthly),
        categories: @json($categories),
        statuses: @json($statuses),
        disbursements: @json($disbursements),
    };

    const palette = ['#1677FF', '#22B573', '#F5A623', '#E74C3C', '#38BDF8', '#7A8CA5', '#0B1F3A', '#8B5CF6'];
    const inr = (value) => '₹' + new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 }).format(value);

    window.addEventListener('load', () => {
        const textMuted = '#7A8CA5';
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = textMuted;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;

        new Chart(document.getElementById('chart-funnel'), {
            type: 'bar',
            data: {
                labels: data.funnel.map((row) => row.stage),
                datasets: [{
                    label: 'Leads',
                    data: data.funnel.map((row) => row.value),
                    backgroundColor: '#1677FF',
                    borderRadius: 8,
                    barThickness: 26,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color: '#EEF2F7' }, ticks: { precision: 0 } },
                    y: { grid: { display: false } },
                },
            },
        });

        new Chart(document.getElementById('chart-monthly'), {
            type: 'line',
            data: {
                labels: data.monthly.labels,
                datasets: [
                    {
                        label: 'Applications',
                        data: data.monthly.counts,
                        borderColor: '#1677FF',
                        backgroundColor: 'rgba(22,119,255,.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Value (₹)',
                        data: data.monthly.amounts,
                        borderColor: '#22B573',
                        backgroundColor: 'rgba(34,181,115,.08)',
                        borderDash: [5, 4],
                        tension: 0.35,
                        pointRadius: 0,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#EEF2F7' }, ticks: { precision: 0 }, position: 'left' },
                    y1: { beginAtZero: true, grid: { display: false }, position: 'right', ticks: { callback: (v) => inr(v) } },
                    x: { grid: { display: false } },
                },
            },
        });

        new Chart(document.getElementById('chart-category'), {
            type: 'doughnut',
            data: {
                labels: data.categories.labels,
                datasets: [{
                    data: data.categories.values,
                    backgroundColor: palette,
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom' } },
            },
        });

        new Chart(document.getElementById('chart-status'), {
            type: 'polarArea',
            data: {
                labels: data.statuses.labels,
                datasets: [{
                    data: data.statuses.values,
                    backgroundColor: data.statuses.colors,
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { r: { grid: { color: '#EEF2F7' }, ticks: { backdropColor: 'transparent', precision: 0 } } },
            },
        });

        new Chart(document.getElementById('chart-disbursement'), {
            type: 'bar',
            data: {
                labels: data.disbursements.labels,
                datasets: [{
                    label: 'Disbursed (₹)',
                    data: data.disbursements.values,
                    backgroundColor: '#22B573',
                    borderRadius: 6,
                    maxBarThickness: 26,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => inr(ctx.parsed.y) } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#EEF2F7' }, ticks: { callback: (v) => inr(v) } },
                    x: { grid: { display: false } },
                },
            },
        });
    });
</script>
@endpush
