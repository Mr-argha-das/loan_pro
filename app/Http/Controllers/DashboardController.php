<?php

namespace App\Http\Controllers;

use App\Models\Disbursement;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.index', [
            'kpis' => $this->dashboard->kpis($user),
            'funnel' => $this->dashboard->conversionFunnel($user),
            'monthly' => $this->dashboard->monthlyApplications($user),
            'categories' => $this->dashboard->categoryDistribution($user),
            'statuses' => $this->dashboard->statusDistribution($user),
            'disbursements' => $this->dashboard->monthlyDisbursement($user),
            'recentLeads' => $this->dashboard->recentLeads($user, 6),
            'products' => $this->dashboard->productSummary(),
        ]);
    }

    /** Live KPI refresh for the dashboard cards. */
    public function kpis(Request $request)
    {
        return $this->ok('Dashboard refreshed.', ['kpis' => $this->dashboard->kpis($request->user())]);
    }

    public function myWorkspace(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.employee', [
            'kpis' => $this->dashboard->kpis($user),
            'myLeads' => Lead::query()->ownedBy($user)->with(['customer', 'leadStatus'])->latest()->limit(8)->get(),
            'myApplications' => LoanApplication::query()->ownedBy($user)->with(['customer', 'category'])->latest()->limit(8)->get(),
            'pendingTasks' => Lead::query()->ownedBy($user)
                ->whereIn('status', ['new', 'verified', 'lender-selected', 'application-created', 'under-review'])
                ->with(['customer', 'leadStatus'])
                ->orderByDesc('priority')
                ->limit(8)
                ->get(),
            'myDisbursements' => Disbursement::query()->where('created_by', $user->id)->with(['customer'])->latest()->limit(6)->get(),
            'myInvoices' => Invoice::query()->where('created_by', $user->id)->with('customer')->latest()->limit(6)->get(),
            'myPayments' => Payment::query()->where('created_by', $user->id)->with('customer')->latest()->limit(6)->get(),
        ]);
    }
}
