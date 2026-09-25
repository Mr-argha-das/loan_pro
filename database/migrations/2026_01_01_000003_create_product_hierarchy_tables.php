<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dynamic product hierarchy: products -> product_categories -> product_subcategories.
     *
     * Every category (e.g. "Personal Loan"), purpose ("Marriage") or insurance
     * product ("Term Life Insurance") lives here, so administrators can extend
     * the catalogue from Master Management without any code change.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('code', 40)->unique();
            $table->string('icon', 60)->default('briefcase');
            $table->string('icon_set', 40)->default('bootstrap');
            $table->string('theme', 40)->default('primary');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('card_gradient_from', 20)->nullable();
            $table->string('card_gradient_to', 20)->nullable();
            $table->string('category_label', 60)->default('Categories');
            $table->string('subcategory_label', 60)->default('Purposes');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 170);
            $table->string('code', 50)->nullable();
            $table->string('icon', 60)->nullable();
            $table->text('description')->nullable();
            $table->decimal('min_amount', 16, 2)->nullable();
            $table->decimal('max_amount', 16, 2)->nullable();
            $table->unsignedSmallInteger('min_tenure_months')->nullable();
            $table->unsignedSmallInteger('max_tenure_months')->nullable();
            $table->decimal('default_roi', 6, 3)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'slug']);
        });

        Schema::create('product_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->string('code', 60)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_category_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_subcategories');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('products');
    }
};
