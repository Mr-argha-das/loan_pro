<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('monthly_salary', 14, 2)->nullable()->after('monthly_target');
            $table->decimal('coin_per_approved_lead', 10, 2)->default(0)->after('monthly_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['monthly_salary', 'coin_per_approved_lead']);
        });
    }
};
