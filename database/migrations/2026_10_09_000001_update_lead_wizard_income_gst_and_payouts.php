<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026-10-09 change set:
 *  - lender products get a monthly income band (max_monthly_income) so a bank is
 *    only offered to customers inside its band;
 *  - customers get GST details used to auto-fill invoices;
 *  - employees get a monthly salary and a per-approved-lead coin rate;
 *  - the employee payout module (salary + incentive per month) gets its table;
 *  - the lead wizard lost its OTP step, so stored step numbers are shifted down.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lender_products', function (Blueprint $table) {
            $table->decimal('max_monthly_income', 14, 2)->nullable()->after('min_monthly_income');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('gst_treatment', 60)->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('gst_legal_name', 180)->nullable();
            $table->string('gst_trade_name', 180)->nullable();
            $table->string('place_of_supply', 80)->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('gst_treatment', 60)->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('gst_legal_name', 180)->nullable();
            $table->string('gst_trade_name', 180)->nullable();
            $table->string('pan_number', 10)->nullable();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('monthly_salary', 12, 2)->nullable()->after('monthly_target');
            $table->decimal('coins_per_lead', 10, 2)->nullable()->after('monthly_salary');
        });

        Schema::create('employee_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_code', 30)->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('payout_month');
            $table->unsignedInteger('approved_leads')->default(0);
            $table->decimal('coins_per_lead', 10, 2)->default(0);
            $table->decimal('total_coins', 12, 2)->default(0);
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->decimal('incentive_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('unpaid')->index();
            $table->date('paid_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'payout_month']);
        });

        // Lead wizard: old steps 4..10 became 3..9 and the OTP step (3) was removed.
        DB::table('leads')->where('current_step', '>=', 4)->update(['current_step' => DB::raw('current_step - 1')]);
        DB::table('leads')->where('current_step', 3)->update(['current_step' => 2]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payouts');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['monthly_salary', 'coins_per_lead']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['gst_treatment', 'gstin', 'gst_legal_name', 'gst_trade_name', 'place_of_supply']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['gst_treatment', 'gstin', 'gst_legal_name', 'gst_trade_name', 'pan_number']);
        });

        Schema::table('lender_products', function (Blueprint $table) {
            $table->dropColumn('max_monthly_income');
        });
    }
};
