<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Concerns\HandlesApplicationWorkflow;
use App\Http\Controllers\Controller;
use App\Models\ApplicationStatus;
use App\Models\Customer;
use App\Models\InsuranceApplication;
use App\Models\Lead;
use App\Models\Lender;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Services\CodeGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsuranceApplicationController extends Controller
{
    use HandlesApplicationWorkflow;

    public function __construct(protected CodeGeneratorService $codes)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', \App\Models\LoanApplication::class);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'product_category_id' => ['required', 'exists:product_categories,id'],
            'product_subcategory_id' => ['nullable', 'exists:product_subcategories,id'],
            'insurer_id' => ['nullable', 'exists:lenders,id'],
            'sum_assured' => ['required', 'numeric', 'min:10000'],
            'premium_amount' => ['required', 'numeric', 'min:100'],
            'premium_frequency' => ['required', Rule::in(['monthly', 'quarterly', 'half_yearly', 'yearly', 'single'])],
            'policy_term_years' => ['required', 'integer', 'between:1,50'],
            'nominee_name' => ['nullable', 'string', 'max:160'],
            'nominee_relation' => ['nullable', 'string', 'max:60'],
            'nominee_dob' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = Product::query()->where('slug', Product::INSURANCE)->firstOrFail();

        $application = InsuranceApplication::query()->create(array_merge($data, [
            'application_code' => $this->codes->insuranceApplication(),
            'product_id' => $product->id,
            'status' => 'created',
            'assigned_to' => $data['assigned_to'] ?? $request->user()->id,
            'created_by' => $request->user()->id,
        ]));

        if ($status = ApplicationStatus::query()->where('slug', 'created')->first()) {
            $application->recordApplicationStatus($status, 'Insurance application created', $request->user());
        }

        return redirect()->route('insurance.show', $application)->with('success', 'Insurance application '.$application->application_code.' created.');
    }

    public function create(Request $request)
    {
        $this->authorize('create', \App\Models\LoanApplication::class);

        return view('applications.insurance.create', [
            'customers' => Customer::query()->orderBy('name')->limit(500)->get(['id', 'name', 'mobile']),
            'types' => ProductCategory::query()->active()->ordered()
                ->whereHas('product', fn ($q) => $q->where('slug', Product::INSURANCE))->get(),
            'plans' => ProductSubcategory::query()->active()->ordered()->get(),
            'insurers' => Lender::query()->active()->ordered()->get(),
            'lead' => $request->filled('lead_id') ? Lead::query()->find($request->integer('lead_id')) : null,
        ]);
    }

    public function update(Request $request, InsuranceApplication $insurance): RedirectResponse
    {
        $this->authorize('update', $insurance);

        $data = $request->validate([
            'policy_number' => ['nullable', 'string', 'max:60'],
            'sum_assured' => ['nullable', 'numeric', 'min:0'],
            'premium_amount' => ['nullable', 'numeric', 'min:0'],
            'premium_frequency' => ['nullable', Rule::in(['monthly', 'quarterly', 'half_yearly', 'yearly', 'single'])],
            'policy_term_years' => ['nullable', 'integer', 'between:1,50'],
            'policy_start_date' => ['nullable', 'date'],
            'policy_end_date' => ['nullable', 'date', 'after_or_equal:policy_start_date'],
            'nominee_name' => ['nullable', 'string', 'max:160'],
            'nominee_relation' => ['nullable', 'string', 'max:60'],
            'nominee_dob' => ['nullable', 'date'],
            'commission_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $insurance->update($data);

        return back()->with('success', 'Insurance application updated.');
    }
}
