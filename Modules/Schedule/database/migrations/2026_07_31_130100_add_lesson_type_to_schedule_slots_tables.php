<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table): void {
            if (! Schema::hasColumn('schedule_slots', 'lesson_type')) {
                $table->string('lesson_type', 30)
                    ->nullable()
                    ->after('assignment_type')
                    ->index();
            }
        });

        Schema::table('schedule_slot_groups', function (Blueprint $table): void {
            if (! Schema::hasColumn('schedule_slot_groups', 'lesson_type')) {
                $table->string('lesson_type', 30)
                    ->nullable()
                    ->after('assignment_type')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table): void {
            if (Schema::hasColumn('schedule_slots', 'lesson_type')) {
                $table->dropIndex(['lesson_type']);
                $table->dropColumn('lesson_type');
            }
        });

        Schema::table('schedule_slot_groups', function (Blueprint $table): void {
            if (Schema::hasColumn('schedule_slot_groups', 'lesson_type')) {
                $table->dropIndex(['lesson_type']);
                $table->dropColumn('lesson_type');
            }
        });
    }
};
