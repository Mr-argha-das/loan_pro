<?php

namespace Database\Seeders;

use App\Models\Lender;
use App\Models\LenderProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Partner banks / NBFCs and their product configurations. Every value the
 * lender selection screen renders (ROI, APR, EMI, penal charge, documents)
 * originates from these records.
 */
class LenderSeeder extends Seeder
{
    public const LENDERS = [
        [
            'name' => 'HDFC Bank', 'code' => 'HDFC', 'type' => 'bank', 'online' => 'online',
            'contact_person' => 'Rakesh Menon', 'contact_number' => '+91 22 6172 1000',
            'contact_email' => 'partners@hdfcbank.example', 'website' => 'https://www.hdfcbank.com',
            'processing_time_days' => 4, 'min_ticket' => 50000, 'max_ticket' => 5000000,
            'city' => 'Mumbai', 'remarks' => 'Preferred partner - digital sanction available.',
        ],
        [
            'name' => 'ICICI Bank', 'code' => 'ICICI', 'type' => 'bank', 'online' => 'online',
            'contact_person' => 'Sneha Kulkarni', 'contact_number' => '+91 22 3366 7777',
            'contact_email' => 'relations@icicibank.example', 'website' => 'https://www.icicibank.com',
            'processing_time_days' => 5, 'min_ticket' => 50000, 'max_ticket' => 7500000,
            'city' => 'Mumbai', 'remarks' => 'Strong on secured lending.',
        ],
        [
            'name' => 'Axis Bank', 'code' => 'AXIS', 'type' => 'bank', 'online' => 'online',
            'contact_person' => 'Imran Shaikh', 'contact_number' => '+91 22 4245 6000',
            'contact_email' => 'insurance.desk@axisbank.example', 'website' => 'https://www.axisbank.com',
            'processing_time_days' => 6, 'min_ticket' => 50000, 'max_ticket' => 4000000,
            'city' => 'Mumbai', 'remarks' => 'Good volumes on LAP.',
        ],
        [
            'name' => 'Kotak Mahindra Bank', 'code' => 'KOTAK', 'type' => 'bank', 'online' => 'online',
            'contact_person' => 'Priya Nair', 'contact_number' => '+91 22 6166 0000',
            'contact_email' => 'loans@kotak.example', 'website' => 'https://www.kotak.com',
            'processing_time_days' => 5, 'min_ticket' => 75000, 'max_ticket' => 6000000,
            'city' => 'Pune', 'remarks' => 'Aggressive on personal loans.',
        ],
        [
            'name' => 'IDFC FIRST Bank', 'code' => 'IDFC', 'type' => 'bank', 'online' => 'offline',
            'contact_person' => 'Vikram Patel', 'contact_number' => '+91 22 7132 5500',
            'contact_email' => 'sales@idfcfirst.example', 'website' => 'https://www.idfcfirstbank.com',
            'processing_time_days' => 7, 'min_ticket' => 50000, 'max_ticket' => 3000000,
            'city' => 'Ahmedabad', 'remarks' => 'Manual credit underwriting.',
        ],
        [
            'name' => 'Bajaj Finserv', 'code' => 'BAJAJ', 'type' => 'nbfc', 'online' => 'online',
            'contact_person' => 'Anita Deshmukh', 'contact_number' => '+91 20 6740 9000',
            'contact_email' => 'dsa@bajajfinserv.example', 'website' => 'https://www.bajajfinserv.in',
            'processing_time_days' => 3, 'min_ticket' => 30000, 'max_ticket' => 4000000,
            'city' => 'Pune', 'remarks' => 'Fastest disbursal for unsecured loans.',
        ],
        [
            'name' => 'Tata Capital', 'code' => 'TATA', 'type' => 'nbfc', 'online' => 'online',
            'contact_person' => 'Sameer Joshi', 'contact_number' => '+91 22 6911 1000',
            'contact_email' => 'channel@tatacapital.example', 'website' => 'https://www.tatacapital.com',
            'processing_time_days' => 4, 'min_ticket' => 50000, 'max_ticket' => 5000000,
            'city' => 'Mumbai', 'remarks' => 'Flexible on business loans.',
        ],
        [
            'name' => 'L&T Finance', 'code' => 'LTF', 'type' => 'nbfc', 'online' => 'offline',
            'contact_person' => 'Deepak Rao', 'contact_number' => '+91 22 6217 9000',
            'contact_email' => 'partner@ltfinance.example', 'website' => 'https://www.ltfinance.com',
            'processing_time_days' => 6, 'min_ticket' => 40000, 'max_ticket' => 2500000,
            'city' => 'Mumbai', 'remarks' => 'Strong rural and semi-urban coverage.',
        ],
        [
            'name' => 'Aditya Birla Capital', 'code' => 'ABC', 'type' => 'nbfc', 'online' => 'online',
            'contact_person' => 'Ritu Sharma', 'contact_number' => '+91 22 4356 7000',
            'contact_email' => 'loans@abcapital.example', 'website' => 'https://www.adityabirlacapital.com',
            'processing_time_days' => 5, 'min_ticket' => 25000, 'max_ticket' => 3500000,
            'city' => 'Mumbai', 'remarks' => 'Good for loan against property.',
        ],
        [
            'name' => 'Muthoot Finance', 'code' => 'MUTHOOT', 'type' => 'nbfc', 'online' => 'offline',
            'contact_person' => 'Krishnan Iyer', 'contact_number' => '+91 484 6690 000',
            'contact_email' => 'gold@muthoot.example', 'website' => 'https://www.muthootfinance.com',
            'processing_time_days' => 1, 'min_ticket' => 15000, 'max_ticket' => 7500000,
            'city' => 'Kochi', 'remarks' => 'Same-day gold loan disbursal.',
        ],
    ];

