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
        Schema::table('daily_training_logs', function (Blueprint $table) {
            // QS - Quân số (attendance count)
            $table->unsignedInteger('attendance_count')->nullable()->after('actual_period_number');
            // V - Vắng (absent count)
            $table->unsignedInteger('absent_count')->nullable()->after('attendance_count');
            // Nhận xét tiết học (per-slot remarks)
            $table->text('remarks')->nullable()->after('issue_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_training_logs', function (Blueprint $table) {
            $table->dropColumn(['attendance_count', 'absent_count', 'remarks']);
        });
    }
};
