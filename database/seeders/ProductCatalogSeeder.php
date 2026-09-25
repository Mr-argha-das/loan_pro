<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the dynamic product catalogue.
 *
 * Only the catalogue that has actually been defined is seeded - Cards and Real
 * Estate are created as products with no categories, because their categories
 * are configured later by an administrator (the UI stays data-driven).
 */
class ProductCatalogSeeder extends Seeder
{
    public const LOAN_CATEGORIES = [
        'Personal Loan' => [
            'Family Function', 'Purchase of Appliance', 'Furniture', 'Electronics', 'Marriage',
            'Medical', 'Travel', 'Vacation', 'Home Renovation', 'Education', 'Balance Transfer',
            'Top Up', 'Balance Transfer + Top Up', 'Others',
        ],
        'Business Loan' => [
            'Business Expansion', 'Working Capital', 'Purchase of Machinery', 'Inventory Purchase',
            'Business Premises', 'Balance Transfer', 'Top Up', 'Others',
        ],
        'Home Loan' => [
            'Buy Ready-To-Occupy Home', 'Buy Underconstruction Home', 'Buy A Plot Of Land',
            'Balance Transfer + Top Up', 'Repair & Renovate Own Home', 'Others',
        ],
        'Loan Against Property' => [
            'Business Improvement', 'Debt Consolidation', 'Balance Transfer + Top Up', 'Others',
        ],
        'Gold Loan' => [
            'Term Loan', 'OD', 'Balance Transfer', 'Top Up',
        ],
        'Loan For Premium' => [
            'Insurance Premium',
        ],
    ];

    public const INSURANCE_CATEGORIES = [
        'Life Insurance' => [
            'Term Life Insurance', 'Endowment', 'Unit-Linked', 'Money Back', 'Guaranteed Return', 'Pension',
        ],
        'Health Insurance' => [
            'Individual', 'Family', 'Group',
        ],
        'General Insurance' => [
            'Vehicle Insurance', 'Property Insurance',
        ],
    ];

    public function run(): void
    {
        $loans = $this->product([
            'name' => 'Loans',
            'slug' => Product::LOANS,
            'code' => 'LOAN',
            'icon' => 'bi-cash-coin',
            'theme' => 'success',
            'tagline' => 'Personal, business, home, gold & LAP funding',
            'description' => 'Retail and MSME lending across secured and unsecured categories.',
            'card_gradient_from' => '#E8F8F0',
            'card_gradient_to' => '#D6F2E6',
            'category_label' => 'Loan Categories',
            'subcategory_label' => 'Purposes',
            'sort_order' => 1,
        ]);

        foreach (self::LOAN_CATEGORIES as $category => $purposes) {
            $this->category($loans, $category, $purposes, [
                'min_amount' => in_array($category, ['Home Loan', 'Loan Against Property'], true) ? 500000 : 25000,
                'max_amount' => in_array($category, ['Home Loan', 'Loan Against Property'], true) ? 50000000 : 4000000,
                'min_tenure_months' => 6,
                'max_tenure_months' => $category === 'Home Loan' ? 360 : 84,
                'default_roi' => match ($category) {
                    'Home Loan' => 8.5,
                    'Loan Against Property' => 9.5,
                    'Gold Loan' => 10.5,
                    'Business Loan' => 13.5,
                    'Loan For Premium' => 11.0,
                    default => 12.5,
                },
            ]);
        }

        $insurance = $this->product([
            'name' => 'Insurance',
            'slug' => Product::INSURANCE,
            'code' => 'INSU',
            'icon' => 'bi-umbrella',
            'theme' => 'warning',
            'tagline' => 'Life, health & general insurance solutions',
            'description' => 'Protection products across life, health and general insurance.',
            'card_gradient_from' => '#FFF6E5',
            'card_gradient_to' => '#FFEBC7',
            'category_label' => 'Insurance Categories',
            'subcategory_label' => 'Sub-Categories',
            'sort_order' => 2,
        ]);

        foreach (self::INSURANCE_CATEGORIES as $category => $plans) {
            $this->category($insurance, $category, $plans, [
                'min_amount' => 100000,
                'max_amount' => 100000000,
                'min_tenure_months' => 12,
                'max_tenure_months' => 480,
            ]);
        }

        $this->product([
            'name' => 'Cards',
            'slug' => Product::CARDS,
            'code' => 'CARD',
            'icon' => 'bi-credit-card-2-front',
            'theme' => 'info',
            'tagline' => 'Credit, debit & business cards',
            'description' => 'Card programmes sourced from partner banks and issuers.',
            'card_gradient_from' => '#E9F3FF',
            'card_gradient_to' => '#D6E9FF',
            'category_label' => 'Card Categories',
            'subcategory_label' => 'Card Products',
            'sort_order' => 3,
        ]);

        $this->product([
            'name' => 'Real Estate',
            'slug' => Product::REAL_ESTATE,
            'code' => 'REAL',
            'icon' => 'bi-house-door',
            'theme' => 'danger',
            'tagline' => 'Residential & commercial property services',
            'description' => 'Property sourcing, advisory and documentation services.',
            'card_gradient_from' => '#FFECEF',
            'card_gradient_to' => '#FFD9DF',
            'category_label' => 'Property Categories',
            'subcategory_label' => 'Services',
            'sort_order' => 4,
        ]);
    }

    protected function product(array $attributes): Product
    {
        return Product::query()->updateOrCreate(['slug' => $attributes['slug']], $attributes);
    }

    protected function category(Product $product, string $name, array $purposes, array $defaults = []): ProductCategory
    {
        $slug = Str::slug($name);

        $category = ProductCategory::query()->updateOrCreate(
            ['product_id' => $product->id, 'slug' => $slug],
            array_merge([
                'name' => $name,
                'code' => strtoupper(Str::substr(Str::slug($name, ''), 0, 12)),
                'description' => $name.' options offered by partner lenders.',
                'is_active' => true,
                'sort_order' => ProductCategory::query()->where('product_id', $product->id)->count() + 1,
            ], $defaults)
        );

        foreach (array_values($purposes) as $index => $purpose) {
            ProductSubcategory::query()->updateOrCreate(
                ['product_category_id' => $category->id, 'slug' => Str::slug($purpose)],
                [
                    'product_id' => $product->id,
                    'name' => $purpose,
                    'code' => strtoupper(Str::substr(Str::slug($purpose, ''), 0, 14)),
                    'description' => $purpose.' funding under '.$name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        return $category;
    }
}