    public function run(): void
    {
        $loanProduct = Product::query()->where('slug', Product::LOANS)->firstOrFail();
        $insuranceProduct = Product::query()->where('slug', Product::INSURANCE)->firstOrFail();

        foreach (self::LENDERS as $index => $data) {
            $lender = Lender::query()->updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'lender_type' => $data['type'],
                    'online_status' => $data['online'],
                    'contact_person' => $data['contact_person'],
                    'contact_number' => $data['contact_number'],
                    'contact_email' => $data['contact_email'],
                    'website' => $data['website'],
                    'processing_time_days' => $data['processing_time_days'],
                    'min_ticket_size' => $data['min_ticket'],
                    'max_ticket_size' => $data['max_ticket'],
                    'city' => $data['city'],
                    'remarks' => $data['remarks'],
                    'status' => 'active',
                    'sort_order' => $index + 1,
                ]
            );

            $this->productsFor($lender, $loanProduct, $insuranceProduct, $index);
        }
    }

    protected function productsFor(Lender $lender, Product $loanProduct, Product $insuranceProduct, int $index): void
    {
        $matrix = [
            'Personal Loan' => ['roi' => [10.49, 11.25, 12.75], 'fee' => [2.0, 2.5, 3.0], 'amount' => [25000, 4000000], 'tenure' => [12, 84]],
            'Business Loan' => ['roi' => [13.25, 15.50, 17.00], 'fee' => [2.5, 3.0, 3.5], 'amount' => [100000, 5000000], 'tenure' => [12, 60]],
            'Home Loan' => ['roi' => [8.35, 8.75, 9.15], 'fee' => [0.5, 1.0, 1.25], 'amount' => [500000, 50000000], 'tenure' => [60, 360]],
            'Loan Against Property' => ['roi' => [9.25, 10.10, 11.00], 'fee' => [1.0, 1.5, 2.0], 'amount' => [500000, 25000000], 'tenure' => [60, 240]],
            'Gold Loan' => ['roi' => [9.90, 11.50, 13.00], 'fee' => [0.5, 1.0, 1.5], 'amount' => [15000, 7500000], 'tenure' => [6, 36]],
            'Loan For Premium' => ['roi' => [10.75, 11.99, 13.50], 'fee' => [1.0, 1.5, 2.0], 'amount' => [10000, 1500000], 'tenure' => [12, 60]],
        ];

        $bucket = $index % 3;
        $sort = $index * 10;

        foreach ($matrix as $categoryName => $config) {
            $category = ProductCategory::query()
                ->where('product_id', $loanProduct->id)
                ->where('name', $categoryName)
                ->first();

            if (! $category) {
                continue;
            }

            $roi = $config['roi'][$bucket];

            $documents = ['Identity Proof', 'Address Proof', 'Income Proof', 'Bank Statement', 'Passport Size Photo'];

            if (in_array($categoryName, ['Home Loan', 'Loan Against Property'], true)) {
                $documents[] = 'Property Documents';
            }

            if ($categoryName === 'Business Loan') {
                $documents[] = 'GST Returns';
                $documents[] = 'ITR';
            }

            LenderProduct::query()->updateOrCreate(
                ['lender_id' => $lender->id, 'product_category_id' => $category->id, 'product_name' => $lender->name.' '.$categoryName],
                [
                    'product_id' => $loanProduct->id,
                    'code' => $lender->code.'-'.strtoupper(substr(preg_replace('/[^A-Z]/', '', strtoupper($categoryName)), 0, 4)),
                    'loan_type' => in_array($categoryName, ['Gold Loan', 'Home Loan', 'Loan Against Property'], true) ? 'secured' : 'unsecured',
                    'min_amount' => $config['amount'][0],
                    'max_amount' => $config['amount'][1],
                    'min_tenure_months' => $config['tenure'][0],
                    'max_tenure_months' => $config['tenure'][1],
                    'roi' => $roi,
                    'apr' => round($roi + 0.7 + ($bucket * 0.35), 2),
                    'processing_fee' => $config['fee'][$bucket],
                    'processing_fee_type' => 'percent',
                    'penal_charge' => 2.0 + ($bucket * 0.25),
                    'penal_charge_type' => 'percent',
                    'min_credit_score' => 700 + ($bucket * 15),
                    'min_monthly_income' => 20000 + ($bucket * 5000),
                    'eligibility' => 'Age 21-60 years, minimum 6 months in current employment/business, credit score '.$lender->code.' approved.',
                    'required_documents' => $documents,
                    'status' => 'active',
                    'is_featured' => $bucket === 0,
                    'sort_order' => $sort++,
                ]
            );
        }

        // Insurance placement products (insurers are modelled as lenders/insurers).
        if ($index % 2 === 0) {
            foreach (['Life Insurance' => 'Term Life Insurance', 'Health Insurance' => 'Family'] as $categoryName => $plan) {
                $category = ProductCategory::query()
                    ->where('product_id', $insuranceProduct->id)
                    ->where('name', $categoryName)
                    ->first();

                if (! $category) {
                    continue;
                }

                LenderProduct::query()->updateOrCreate(
                    ['lender_id' => $lender->id, 'product_category_id' => $category->id, 'product_name' => $lender->name.' '.$plan],
                    [
                        'product_id' => $insuranceProduct->id,
                        'code' => $lender->code.'-INS',
                        'loan_type' => 'insurance',
                        'min_amount' => 500000,
                        'max_amount' => 20000000,
                        'min_tenure_months' => 12,
                        'max_tenure_months' => 360,
                        'roi' => null,
                        'apr' => null,
                        'processing_fee' => null,
                        'penal_charge' => null,
                        'eligibility' => 'Age 18-65 years, basic medical screening for sum assured above ₹50 Lakh.',
                        'required_documents' => ['Identity Proof', 'Address Proof', 'Income Proof', 'Passport Size Photo', 'Medical Report'],
                        'status' => 'active',
                        'sort_order' => 100 + $sort++,
                    ]
                );
            }
        }
    }
}
