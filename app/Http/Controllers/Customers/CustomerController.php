<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerProfessionalDetail;
use App\Models\EmploymentType;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Payment;
use App\Models\Remark;
use App\Models\User;
use App\Services\CodeGeneratorService;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(protected CodeGeneratorService $codes)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->search($request->string('q')->toString())
            ->filter($request)
            ->ownedBy($request->user())
            ->when($request->filled('kyc_status'), fn ($q) => $q->where('kyc_status', $request->string('kyc_status')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')))
            ->when($request->filled('assigned_employee_id'), fn ($q) => $q->where('assigned_employee_id', $request->integer('assigned_employee_id')))
            ->with(['assignedEmployee', 'creator'])
            ->withCount(['leads', 'applications'])
            ->orderBy($request->string('sort', 'created_at')->toString(), $request->string('direction', 'desc')->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($customers, 'customers.partials.table');
        }

        return view('customers.index', [
            'customers' => $customers,
            'employees' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'cities' => Customer::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city'),
            'stats' => [
                'total' => Customer::query()->ownedBy($request->user())->count(),
                'verified' => Customer::query()->ownedBy($request->user())->where('kyc_status', 'verified')->count(),
                'pending' => Customer::query()->ownedBy($request->user())->whereIn('kyc_status', ['pending', 'in_progress'])->count(),
                'new_this_month' => Customer::query()->ownedBy($request->user())->where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'filters' => $request->all(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.form', [
            'customer' => new Customer(['customer_type' => 'individual', 'status' => 'active', 'kyc_status' => 'pending']),
            'employmentTypes' => EmploymentType::query()->active()->ordered()->get(),
            'employees' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'designation']),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create(array_merge($request->validated(), [
            'customer_code' => $this->codes->customer(),
            'created_by' => $request->user()->id,
            'assigned_employee_id' => $request->integer('assigned_employee_id') ?: $request->user()->id,
        ]));

        $this->syncRelations($customer, $request);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer '.$customer->name.' created.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load([
            'addresses', 'professionalDetails.employmentType', 'assignedEmployee', 'creator',
            'leads.category', 'leads.leadStatus', 'applications.category', 'applications.lender',
            'invoices', 'payments.paymentMethod', 'documents.documentType', 'documents.verifier',
            'insuranceApplications.plan',
        ]);

        return view('customers.show', [
            'customer' => $customer,
            'leads' => $customer->leads()->with(['category', 'leadStatus', 'assignee'])->latest()->get(),
            'applications' => $customer->applications()->with(['category', 'lender'])->latest()->get(),
            'invoices' => $customer->invoices()->latest('invoice_date')->get(),
            'payments' => $customer->payments()->with('paymentMethod')->latest('payment_date')->get(),
            'documents' => $customer->documents()->with(['documentType', 'verifier'])->latest()->get(),
            'insurance' => $customer->insuranceApplications()->with(['plan', 'insuranceType', 'lender'])->latest()->get(),
            'activities' => Remark::query()
                ->where('remarkable_type', Customer::class)
                ->where('remarkable_id', $customer->id)
                ->with('creator')
                ->latest()
                ->get(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.form', [
            'customer' => $customer->load('addresses', 'professionalDetails'),
            'employmentTypes' => EmploymentType::query()->active()->ordered()->get(),
            'employees' => User::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'designation']),
        ]);
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());
        $this->syncRelations($customer, $request);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer profile updated.');
    }

    protected function syncRelations(Customer $customer, Request $request): void
    {
        if ($request->filled('address')) {
            CustomerAddress::query()->updateOrCreate(
                ['customer_id' => $customer->id, 'is_primary' => true],
                [
                    'address_type' => 'residential',
                    'address_line' => $request->string('address'),
                    'city' => $request->string('city'),
                    'state' => $request->string('state'),
                    'pincode' => $request->string('pincode'),
                ]
            );
        }

        if ($request->filled('company_name') || $request->filled('employment_type_id') || $request->filled('monthly_income')) {
            CustomerProfessionalDetail::query()->updateOrCreate(
                ['customer_id' => $customer->id, 'is_current' => true],
                [
                    'employment_type_id' => $request->integer('employment_type_id') ?: null,
                    'company_name' => $request->string('company_name'),
                    'designation' => $request->string('designation'),
                    'monthly_income' => $request->input('monthly_income'),
                    'annual_income' => $request->input('annual_income'),
                    'work_experience_years' => $request->integer('work_experience_years') ?: null,
                    'office_address' => $request->string('office_address'),
                ]
            );
        }
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    public function export(Request $request, ExportService $export)
    {
        $this->authorize('export', Customer::class);

        $rows = Customer::query()
            ->ownedBy($request->user())
            ->search($request->string('q')->toString())
            ->filter($request)
            ->with(['assignedEmployee'])
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (Customer $c) => [
                $c->customer_code, $c->name, $c->mobile, $c->email, $c->city, $c->state,
                $c->occupation, $c->monthly_income, $c->kyc_status, $c->assignedEmployee?->name,
            ]);

        return $export->xlsx('customers-'.now()->format('Ymd-His').'.xlsx', [
            'Customer ID', 'Name', 'Mobile', 'Email', 'City', 'State', 'Occupation', 'Monthly Income', 'KYC', 'Relationship Manager',
        ], $rows, 'Customers', [
            'Report' => 'Customer Register',
            'Generated By' => $request->user()->name,
            'Generated At' => now()->format('d M Y H:i'),
        ]);
    }

    public function timeline(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return response()->json([
            'success' => true,
            'leads' => $customer->leads()->count(),
            'applications' => $customer->applications()->count(),
            'invoices' => $customer->invoices()->count(),
            'payments' => (float) $customer->payments()->sum('amount'),
        ]);
    }
}
