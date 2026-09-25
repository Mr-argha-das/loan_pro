<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_code', 30)->unique();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('product_subcategory_id')->nullable()->constrained('product_subcategories')->nullOnDelete();
            $table->foreignId('application_status_id')->nullable()->constrained('application_statuses')->nullOnDelete();
            $table->string('status', 40)->default('created')->index();
            $table->decimal('loan_amount', 16, 2)->default(0);
            $table->unsignedSmallInteger('tenure_months')->nullable();
            $table->decimal('roi', 6, 3)->nullable();
            $table->decimal('apr', 6, 3)->nullable();
            $table->decimal('emi', 14, 2)->nullable();
            $table->decimal('processing_fee', 14, 2)->nullable();
            $table->decimal('sanctioned_amount', 16, 2)->nullable();
            $table->decimal('disbursed_amount', 16, 2)->nullable();
            $table->date('sanction_date')->nullable();
            $table->date('expected_disbursement_date')->nullable();
            $table->foreignId('primary_lender_id')->nullable()->constrained('lenders')->nullOnDelete();
            $table->string('preferred_bank', 160)->nullable();
            $table->string('employment_type', 60)->nullable();
            $table->decimal('monthly_income', 14, 2)->nullable();
            $table->decimal('existing_emi', 14, 2)->nullable();
            $table->unsignedSmallInteger('credit_score')->nullable();
            $table->boolean('is_document_verified')->default(false);
            $table->string('verification_status', 30)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('insurance_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_code', 30)->unique();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('product_subcategory_id')->nullable()->constrained('product_subcategories')->nullOnDelete();
            $table->foreignId('application_status_id')->nullable()->constrained('application_statuses')->nullOnDelete();
            $table->string('status', 40)->default('created')->index();
            $table->string('policy_number', 60)->nullable();
            $table->decimal('sum_assured', 16, 2)->default(0);
            $table->decimal('premium_amount', 14, 2)->default(0);
            $table->string('premium_frequency', 30)->default('yearly');
            $table->unsignedSmallInteger('policy_term_years')->nullable();
            $table->date('policy_start_date')->nullable();
            $table->date('policy_end_date')->nullable();
            $table->string('nominee_name', 160)->nullable();
            $table->string('nominee_relation', 60)->nullable();
            $table->date('nominee_dob')->nullable();
            $table->decimal('commission_amount', 14, 2)->nullable();
            $table->foreignId('insurer_id')->nullable()->constrained('lenders')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('application_type', 40)->default('loan')->index();
            $table->unsignedBigInteger('application_id')->index();
            $table->foreignId('application_status_id')->nullable()->constrained('application_statuses')->nullOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('application_statuses')->nullOnDelete();
            $table->string('stage', 100);
            $table->string('state', 20)->default('completed');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_id', 'created_at']);
        });

        Schema::create('loan_lenders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->cascadeOnDelete();
            $table->foreignId('loan_application_id')->nullable()->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('lender_id')->constrained('lenders')->cascadeOnDelete();
            $table->foreignId('lender_product_id')->nullable()->constrained('lender_products')->nullOnDelete();
            $table->decimal('loan_amount', 16, 2)->nullable();
            $table->unsignedSmallInteger('tenure_months')->nullable();
            $table->decimal('roi', 6, 3)->nullable();
            $table->decimal('apr', 6, 3)->nullable();
            $table->decimal('emi', 14, 2)->nullable();
            $table->decimal('processing_fee', 14, 2)->nullable();
            $table->decimal('penal_charge', 8, 3)->nullable();
            $table->decimal('other_charges', 14, 2)->nullable();
            $table->json('required_documents')->nullable();
            $table->boolean('is_selected')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->string('status', 30)->default('selected')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['lead_id', 'lender_id'], 'loan_lenders_lead_lender_unique');
        });

        Schema::create('lender_application_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->nullable()->constrained('loan_applications')->cascadeOnDelete();
            $table->foreignId('loan_lender_id')->nullable()->constrained('loan_lenders')->cascadeOnDelete();
            $table->foreignId('lender_id')->constrained('lenders')->cascadeOnDelete();
            $table->string('reference_number', 80)->nullable();
            $table->string('status', 40)->default('submitted')->index();
            $table->decimal('requested_amount', 16, 2)->nullable();
            $table->decimal('sanctioned_amount', 16, 2)->nullable();
            $table->decimal('roi', 6, 3)->nullable();
            $table->unsignedSmallInteger('tenure_months')->nullable();
            $table->decimal('processing_fee', 14, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('sanctioned_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('meta')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lender_application_details');
        Schema::dropIfExists('loan_lenders');
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('insurance_applications');
        Schema::dropIfExists('loan_applications');
    }
};
