<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\ExportService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->ownedBy($request->user())
            ->with(['customer', 'invoice', 'paymentMethod', 'receiver'])
            ->filter($request)
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('payment_method_id'), fn ($q) => $q->where('payment_method_id', $request->integer('payment_method_id')))
            ->when($request->filled('min_amount'), fn ($q) => $q->where('amount', '>=', $request->input('min_amount')))
            ->orderBy($request->string('sort', 'payment_date')->toString(), $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($payments, 'finance.payments.partials.table');
        }

        return view('finance.payments.index', [
            'payments' => $payments,
            'stats' => $this->payments->stats(),
            'methods' => PaymentMethod::query()->active()->ordered()->get(),
            'customers' => $this->payments->customerOptions(),
            'filters' => $request->all(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);

        return view('finance.payments.form', [
            'payment' => new Payment(['payment_date' => now()->toDateString(), 'status' => 'completed']),
            'methods' => PaymentMethod::query()->active()->ordered()->get(),
            'customers' => $this->payments->customerOptions(),
            'invoices' => $this->invoiceOptions($request),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = $this->payments->record($request->validated(), $request->user());

        return redirect()->route('payments.show', $payment)->with('success', 'Payment '.$payment->payment_code.' recorded.');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        return view('finance.payments.show', [
            'payment' => $payment->load(['customer', 'invoice.items', 'paymentMethod', 'receiver', 'application']),
        ]);
    }

    public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);

        return view('finance.payments.form', [
            'payment' => $payment,
            'methods' => PaymentMethod::query()->active()->ordered()->get(),
            'customers' => $this->payments->customerOptions(),
            'invoices' => $this->invoiceOptions(request()),
        ]);
    }

    public function update(StorePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $this->payments->update($payment, $request->validated());

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $invoice = $payment->invoice;
        $payment->delete();

        if ($invoice) {
            $invoice->refresh()->recalculate();
            $invoice->save();
        }

        return redirect()->route('payments.index')->with('success', 'Payment deleted.');
    }

    public function export(Request $request, ExportService $export)
    {
        $this->authorize('viewAny', Payment::class);

        $rows = Payment::query()
            ->ownedBy($request->user())
            ->with(['customer', 'paymentMethod', 'invoice'])
            ->filter($request)
            ->latest('payment_date')
            ->limit(5000)
            ->get()
            ->map(fn (Payment $p) => [
                $p->payment_code, $p->payment_date?->format('d M Y'), $p->customer?->name,
                $p->invoice?->invoice_number, $p->amount, $p->paymentMethod?->name,
                $p->transaction_id, \App\Support\StatusBadge::label($p->status),
            ]);

        return $export->xlsx('payments-'.now()->format('Ymd-His').'.xlsx', [
            'Payment ID', 'Date', 'Customer', 'Invoice', 'Amount', 'Method', 'Reference', 'Status',
        ], $rows, 'Payments', [
            'Report' => 'Payment Collection Register',
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }

    protected function invoiceOptions(Request $request)
    {
        return Invoice::query()->ownedBy($request->user())
            ->with('customer')
            ->whereIn('status', ['draft', 'issued', 'partially_paid', 'overdue'])
            ->latest('invoice_date')
            ->limit(300)
            ->get();
    }
}
