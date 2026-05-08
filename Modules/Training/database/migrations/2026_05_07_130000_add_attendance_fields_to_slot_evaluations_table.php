<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('slot_evaluations', function (Blueprint $table) {
            $table->unsignedInteger('attendance_count')->nullable()->after('evaluator_id');
            $table->unsignedInteger('absent_count')->nullable()->after('attendance_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slot_evaluations', function (Blueprint $table) {
            $table->dropColumn(['attendance_count', 'absent_count']);
        });
    }
};
