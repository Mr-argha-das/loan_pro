<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Controller;
use App\Http\Requests\Application\ChangeApplicationStatusRequest;
use App\Http\Controllers\Concerns\HandlesApplicationWorkflow;
use App\Models\ApplicationStatus;
use App\Models\DocumentType;
use App\Models\InsuranceApplication;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\DisbursementService;
use App\Services\ExportService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanApplicationController extends Controller
{
    use HandlesApplicationWorkflow;

    public function __construct(
        protected DashboardService $dashboard,
        protected LeadService $leads,
        protected DisbursementService $disbursements,
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', LoanApplication::class);

        $applications = LoanApplication::query()
            ->ownedBy($request->user())
            ->search($request->string('q')->toString())
            ->filter($request)
            ->when($request->filled('category_id'), fn ($q) => $q->where('product_category_id', $request->integer('category_id')))
            ->when($request->filled('lender_id'), fn ($q) => $q->where('primary_lender_id', $request->integer('lender_id')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('loan_amount', '>=', $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($q) => $q->where('loan_amount', '<=', $request->input('max_amount')))
            ->with(['customer', 'category', 'subcategory', 'lender', 'applicationStatus', 'assignee'])
            ->orderBy($request->string('sort', 'created_at')->toString(), $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($applications, 'applications.loans.partials.table');
        }

        return view('applications.loans.index', [
            'applications' => $applications,
            'statuses' => ApplicationStatus::query()->active()->ordered()->get(),
            'categories' => ProductCategory::query()->active()->ordered()->get(),
            'lenders' => \App\Models\Lender::query()->active()->ordered()->get(),
            'employees' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'stats' => $this->stats($request->user()),
            'filters' => $request->all(),
        ]);
    }

    public function stats(User $user): array
    {
        $base = fn () => LoanApplication::query()->ownedBy($user);

        return [
            'total' => $base()->count(),
            'under_review' => $base()->whereIn('status', ['created', 'verified', 'under-review', 'lender-selected'])->count(),
            'approved' => $base()->where('status', 'approved')->count(),
            'rejected' => $base()->where('status', 'rejected')->count(),
            'disbursed' => $base()->where('status', 'disbursed')->count(),
            'sanctioned_value' => (float) $base()->whereIn('status', ['approved', 'disbursed', 'closed'])->sum('sanctioned_amount'),
        ];
    }

    public function show(LoanApplication $application): View
    {
        $this->authorize('view', $application);

        $application->load([
            'customer.documents.documentType', 'lead', 'category', 'subcategory', 'lender',
            'applicationStatus', 'assignee', 'creator', 'statusHistories.changedBy',
            'statusHistories.status', 'lenders.lender', 'lenders.lenderProduct',
            'lenderSubmissions', 'disbursements', 'invoices.payments', 'documents.documentType', 'remarks.creator',
        ]);

        return view('applications.loans.show', [
            'application' => $application,
            'statuses' => ApplicationStatus::query()->active()->ordered()->get(),
            'documentTypes' => DocumentType::query()->active()->orderBy('sort_order')->get(),
            'disbursement' => $application->disbursements()->latest()->first(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', LoanApplication::class);

        $lead = $request->filled('lead_id')
            ? Lead::query()->ownedBy($request->user())->findOrFail($request->integer('lead_id'))
            : null;

        if ($lead && ! $request->boolean('review')) {
            return redirect()->route('applications.show', $this->leads->convertToApplication($lead, $request->user()));
        }

        return view('applications.loans.create', [
            'lead' => $lead?->load(['customer', 'category', 'subcategory', 'lenders.lender']),
            'leads' => Lead::query()->ownedBy($request->user())->with('customer')
                ->whereIn('status', ['verified', 'lender-selected'])
                ->latest()->limit(200)->get(),
            'product' => Product::query()->where('slug', Product::LOANS)->first(),
        ]);
    }

    public function changeStatus(ChangeApplicationStatusRequest $request, LoanApplication $application): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $application);

        $statusSlug = $request->validated('status_slug');

        if (in_array($statusSlug, ['approved', 'rejected', 'disbursed', 'closed'], true)) {
            $this->authorize('approve', $application);
        }

        $this->applyStatus($application, $request->validated(), $request->user());

        if ($request->expectsJson()) {
            return $this->ok('Application status updated.', ['status' => $application->refresh()->status]);
        }

        return back()->with('success', 'Application status updated to '.\App\Support\StatusBadge::label($statusSlug).'.');
    }

    public function addRemark(Request $request, LoanApplication $application): RedirectResponse|JsonResponse
    {
        $this->authorize('view', $application);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'in:comment,call,meeting,note,status'],
        ]);

        $remark = $application->remarks()->create([
            'body' => $data['body'],
            'type' => $data['type'] ?? 'comment',
            'created_by' => $request->user()->id,
        ]);

        if ($request->expectsJson()) {
            return $this->ok('Remark added.', ['html' => view('leads.partials.remark', ['remark' => $remark->load('creator')])->render()]);
        }

        return back()->with('success', 'Remark added.');
    }

    public function assign(Request $request, LoanApplication $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $data = $request->validate(['assigned_to' => ['required', 'exists:users,id']]);

        $application->forceFill(['assigned_to' => $data['assigned_to']])->save();

        return back()->with('success', 'Application assigned successfully.');
    }

    public function quickView(LoanApplication $application): JsonResponse
    {
        $this->authorize('view', $application);

        return response()->json([
            'success' => true,
            'html' => view('applications.loans.partials.quick-view', [
                'application' => $application->load(['customer', 'category', 'lender', 'applicationStatus']),
            ])->render(),
        ]);
    }

    public function export(Request $request, ExportService $export)
    {
        $this->authorize('viewAny', LoanApplication::class);

        $rows = LoanApplication::query()
            ->ownedBy($request->user())
            ->search($request->string('q')->toString())
            ->filter($request)
            ->with(['customer', 'category', 'lender'])
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (LoanApplication $a) => [
                $a->application_code, $a->customer?->name, $a->category?->name, $a->loan_amount,
                $a->roi, $a->emi, $a->tenure_months, $a->lender?->name,
                \App\Support\StatusBadge::label($a->status), $a->created_at?->format('d M Y'),
            ]);

        return $export->xlsx('loan-applications-'.now()->format('Ymd-His').'.xlsx', [
            'Application ID', 'Customer', 'Category', 'Amount', 'ROI %', 'EMI', 'Tenure', 'Lender', 'Status', 'Created On',
        ], $rows, 'Applications', [
            'Report' => 'Loan Application Register',
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }

    public function insurance(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', LoanApplication::class);

        $applications = InsuranceApplication::query()
            ->ownedBy($request->user())
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $sub->where('application_code', 'like', '%'.$request->string('q').'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->string('q').'%'));
            }))
            ->filter($request)
            ->with(['customer', 'category', 'subcategory', 'insurer', 'applicationStatus'])
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($applications, 'applications.insurance.partials.table');
        }

        return view('applications.insurance.index', [
            'applications' => $applications,
            'statuses' => ApplicationStatus::query()->active()->ordered()->get(),
            'filters' => $request->all(),
        ]);
    }

    public function showInsurance(InsuranceApplication $insurance): View
    {
        $this->authorize('view', $insurance);

        $insurance->load([
            'customer', 'lead', 'category', 'subcategory', 'insurer', 'applicationStatus',
            'assignee', 'statusHistories.changedBy', 'documents.documentType', 'remarks.creator',
        ]);

        return view('applications.insurance.show', ['application' => $insurance]);
    }
}
