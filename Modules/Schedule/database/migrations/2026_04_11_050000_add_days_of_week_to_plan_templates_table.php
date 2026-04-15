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
        Schema::table('plan_templates', function (Blueprint $table) {
            // Thêm cột days_of_week để lưu danh sách ngày trong tuần (JSON)
            // Ví dụ: [2,4,6] = Thứ 2, 4, 6
            $table->json('days_of_week')->nullable()->after('day_of_week')->comment('Danh sách ngày trong tuần (2-8), ví dụ: [2,4,6]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_templates', function (Blueprint $table) {
            $table->dropColumn('days_of_week');
        });
    }
};
