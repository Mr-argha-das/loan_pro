<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel database notifications, extended with LoanPro metadata
     * (module, type slug, action url and actor) so the notification centre
     * can render a rich, filterable feed.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->string('notification_type', 80)->nullable()->index();
            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->string('module', 80)->nullable()->index();
            $table->string('icon', 60)->nullable();
            $table->string('color', 20)->default('primary');
            $table->string('url')->nullable();
            $table->text('data')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('severity', 20)->default('info')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
