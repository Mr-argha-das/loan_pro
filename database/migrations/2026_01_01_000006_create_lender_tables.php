<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lenders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('code', 50)->unique();
            $table->string('logo_path')->nullable();
            $table->string('lender_type', 40)->default('bank')->index();
            $table->string('online_status', 20)->default('online')->index();
            $table->string('contact_person', 140)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('website')->nullable();
            $table->unsignedSmallInteger('processing_time_days')->nullable();
            $table->decimal('min_ticket_size', 16, 2)->nullable();
            $table->decimal('max_ticket_size', 16, 2)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 80)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lender_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->constrained('lenders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('product_subcategory_id')->nullable()->constrained('product_subcategories')->nullOnDelete();
            $table->string('product_name', 180);
            $table->string('code', 60)->nullable();
            $table->string('loan_type', 60)->default('unsecured')->index();
            $table->decimal('min_amount', 16, 2)->nullable();
            $table->decimal('max_amount', 16, 2)->nullable();
            $table->unsignedSmallInteger('min_tenure_months')->nullable();
            $table->unsignedSmallInteger('max_tenure_months')->nullable();
            $table->decimal('roi', 6, 3)->nullable();
            $table->decimal('apr', 6, 3)->nullable();
            $table->decimal('processing_fee', 8, 3)->nullable();
            $table->string('processing_fee_type', 20)->default('percent')->comment('percent or fixed');
            $table->decimal('penal_charge', 8, 3)->nullable();
            $table->string('penal_charge_type', 20)->default('percent');
            $table->unsignedSmallInteger('min_credit_score')->nullable();
            $table->decimal('min_monthly_income', 14, 2)->nullable();
            $table->text('eligibility')->nullable();
            $table->json('required_documents')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('active')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lender_products');
        Schema::dropIfExists('lenders');
    }
};
