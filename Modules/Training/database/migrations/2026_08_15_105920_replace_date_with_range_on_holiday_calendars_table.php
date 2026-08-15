<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('holiday_calendars', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
        });

        DB::table('holiday_calendars')->update([
            'start_date' => DB::raw('date'),
            'end_date' => DB::raw('date'),
        ]);

        Schema::table('holiday_calendars', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });

        Schema::table('holiday_calendars', function (Blueprint $table) {
            $table->dropUnique(['date']);
            $table->dropColumn('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('holiday_calendars', function (Blueprint $table) {
            $table->date('date')->nullable()->after('name');
        });

        DB::table('holiday_calendars')->update([
            'date' => DB::raw('start_date'),
        ]);

        Schema::table('holiday_calendars', function (Blueprint $table) {
            $table->date('date')->nullable(false)->unique()->change();
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
