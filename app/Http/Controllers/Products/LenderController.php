<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Lender;
use App\Models\LenderProduct;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LenderController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('lenders.view'), 403);

        $lenders = Lender::query()
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $term = $request->string('q')->toString();
                $sub->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%");
            }))
            ->when($request->filled('lender_type'), fn ($q) => $q->where('lender_type', $request->string('lender_type')))
            ->when($request->filled('online_status'), fn ($q) => $q->where('online_status', $request->string('online_status')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->withCount('products')
            ->orderBy('sort_order')
            ->paginate($this->perPage($request))
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->tablePayload($lenders, 'products.lenders.partials.table');
        }

        return view('products.lenders.index', [
            'lenders' => $lenders,
            'filters' => $request->all(),
        ]);
    }

    public function show(Lender $lender): View
    {
        abort_unless(auth()->user()->hasPermissionTo('lenders.view'), 403);

        return view('products.lenders.show', [
            'lender' => $lender->load(['products' => fn ($q) => $q->with('category')->orderBy('roi')]),
        ]);
    }

    /**
     * Lender product feed used by the wizard's lender selection step: search,
     * loan-type filter and ROI/EMI sorting, all server side.
     */
    public function products(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasPermissionTo('lenders.view') || $request->user()->hasPermissionTo('leads.view'), 403);

        $amount = (float) $request->input('loan_amount', 500000);
        $tenure = (int) $request->input('tenure_months', 36);

        $query = LenderProduct::query()
            ->with(['lender', 'category'])
            ->where('status', 'active')
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('product_category_id', $request->integer('category_id')))
            ->when($request->filled('loan_type'), fn ($q) => $q->where('loan_type', $request->string('loan_type')))
            ->when($request->filled('lender_id'), fn ($q) => $q->where('lender_id', $request->integer('lender_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->toString();
                $q->where(fn ($sub) => $sub->where('product_name', 'like', "%{$term}%")
                    ->orWhereHas('lender', fn ($l) => $l->where('name', 'like', "%{$term}%")));
            })
            ->when($request->filled('min_amount'), fn ($q) => $q->where('max_amount', '>=', $request->input('min_amount')))
            ->when($request->filled('max_amount'), fn ($q) => $q->where('min_amount', '<=', $request->input('max_amount')));

        $sort = $request->string('sort', 'roi')->toString();
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $sort === 'emi'
            ? $query->orderByRaw('roi is null, roi '.$direction)
            : $query->orderBy($sort === 'amount' ? 'max_amount' : ($sort === 'tenure' ? 'max_tenure_months' : 'roi'), $direction);

        $products = $query->limit(60)->get()->map(function (LenderProduct $product) use ($amount, $tenure) {
            return [
                'id' => $product->id,
                'lender_id' => $product->lender_id,
                'lender_name' => $product->lender?->name,
                'lender_code' => $product->lender?->code,
                'online_status' => $product->lender?->online_status,
                'lender_type' => $product->lender?->lender_type,
                'product_name' => $product->product_name,
                'category' => $product->category?->name,
                'loan_type' => $product->loan_type,
                'min_amount' => (float) $product->min_amount,
                'max_amount' => (float) $product->max_amount,
                'max_tenure_months' => $product->max_tenure_months,
                'roi' => $product->roi !== null ? (float) $product->roi : null,
                'apr' => $product->apr !== null ? (float) $product->apr : null,
                'penal_charge' => $product->penal_charge !== null ? (float) $product->penal_charge : null,
                'processing_fee' => $product->processingFeeAmount($amount),
                'emi' => $product->calculateEmi($amount, $tenure),
                'eligibility' => $product->eligibility,
                'required_documents' => $product->required_documents ?? [],
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $products->count(),
            'html' => view('leads.partials.lender-cards', [
                'lenderProducts' => $products,
                'amount' => $amount,
                'tenure' => $tenure,
            ])->render(),
        ]);
    }
}
