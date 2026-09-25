<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ChangeDisbursementStatusRequest;
use App\Http\Requests\Finance\StoreDisbursementRequest;
use App\Models\Customer;
use App\Models\Disbursement;
use App\Models\Lender;
use App\Models\LoanApplication;
use App\Services\DisbursementService;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisbursementController extends Controller
{
    public function __construct(protected DisbursementService $disbursements)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Disbursement::class);

        $disbursements = Disbursement::query()
            ->ownedBy($request->user())
            ->with(['customer', 'application', 'lender', 'creator', 'approver'])
            ->filter($request)
            ->when($request->filled('lender_id'), fn ($q) => $q->where('lender_id', $request->integer('lender_id')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('disbursed_amount', '>=', $request->input('min_amount')))
            ->orderBy($request->string('sort', 'disbursement_date')->toString(), $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($disbursements, 'finance.disbursements.partials.table');
        }

        return view('finance.disbursements.index', [
            'disbursements' => $disbursements,
            'stats' => $this->stats($request->user()),
            'lenders' => Lender::query()->active()->ordered()->get(),
            'customers' => Customer::query()->orderBy('name')->limit(500)->get(['id', 'name', 'mobile']),
            'filters' => $request->all(),
        ]);
    }

    protected function stats($user): array
    {
        $base = fn () => Disbursement::query()->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id));

        return [
            'total' => $base()->count(),
            'completed' => $base()->where('status', 'completed')->count(),
            'pending' => $base()->whereIn('status', ['pending', 'processing'])->count(),
            'failed' => $base()->whereIn('status', ['failed', 'cancelled'])->count(),
            'disbursed_value' => (float) $base()->where('status', 'completed')->sum('disbursed_amount'),
        ];
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Disbursement::class);

        $application = $request->filled('loan_application_id')
            ? LoanApplication::query()->ownedBy($request->user())->with(['customer', 'lenders.lender'])->findOrFail($request->integer('loan_application_id'))
            : null;

        return view('finance.disbursements.form', [
            'disbursement' => new Disbursement([
                'disbursement_date' => now()->toDateString(),
                'status' => 'pending',
                'mode' => 'neft',
            ]),
            'applications' => LoanApplication::query()->ownedBy($request->user())
                ->with('customer')
                ->whereIn('status', ['approved', 'disbursement-pending'])
                ->latest()->limit(300)->get(),
            'lenders' => Lender::query()->active()->ordered()->get(),
            'application' => $application,
        ]);
    }

    public function store(StoreDisbursementRequest $request): RedirectResponse
    {
        $disbursement = $this->disbursements->create($request->validated(), $request->user());

        return redirect()->route('disbursements.show', $disbursement)->with('success', 'Disbursement '.$disbursement->disbursement_code.' created.');
    }

    public function show(Disbursement $disbursement): View
    {
        $this->authorize('view', $disbursement);

        return view('finance.disbursements.show', [
            'disbursement' => $disbursement->load(['customer', 'application.lenders.lender', 'lender', 'creator', 'approver']),
        ]);
    }

    public function edit(Disbursement $disbursement): View
    {
        $this->authorize('update', $disbursement);

        return view('finance.disbursements.form', [
            'disbursement' => $disbursement,
            'applications' => LoanApplication::query()->ownedBy(request()->user())->with('customer')->latest()->limit(300)->get(),
            'lenders' => Lender::query()->active()->ordered()->get(),
            'application' => $disbursement->application,
        ]);
    }

    public function update(StoreDisbursementRequest $request, Disbursement $disbursement): RedirectResponse
    {
        $this->authorize('update', $disbursement);

        $this->disbursements->update($disbursement, $request->validated());

        return redirect()->route('disbursements.show', $disbursement)->with('success', 'Disbursement updated.');
    }

    public function changeStatus(ChangeDisbursementStatusRequest $request, Disbursement $disbursement): RedirectResponse|JsonResponse
    {
        $this->authorize('changeStatus', $disbursement);

        $this->disbursements->changeStatus(
            $disbursement,
            $request->validated('status'),
            $request->user(),
            $request->safe()->only(['disbursement_date', 'reference_number', 'utr_number', 'remarks'])
        );

        if ($request->expectsJson()) {
            return $this->ok('Disbursement marked as '.\App\Support\StatusBadge::label($request->validated('status')).'.');
        }

        return back()->with('success', 'Disbursement marked as '.\App\Support\StatusBadge::label($request->validated('status')).'.');
    }

    public function export(Request $request, ExportService $export)
    {
        $this->authorize('viewAny', Disbursement::class);

        $rows = Disbursement::query()
            ->ownedBy($request->user())
            ->with(['customer', 'lender', 'application'])
            ->filter($request)
            ->latest('disbursement_date')
            ->limit(5000)
            ->get()
            ->map(fn (Disbursement $d) => [
                $d->disbursement_code, $d->disbursement_date?->format('d M Y'), $d->customer?->name,
                $d->application?->application_code, $d->lender?->name, $d->approved_amount,
                $d->processing_fee, $d->net_amount, \App\Support\StatusBadge::label($d->status),
                $d->utr_number,
            ]);

        return $export->xlsx('disbursements-'.now()->format('Ymd-His').'.xlsx', [
            'Disbursement ID', 'Date', 'Customer', 'Application', 'Lender', 'Approved Amount',
            'Processing Fee', 'Net Disbursed', 'Status', 'UTR',
        ], $rows, 'Disbursements', [
            'Report' => 'Disbursement Register',
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }
}
