<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\AssignLeadRequest;
use App\Http\Requests\Lead\ChangeLeadStatusRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadStepRequest;
use App\Http\Requests\Lead\VerifyOtpRequest;
use App\Models\Customer;
use App\Models\DocumentType;
use App\Models\EmploymentType;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\Lender;
use App\Models\LenderProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\Remark;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\ExportService;
use App\Services\LeadService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leads,
        protected OtpService $otp,
        protected DocumentService $documents,
    ) {
    }

    /* ------------------------------------------------------------------ listing */

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $query = Lead::query()
            ->ownedBy($request->user())
            ->with(['customer', 'category', 'subcategory', 'leadStatus', 'assignee', 'source'])
            ->search($request->string('q')->toString())
            ->filter($request)
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('product_category_id', $request->integer('category_id')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->integer('priority')))
            ->when($request->boolean('drafts_only'), fn ($q) => $q->draft())
            ->when($request->boolean('verified_only'), fn ($q) => $q->where('is_otp_verified', true));

        $this->applySort($query, $request, ['created_at', 'lead_code', 'loan_amount', 'priority', 'status']);

        $leads = $query->paginate($this->perPage($request))->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($leads, 'leads.partials.table');
        }

        return view('leads.index', [
            'leads' => $leads,
            'statuses' => LeadStatus::query()->active()->ordered()->get(),
            'sources' => LeadSource::query()->active()->ordered()->get(),
            'products' => Product::query()->active()->ordered()->get(),
            'categories' => ProductCategory::query()->active()->ordered()->get(),
            'employees' => $this->assignableUsers(),
            'stats' => $this->stats($request->user()),
            'filters' => $request->all(),
        ]);
    }

    public function stats(User $user): array
    {
        $base = fn () => Lead::query()->ownedBy($user);

        return [
            'total' => $base()->count(),
            'draft' => $base()->draft()->count(),
            'new' => $base()->where('status', 'new')->count(),
            'verified' => $base()->where('is_otp_verified', true)->count(),
            'converted' => $base()->where('is_converted', true)->count(),
            'rejected' => $base()->whereIn('status', ['rejected', 'cancelled'])->count(),
        ];
    }

    /* ------------------------------------------------------------------ wizard */

    public function create(Request $request): View
    {
        $this->authorize('create', Lead::class);

        return view('leads.create', $this->wizardPayload(null, (int) $request->integer('step', 1)));
    }

    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        $lead = $this->leads->createLead($request->validated(), $request->user());

        if ($request->expectsJson()) {
            return $this->ok('Lead created.', ['lead_id' => $lead->id, 'lead_code' => $lead->lead_code]);
        }

        return redirect()
            ->route('leads.wizard', ['lead' => $lead, 'step' => 2])
            ->with('success', 'Lead '.$lead->lead_code.' created. Continue with the basic information.');
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load([
            'customer.addresses', 'customer.professionalDetails', 'customer.documents',
            'category', 'subcategory', 'product', 'source', 'leadStatus', 'employmentType',
            'assignee', 'creator', 'statusHistories.changedBy', 'statusHistories.leadStatus',
            'lenders.lender', 'lenders.lenderProduct', 'applications.category',
            'documents.documentType', 'documents.verifier', 'remarks.creator', 'assignments.assignee',
        ]);

        return view('leads.show', [
            'lead' => $lead,
            'statuses' => LeadStatus::query()->active()->ordered()->get(),
            'documentTypes' => DocumentType::query()->active()->orderBy('sort_order')->get(),
            'employees' => $this->assignableUsers(),
        ]);
    }

    public function wizard(Request $request, Lead $lead, int $step = 1): View|RedirectResponse
    {
        $this->authorize('update', $lead);

        $step = max(1, min(10, $step));

        if ($step === 3 && $lead->is_otp_verified && ! $request->boolean('reverify')) {
            return redirect()->route('leads.wizard', ['lead' => $lead, 'step' => 4]);
        }

        return view('leads.create', array_merge($this->wizardPayload($lead, $step), [
            'lead' => $lead->load(['customer.addresses', 'customer.professionalDetails', 'documents.documentType', 'lenders.lender', 'lenders.lenderProduct']),
        ]));
    }

    public function saveStep(UpdateLeadStepRequest $request, Lead $lead, int $step): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $lead);

        $step = max(1, min(10, $step));

        $lead = match ($step) {
            1 => $this->updateCustomerStep($lead, $request),
            3 => $lead,
            6 => $this->handleDocuments($request, $lead),
            8, 9 => $this->handleLenderSelection($request, $lead),
            default => $this->leads->updateStep($lead, $step, $request->validated()),
        };

        $nextStep = min(10, $step + 1);
        $asDraft = $request->boolean('is_draft');

        if (! $asDraft) {
            $lead = $this->leads->updateStep($lead, $step, ['current_step' => $nextStep]);
        }

        if ($step === 7 && ! $asDraft && $lead->status === 'draft') {
            $this->leads->changeStatus($lead, 'new', 'Basic details and loan requirement captured.', $request->user());
        }

        if ($request->expectsJson()) {
            return $this->ok($asDraft ? 'Saved as draft.' : 'Step saved.', [
                'lead_id' => $lead->id,
                'current_step' => $lead->current_step,
                'next_url' => route('leads.wizard', ['lead' => $lead, 'step' => $asDraft ? $step : $nextStep]),
                'progress' => $lead->progressPercent(),
            ]);
        }

        if ($asDraft) {
            return redirect()->route('leads.wizard', ['lead' => $lead, 'step' => $step])
                ->with('success', 'Draft saved. You can resume this lead at any time.');
        }

        return redirect()->route('leads.wizard', ['lead' => $lead, 'step' => $nextStep])
            ->with('success', 'Step '.(! $asDraft ? $step : $step - 1).' saved successfully.');
    }

    protected function updateCustomerStep(Lead $lead, Request $request): Lead
    {
        $data = $request->validated();

        if ($lead->customer) {
            $this->leads->saveCustomerDetails($lead->customer, $data);
            $lead->customer->refresh();
        }

        return $lead->refresh();
    }

    protected function handleDocuments(Request $request, Lead $lead): Lead
    {
        foreach ((array) $request->file('documents', []) as $row) {
            if (! is_array($row) || empty($row['file'])) {
                continue;
            }

            $this->documents->store($lead, $row['file'], [
                'document_type_id' => $row['document_type_id'] ?? null,
                'is_required' => (bool) ($row['is_required'] ?? false),
                'issued_number' => $row['issued_number'] ?? null,
                'expires_at' => $row['expires_at'] ?? null,
            ], $request->user());
        }

        return $lead->refresh();
    }

    protected function handleLenderSelection(Request $request, Lead $lead): Lead
    {
        $rows = (array) $request->input('lenders', []);

        foreach ($rows as $row) {
            if (empty($row['lender_id'])) {
                continue;
            }

            $product = ! empty($row['lender_product_id'])
                ? LenderProduct::query()->find($row['lender_product_id'])
                : null;

            // Empty strings arrive from the UI for values the customer left untouched —
            // they must fall back to the lender product defaults, never overwrite with 0.
            $value = fn (string $key, $fallback) => array_key_exists($key, $row) && $row[$key] !== '' && $row[$key] !== null
                ? (float) $row[$key]
                : $fallback;

            $amount = (float) ($value('loan_amount', null) ?? $lead->loan_amount ?? 0);
            $tenure = (int) ($value('tenure_months', null) ?? $lead->tenure_months ?? 36);
            $roi = $value('roi', (float) ($product?->roi ?? 0));

            // loan_lenders is unique on (lead_id, lender_id): one shortlist row per lender.
            $lead->lenders()->updateOrCreate(
                ['lender_id' => $row['lender_id']],
                [
                    'lender_product_id' => $product?->id,
                    'loan_amount' => $amount,
                    'tenure_months' => $tenure,
                    'roi' => $roi,
                    'apr' => $value('apr', $product?->apr),
                    'emi' => $value('emi', $product?->calculateEmi($amount, $tenure) ?: null),
                    'processing_fee' => $value('processing_fee', $product?->processingFeeAmount($amount)),
                    'penal_charge' => $value('penal_charge', $product?->penal_charge),
                    'required_documents' => $row['required_documents'] ?? $product?->required_documents,
                    'is_selected' => true,
                    'status' => 'selected',
                    'created_by' => $request->user()->id,
                ]
            );
        }

        // Keep the shortlist in sync: lenders unchecked in the UI are dropped.
        $kept = collect($rows)->pluck('lender_id')->filter()->unique()->values();
        if ($kept->isNotEmpty()) {
            $lead->lenders()->whereNotIn('lender_id', $kept->all())->delete();
        }

        if ($request->filled('remove_lender_id')) {
            $lead->lenders()->where('lender_id', $request->integer('remove_lender_id'))->delete();
        }

        $lead->refresh();

        if ($lead->lenders()->exists() && $lead->is_otp_verified && $lead->status === 'new') {
            $this->leads->changeStatus($lead, 'lender-selected', 'Lenders shortlisted for the customer.', $request->user());
        }

        return $lead->refresh();
    }

    /* ------------------------------------------------------------------ otp */

    public function sendOtp(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $code = $this->otp->generate($lead);
        $lead->forceFill(['otp_channel' => 'sms'])->save();

        return $this->ok('A 6-digit OTP has been sent to '.$lead->customer->mobile.'.', [
            'expires_in' => $this->otp->expiresIn($lead),
            'demo_code' => app()->environment('production') ? null : $code,
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        if (! $this->otp->verify($lead, $request->validated('otp'))) {
            return $this->fail('The OTP entered is incorrect or has expired. Please try again.', 422);
        }

        if ($lead->status === 'draft') {
            $this->leads->changeStatus($lead, 'verified', 'Mobile number verified via OTP.', $request->user());
        }

        return $this->ok('Mobile number verified successfully.', [
            'verified_at' => $lead->refresh()->otp_verified_at?->toDateTimeString(),
            'next_url' => route('leads.wizard', ['lead' => $lead, 'step' => 4]),
        ]);
    }

    /* ------------------------------------------------------------------ actions */

    public function changeStatus(ChangeLeadStatusRequest $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $lead);

        $status = LeadStatus::query()->findOrFail($request->integer('lead_status_id'));
        $this->leads->changeStatus($lead, $status->slug, $request->validated('note'), $request->user());

        if ($request->expectsJson()) {
            return $this->ok('Lead status updated to '.$status->name.'.', ['status' => $status->slug]);
        }

        return back()->with('success', 'Lead status updated to '.$status->name.'.');
    }

    public function assign(AssignLeadRequest $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $this->authorize('assign', $lead);

        $this->leads->assign($lead, $request->integer('assigned_to'), $request->user(), $request->validated('note'));

        if ($request->expectsJson()) {
            return $this->ok('Lead assigned successfully.');
        }

        return back()->with('success', 'Lead assigned successfully.');
    }

    public function addRemark(Request $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $this->authorize('view', $lead);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'type' => ['nullable', 'in:comment,call,meeting,note,status'],
        ]);

        $remark = Remark::query()->create([
            'remarkable_type' => Lead::class,
            'remarkable_id' => $lead->id,
            'body' => $data['body'],
            'type' => $data['type'] ?? 'comment',
            'created_by' => $request->user()->id,
        ]);

        $lead->forceFill(['last_activity_at' => now()])->save();

        if ($request->expectsJson()) {
            return $this->ok('Remark added.', ['html' => view('leads.partials.remark', ['remark' => $remark->load('creator')])->render()]);
        }

        return back()->with('success', 'Remark added.');
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('convert', $lead);

        $application = $this->leads->convertToApplication($lead, $request->user());

        return redirect()
            ->route('applications.show', $application)
            ->with('success', 'Application '.$application->application_code.' created from lead '.$lead->lead_code.'.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()->route('leads.index')->with('success', 'Lead deleted.');
    }

    public function export(Request $request, ExportService $export)
    {
        $this->authorize('export', Lead::class);

        $leads = Lead::query()
            ->ownedBy($request->user())
            ->with(['customer', 'category', 'leadStatus', 'assignee'])
            ->search($request->string('q')->toString())
            ->filter($request)
            ->latest()
            ->limit(5000)
            ->get();

        $rows = $leads->map(fn (Lead $lead) => [
            $lead->lead_code,
            $lead->customer?->name,
            $lead->customer?->mobile,
            $lead->category?->name,
            $lead->subcategory?->name,
            $lead->loan_amount,
            $lead->leadStatus?->name,
            $lead->assignee?->name,
            $lead->created_at?->format('d M Y'),
        ]);

        return $export->xlsx('leads-'.now()->format('Ymd-His').'.xlsx', [
            'Lead ID', 'Customer', 'Mobile', 'Category', 'Purpose', 'Amount', 'Status', 'Owner', 'Created On',
        ], $rows, 'Leads', [
            'Report' => 'Lead Register',
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }

    public function quickView(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return response()->json([
            'success' => true,
            'html' => view('leads.partials.quick-view', [
                'lead' => $lead->load(['customer', 'category', 'subcategory', 'leadStatus', 'assignee', 'source']),
            ])->render(),
        ]);
    }

    public function draftResume(Request $request): View
    {
        $drafts = Lead::query()
            ->ownedBy($request->user())
            ->draft()
            ->with(['customer', 'category', 'leadStatus'])
            ->latest('last_activity_at')
            ->paginate(10);

        return view('leads.drafts', ['drafts' => $drafts]);
    }

    /* ------------------------------------------------------------------ helpers */

    protected function wizardPayload(?Lead $lead, int $step): array
    {
        $categoryQuery = ProductCategory::query()->active()->ordered();

        if ($lead?->product_id) {
            $categoryQuery->where('product_id', $lead->product_id);
        }

        return [
            'step' => $step,
            'steps' => Lead::STEPS,
            'customers' => Customer::query()->orderBy('name')->limit(300)->get(['id', 'name', 'mobile', 'email']),
            'sources' => LeadSource::query()->active()->ordered()->get(),
            'products' => Product::query()->active()->ordered()->get(),
            'categories' => $categoryQuery->get(),
            'subcategories' => ProductSubcategory::query()->active()->ordered()->get(),
            'employmentTypes' => EmploymentType::query()->active()->ordered()->get(),
            'documentTypes' => DocumentType::query()->active()->orderBy('sort_order')->get(),
            'employees' => $this->assignableUsers(),
            'lenders' => Lender::query()->active()->ordered()->get(),
            'lenderProducts' => LenderProduct::query()->active()->with('lender')->get(),
            'selectedLenders' => $lead?->lenders()->with(['lender', 'lenderProduct'])->get() ?? collect(),
            'statuses' => LeadStatus::query()->active()->ordered()->get(),
        ];
    }

    protected function assignableUsers()
    {
        return User::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'email']);
    }

    protected function applySort($query, Request $request, array $allowed): void
    {
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $column = in_array($sort, $allowed, true) ? $sort : 'created_at';

        $query->orderBy($column, $direction);
    }
}
