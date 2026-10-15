<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreLenderOfferRequest;
use App\Models\Lender;
use App\Models\LenderProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages lender offers: which product a lender funds, its rates, and the
 * monthly income band it is offered to. The lead wizard's lender selection only
 * shows offers whose band contains the customer's income.
 */
class LenderOfferController extends Controller
{
    public function create(Request $request, Lender $lender): View
    {
        $this->authorizeManage($request);

        return $this->form($lender, new LenderProduct([
            'status' => 'active',
            'loan_type' => 'unsecured',
            'processing_fee_type' => 'percent',
            'penal_charge_type' => 'percent',
        ]));
    }

    public function store(StoreLenderOfferRequest $request, Lender $lender): RedirectResponse
    {
        $lender->products()->create($this->payload($request));

        return redirect()->route('lenders.show', $lender)->with('success', 'Lender offer added.');
    }

    public function edit(Request $request, Lender $lender, LenderProduct $offer): View
    {
        $this->authorizeManage($request);
        $this->ensureOwnedBy($lender, $offer);

        return $this->form($lender, $offer);
    }

    public function update(StoreLenderOfferRequest $request, Lender $lender, LenderProduct $offer): RedirectResponse
    {
        $this->ensureOwnedBy($lender, $offer);
        $offer->update($this->payload($request));

        return redirect()->route('lenders.show', $lender)->with('success', 'Lender offer updated.');
    }

    public function destroy(Request $request, Lender $lender, LenderProduct $offer): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->ensureOwnedBy($lender, $offer);
        $offer->delete();

        return redirect()->route('lenders.show', $lender)->with('success', 'Lender offer removed.');
    }

    protected function form(Lender $lender, LenderProduct $offer): View
    {
        return view('products.lenders.offers.form', [
            'lender' => $lender,
            'offer' => $offer,
            'products' => Product::query()->ordered()->get(['id', 'name']),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name', 'product_id']),
            'subcategories' => ProductSubcategory::query()->orderBy('name')->get(['id', 'name', 'product_category_id']),
        ]);
    }

    /** Validated input in the shape LenderProduct expects. */
    protected function payload(StoreLenderOfferRequest $request): array
    {
        $data = $request->validated();

        foreach (['min_amount', 'max_amount', 'roi', 'apr', 'processing_fee', 'penal_charge',
            'min_monthly_income', 'max_monthly_income'] as $numeric) {
            $data[$numeric] = $data[$numeric] ?? null;
        }

        return $data;
    }

    protected function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasPermissionTo('lenders.manage'), 403);
    }

    protected function ensureOwnedBy(Lender $lender, LenderProduct $offer): void
    {
        abort_unless((int) $offer->lender_id === (int) $lender->id, 404);
    }
}
