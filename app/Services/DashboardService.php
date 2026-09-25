<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Disbursement;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregations for the executive dashboard and the reports module.
 */
class DashboardService
{
    public function kpis(User $user, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->startOfYear();
        $to ??= now();

        $leadScope = fn () => Lead::query()->ownedBy($user);
        $appScope = fn () => LoanApplication::query()->ownedBy($user);

        $totalLeads = $leadScope()->count();
        $previousLeads = $leadScope()->whereBetween('created_at', [$from->copy()->subYear(), $to->copy()->subYear()])->count();
        $newLeads = $leadScope()->whereBetween('created_at', [$from, $to])->count();

        $activeApplications = $appScope()->whereNotIn('status', ['disbursed', 'closed', 'cancelled', 'rejected'])->count();
        $approved = $appScope()->where('status', 'approved')->count();
        $pending = $appScope()->whereIn('status', ['created', 'verified', 'lender-selected', 'application-created', 'under-review', 'disbursement-pending'])->count();
        $disbursed = $appScope()->where('status', 'disbursed')->count();

        $totalDisbursed = Disbursement::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->where('status', 'completed')
            ->sum('disbursed_amount');

        $pendingPayments = Invoice::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->whereIn('status', ['issued', 'partially_paid', 'overdue'])
            ->sum('balance_amount');

        return [
            'total_leads' => $totalLeads,
            'total_leads_trend' => $this->trend($totalLeads, $previousLeads),
            'new_leads' => $newLeads,
            'active_applications' => $activeApplications,
            'approved_applications' => $approved,
            'pending_applications' => $pending,
            'disbursed_loans' => $disbursed,
            'total_disbursement' => (float) $totalDisbursed,
            'pending_payments' => (float) $pendingPayments,
            'total_customers' => Customer::query()->ownedBy($user)->count(),
            'total_payments' => (float) Payment::query()->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))->sum('amount'),
            'documents_uploaded' => Document::query()->when(! $user->isAdmin(), fn ($q) => $q->where('uploaded_by', $user->id))->count(),
        ];
    }

    protected function trend(float|int $current, float|int $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** Lead conversion funnel: created -> verified -> lender selected -> application -> approved -> disbursed. */
    public function conversionFunnel(User $user): array
    {
        $base = Lead::query()->ownedBy($user);

        return [
            ['stage' => 'Lead Created', 'value' => (clone $base)->count()],
            ['stage' => 'Verified', 'value' => (clone $base)->whereNotNull('otp_verified_at')->count()],
            ['stage' => 'Lender Selected', 'value' => (clone $base)->whereHas('lenders')->count()],
            ['stage' => 'Application Created', 'value' => LoanApplication::query()->ownedBy($user)->count()],
            ['stage' => 'Approved', 'value' => LoanApplication::query()->ownedBy($user)->whereIn('status', ['approved', 'disbursed'])->count()],
            ['stage' => 'Disbursed', 'value' => LoanApplication::query()->ownedBy($user)->where('status', 'disbursed')->count()],
        ];
    }

    public function monthlyApplications(User $user, int $months = 12): array
    {
        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $rows = LoanApplication::query()
            ->ownedBy($user)
            ->where('created_at', '>=', now()->subMonths($months)->startOfMonth())
            ->selectRaw("{$monthExpression} as ym, COUNT(*) as total, SUM(loan_amount) as amount")
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $labels = [];
        $counts = [];
        $amounts = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $labels[] = $date->format('M Y');
            $counts[] = (int) ($rows[$key]->total ?? 0);
            $amounts[] = (float) ($rows[$key]->amount ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts, 'amounts' => $amounts];
    }

    public function categoryDistribution(User $user): array
    {
        $rows = LoanApplication::query()
            ->ownedBy($user)
            ->join('product_categories', 'product_categories.id', '=', 'loan_applications.product_category_id')
            ->selectRaw('product_categories.name as label, COUNT(*) as total')
            ->groupBy('product_categories.name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    public function statusDistribution(User $user): array
    {
        $rows = LoanApplication::query()
            ->ownedBy($user)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('status')->map(fn ($s) => \App\Support\StatusBadge::label($s))->all(),
            'values' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
            'colors' => $rows->pluck('status')->map(fn ($s) => $this->hex(\App\Support\StatusBadge::color($s)))->all(),
        ];
    }

    public function monthlyDisbursement(User $user, int $months = 12): array
    {
        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "strftime('%Y-%m', disbursement_date)"
            : "DATE_FORMAT(disbursement_date, '%Y-%m')";

        $rows = Disbursement::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->where('status', 'completed')
            ->whereNotNull('disbursement_date')
            ->where('disbursement_date', '>=', now()->subMonths($months)->startOfMonth())
            ->selectRaw("{$monthExpression} as ym, SUM(disbursed_amount) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M');
            $values[] = (float) ($rows[$date->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Per-product rollup used by the dashboard cards and the product master.
     *
     * Rows keep the product identity *and* the live lead/application counts, so
     * both screens render straight from the database without extra queries.
     */
    public function productSummary(): array
    {
        $leadStats = Lead::query()
            ->selectRaw('product_id, COUNT(*) as total, COALESCE(SUM(loan_amount), 0) as amount')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $applicationStats = LoanApplication::query()
            ->selectRaw('product_id, COUNT(*) as total, COALESCE(SUM(loan_amount), 0) as amount')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        return Product::query()
            ->withCount(['categories' => fn ($q) => $q->where('is_active', true)])
            ->ordered()
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'icon' => $product->icon,
                'theme' => $product->theme,
                'tagline' => $product->tagline,
                'categories' => $product->categories_count,
                'category_label' => $product->category_label,
                'leads' => (int) ($leadStats[$product->id]->total ?? 0),
                'applications' => (int) ($applicationStats[$product->id]->total ?? 0),
                'value' => (float) ($applicationStats[$product->id]->amount ?? $leadStats[$product->id]->amount ?? 0),
            ])
            ->all();
    }

    public function recentLeads(User $user, int $limit = 8)
    {
        return Lead::query()
            ->ownedBy($user)
            ->with(['customer', 'category', 'leadStatus', 'assignee'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function recentActivity(User $user, int $limit = 8)
    {
        return \App\Models\AuditLog::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function hex(string $color): string
    {
        return match ($color) {
            'primary' => '#1677FF',
            'success' => '#22B573',
            'warning' => '#F5A623',
            'danger' => '#E74C3C',
            'info' => '#38BDF8',
            'dark' => '#0B1F3A',
            default => '#7A8CA5',
        };
    }
}
