<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            // Add new period_number column as integer
            $table->integer('period_number')->nullable()->after('period');
        });

        // Migrate existing data: convert 'Sáng' to periods 1-5, 'Chiều' to periods 6-9
        DB::table('schedule_slots')
            ->where('period', 'Sáng')
            ->update(['period_number' => DB::raw('(id % 5) + 1')]);

        DB::table('schedule_slots')
            ->where('period', 'Chiều')
            ->update(['period_number' => DB::raw('(id % 4) + 6')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropColumn('period_number');
        });
    }
};
