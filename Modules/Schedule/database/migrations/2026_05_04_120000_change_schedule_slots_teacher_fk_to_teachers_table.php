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
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });

        // Ensure existing values are valid for the new FK target.
        DB::table('schedule_slots')
            ->whereNotNull('teacher_id')
            ->whereNotIn('teacher_id', DB::table('teachers')->select('id'))
            ->update(['teacher_id' => null]);

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
        });

        // Ensure existing values are valid for rollback FK target.
        DB::table('schedule_slots')
            ->whereNotNull('teacher_id')
            ->whereNotIn('teacher_id', DB::table('users')->select('id'))
            ->update(['teacher_id' => null]);

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->foreign('teacher_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
