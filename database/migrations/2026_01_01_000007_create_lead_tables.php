<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('lead_code', 30)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('product_subcategory_id')->nullable()->constrained('product_subcategories')->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->foreignId('lead_status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lead_type', 40)->default('fresh')->index();
            $table->string('customer_type', 40)->default('new')->index();
            $table->string('preferred_contact_method', 30)->default('call');
            $table->string('preferred_contact_time', 40)->nullable();
            $table->unsignedTinyInteger('priority')->default(2)->comment('1 high, 2 normal, 3 low');
            $table->decimal('loan_amount', 16, 2)->nullable()->index();
            $table->unsignedSmallInteger('tenure_months')->nullable();
            $table->string('preferred_bank', 160)->nullable();
            $table->decimal('monthly_income', 14, 2)->nullable();
            $table->decimal('existing_emi', 14, 2)->nullable();
            $table->unsignedSmallInteger('credit_score')->nullable();
            $table->boolean('is_otp_verified')->default(false);
            $table->timestamp('otp_verified_at')->nullable();
            $table->string('otp_channel', 20)->default('sms');
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('status', 40)->default('draft')->index()->comment('denormalised lead_status slug for filtering');
            $table->boolean('is_draft')->default(true)->index();
            $table->boolean('is_converted')->default(false)->index();
            $table->string('verification_status', 30)->default('pending');
            $table->decimal('expected_commission', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_at'], 'leads_created_at_index');
            $table->index(['created_by', 'assigned_to'], 'leads_owner_index');
        });

        Schema::create('lead_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('lead_status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->string('stage', 80);
            $table->string('state', 20)->default('completed')->comment('completed, current, upcoming');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_assignments');
        Schema::dropIfExists('lead_status_histories');
        Schema::dropIfExists('leads');
    }
};
