<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('documentable_type', 160);
            $table->unsignedBigInteger('documentable_id');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_required')->default(false);
            $table->string('issued_number', 80)->nullable();
            $table->date('expires_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['documentable_type', 'documentable_id']);
        });

        Schema::create('remarks', function (Blueprint $table) {
            $table->id();
            $table->morphs('remarkable');
            $table->text('body');
            $table->string('type', 40)->default('comment')->index();
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('action', 120)->index();
            $table->string('module', 80)->index();
            $table->string('record_type', 160)->nullable();
            $table->unsignedBigInteger('record_id')->nullable()->index();
            $table->string('record_label')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['record_type', 'record_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('remarks');
        Schema::dropIfExists('documents');
    }
};
