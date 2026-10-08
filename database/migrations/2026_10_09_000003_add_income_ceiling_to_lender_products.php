<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lender_products', function (Blueprint $table) {
            $table->decimal('max_monthly_income', 14, 2)->nullable()->after('min_monthly_income');
        });
    }

    public function down(): void
    {
        Schema::table('lender_products', fn (Blueprint $table) => $table->dropColumn('max_monthly_income'));
    }
};
