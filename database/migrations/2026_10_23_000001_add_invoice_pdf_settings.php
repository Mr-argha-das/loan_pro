<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the company PAN, SAC code and bank details used by the invoice PDF.
 * insertOrIgnore: rows that already exist (and any values the admin saved) are left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('settings')->insertOrIgnore([
            ['group' => 'company', 'key' => 'company_pan', 'value' => null, 'type' => 'text', 'label' => 'PAN Number', 'description' => 'Printed on invoice PDFs.', 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'company', 'key' => 'company_sac_code', 'value' => null, 'type' => 'text', 'label' => 'SAC Code', 'description' => 'Service accounting code printed on invoice PDFs.', 'sort_order' => 21, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'company', 'key' => 'bank_name', 'value' => null, 'type' => 'text', 'label' => 'Bank Name', 'description' => 'Shown on invoice PDFs.', 'sort_order' => 22, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'company', 'key' => 'bank_account_number', 'value' => null, 'type' => 'text', 'label' => 'Bank Account Number', 'description' => 'Shown on invoice PDFs.', 'sort_order' => 23, 'created_at' => $now, 'updated_at' => $now],
            ['group' => 'company', 'key' => 'bank_ifsc', 'value' => null, 'type' => 'text', 'label' => 'Bank IFSC', 'description' => 'Shown on invoice PDFs.', 'sort_order' => 24, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'company_pan', 'company_sac_code', 'bank_name', 'bank_account_number', 'bank_ifsc',
        ])->delete();
    }
};
