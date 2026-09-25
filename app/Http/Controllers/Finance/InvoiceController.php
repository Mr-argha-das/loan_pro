<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoanApplication;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\ExportService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoices,
        protected PaymentService $payments,
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->ownedBy($request->user())
            ->with(['customer', 'creator'])
            ->search($request->string('q')->toString())
            ->filter($request)
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('total', '>=', $request->input('min_amount')))
            ->orderBy($request->string('sort', 'invoice_date')->toString(), $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($invoices, 'finance.invoices.partials.table');
        }

        return view('finance.invoices.index', [
            'invoices' => $invoices,
            'stats' => $this->invoices->stats($request->user()),
            'customers' => $this->invoices->customerOptions(),
            'filters' => $request->all(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Invoice::class);

        $invoice = new Invoice([
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays((int) Setting::get('invoice_due_days', 15))->toDateString(),
            'tax_rate' => (float) Setting::get('default_tax_rate', 18),
            'status' => 'draft',
            'terms' => $this->invoices->defaultTerms(),
        ]);

        $invoice->setRelation('items', collect());

        return view('finance.invoices.form', [
            'invoice' => $invoice,
            'customers' => $this->invoices->customerOptions(),
            'leads' => $this->leadOptions($request),
            'applications' => $this->applicationOptions($request),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $invoice = $this->invoices->create($data, $items, $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice '.$invoice->invoice_number.' created.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer.addresses', 'lead', 'application', 'items', 'payments.paymentMethod', 'creator', 'documents']);

        return view('finance.invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => PaymentMethod::query()->active()->ordered()->get(),
            'company' => [
                'name' => Setting::get('company_name', config('app.name')),
                'address' => Setting::get('company_address'),
                'email' => Setting::get('company_email'),
                'phone' => Setting::get('company_phone'),
                'gst' => Setting::get('gst_number'),
            ],
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);

        return view('finance.invoices.form', [
            'invoice' => $invoice->load('items'),
            'customers' => $this->invoices->customerOptions(),
            'leads' => $this->leadOptions(request()),
            'applications' => $this->applicationOptions(request()),
        ]);
    }

    public function update(StoreInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $this->invoices->update($invoice, $data, $items);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function issue(Request $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $invoice);

        $this->invoices->markIssued($invoice, $request->user());

        if ($request->expectsJson()) {
            return $this->ok('Invoice issued.');
        }

        return back()->with('success', 'Invoice '.$invoice->invoice_number.' issued.');
    }

    public function changeStatus(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'status' => ['required', 'in:draft,issued,partially_paid,paid,overdue,cancelled'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $invoice->forceFill([
            'status' => $data['status'],
            'cancelled_at' => $data['status'] === 'cancelled' ? now() : $invoice->cancelled_at,
        ])->save();

        return back()->with('success', 'Invoice marked as '.\App\Support\StatusBadge::label($data['status']).'.');
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items', 'payments']);

        $pdf = Pdf::loadView('finance.invoices.pdf', [
            'invoice' => $invoice,
            'company' => [
                'name' => Setting::get('company_name', config('app.name')),
                'tagline' => Setting::get('company_tagline'),
                'address' => Setting::get('company_address'),
                'email' => Setting::get('company_email'),
                'phone' => Setting::get('company_phone'),
                'gst' => Setting::get('gst_number'),
            ],
        ])->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    public function print(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('finance.invoices.print', [
            'invoice' => $invoice->load(['customer', 'items', 'payments']),
            'company' => [
                'name' => Setting::get('company_name', config('app.name')),
                'tagline' => Setting::get('company_tagline'),
                'address' => Setting::get('company_address'),
                'email' => Setting::get('company_email'),
                'phone' => Setting::get('company_phone'),
                'gst' => Setting::get('gst_number'),
            ],
        ]);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        abort_if($invoice->payments()->exists(), 422, 'Invoices with recorded payments cannot be deleted.');

        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function export(Request $request, ExportService $export)
    {
        $this->authorize('viewAny', Invoice::class);

        $rows = Invoice::query()
            ->ownedBy($request->user())
            ->with('customer')
            ->filter($request)
            ->latest('invoice_date')
            ->limit(5000)
            ->get()
            ->map(fn (Invoice $i) => [
                $i->invoice_number, $i->customer?->name, $i->invoice_date?->format('d M Y'),
                $i->due_date?->format('d M Y'), $i->total, $i->paid_amount, $i->balance_amount,
                \App\Support\StatusBadge::label($i->status),
            ]);

        return $export->xlsx('invoices-'.now()->format('Ymd-His').'.xlsx', [
            'Invoice No', 'Customer', 'Invoice Date', 'Due Date', 'Total', 'Paid', 'Balance', 'Status',
        ], $rows, 'Invoices', [
            'Report' => 'Invoice Register',
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }

    protected function leadOptions(Request $request)
    {
        return \App\Models\Lead::query()->ownedBy($request->user())->with('customer')
            ->latest()->limit(200)->get();
    }

    protected function applicationOptions(Request $request)
    {
        return LoanApplication::query()->ownedBy($request->user())->with('customer')
            ->latest()->limit(200)->get();
    }
}
