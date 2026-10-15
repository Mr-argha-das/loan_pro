<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** GPS location captured by the browser at check-in and check-out (nullable: permission may be denied). */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('check_in_lat', 10, 7)->nullable()->after('check_in_at');
            $table->decimal('check_in_lng', 10, 7)->nullable()->after('check_in_lat');
            $table->decimal('check_out_lat', 10, 7)->nullable()->after('check_out_at');
            $table->decimal('check_out_lng', 10, 7)->nullable()->after('check_out_lat');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_lat', 'check_in_lng', 'check_out_lat', 'check_out_lng']);
        });
    }
};
