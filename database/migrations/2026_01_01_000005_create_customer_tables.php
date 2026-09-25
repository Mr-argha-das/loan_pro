<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 40)->unique();
            $table->string('name', 160);
            $table->string('mobile', 20)->index();
            $table->string('alternate_mobile', 20)->nullable();
            $table->string('email')->nullable()->index();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('marital_status', 30)->nullable();
            $table->string('father_or_spouse_name', 160)->nullable();
            $table->string('pan_number', 15)->nullable()->index();
            $table->string('aadhaar_number', 20)->nullable();
            $table->string('nationality', 60)->default('Indian');
            $table->string('customer_type', 40)->default('individual')->index();
            $table->string('occupation', 120)->nullable();
            $table->string('city', 80)->nullable()->index();
            $table->string('state', 80)->nullable();
            $table->string('pincode', 12)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->string('company_name', 160)->nullable();
            $table->string('designation', 120)->nullable();
            $table->decimal('monthly_income', 14, 2)->nullable();
            $table->decimal('annual_income', 14, 2)->nullable();
            $table->unsignedSmallInteger('work_experience_years')->nullable();
            $table->text('office_address')->nullable();
            $table->foreignId('assigned_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('active')->index();
            $table->string('kyc_status', 30)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name', 'created_at']);
            $table->unique(['mobile', 'deleted_at']);
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('address_type', 30)->default('residential')->index();
            $table->string('label', 60)->nullable();
            $table->text('address_line');
            $table->string('landmark', 160)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('country', 60)->default('India');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_professional_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->string('company_name', 180)->nullable();
            $table->string('designation', 140)->nullable();
            $table->string('industry', 120)->nullable();
            $table->decimal('monthly_income', 14, 2)->nullable();
            $table->decimal('annual_income', 14, 2)->nullable();
            $table->decimal('other_income', 14, 2)->nullable();
            $table->unsignedSmallInteger('work_experience_years')->nullable();
            $table->unsignedSmallInteger('business_vintage_years')->nullable();
            $table->string('company_type', 60)->nullable();
            $table->string('gst_number', 20)->nullable();
            $table->text('office_address')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_professional_details');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
