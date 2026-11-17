<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Place name of the check-in / check-out (e.g. "Head Office", "Pune, Maharashtra").
     * Kept next to the coordinates so the attendance register can show a readable
     * location instead of a raw position.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('check_in_location', 150)->nullable()->after('check_in_lng');
            $table->string('check_out_location', 150)->nullable()->after('check_out_lng');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_location', 'check_out_location']);
        });
    }
};
