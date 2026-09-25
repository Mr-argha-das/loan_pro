<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(protected DashboardService $dashboard)
    {
    }

    /** Product master dashboard: four DB driven product cards. */
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('leads.view') || auth()->user()->hasPermissionTo('masters.view'), 403);

        return view('products.index', [
            'products' => Product::query()
                ->active()
                ->ordered()
                ->withCount([
                    'categories' => fn ($q) => $q->where('is_active', true),
                ])
                ->get(),
            'totals' => [
                'categories' => ProductCategory::query()->where('is_active', true)->count(),
                'subcategories' => ProductSubcategory::query()->where('is_active', true)->count(),
                'lenders' => \App\Models\Lender::query()->where('status', 'active')->count(),
                'lender_products' => \App\Models\LenderProduct::query()->where('status', 'active')->count(),
            ],
            'summary' => $this->dashboard->productSummary(),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless(auth()->user()->hasPermissionTo('leads.view') || auth()->user()->hasPermissionTo('masters.view'), 403);

        return view('products.show', [
            'product' => $product->load(['categories' => fn ($q) => $q->where('is_active', true)->ordered()->with([
                'subcategories' => fn ($s) => $s->where('is_active', true)->ordered(),
            ])]),
            'lenderProducts' => \App\Models\LenderProduct::query()
                ->where('product_id', $product->id)
                ->with(['lender', 'category'])
                ->orderBy('roi')
                ->limit(40)
                ->get(),
        ]);
    }

    /** Categories / sub-categories for the accordion (AJAX refresh). */
    public function categories(Request $request): JsonResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        $categories = ProductCategory::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->ordered()
            ->with(['subcategories' => fn ($q) => $q->where('is_active', true)->ordered()])
            ->get();

        return response()->json([
            'success' => true,
            'html' => view('products.partials.categories', ['categories' => $categories, 'product' => $product])->render(),
        ]);
    }
}
