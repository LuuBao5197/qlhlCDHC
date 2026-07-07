<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table): void {
            if (! Schema::hasColumn('schedule_slots', 'assignment_type')) {
                $table->string('assignment_type', 30)
                    ->nullable()
                    ->after('teacher_id')
                    ->index();
            }
        });

        Schema::table('schedule_slot_groups', function (Blueprint $table): void {
            if (! Schema::hasColumn('schedule_slot_groups', 'assignment_type')) {
                $table->string('assignment_type', 30)
                    ->nullable()
                    ->after('teacher_id')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table): void {
            if (Schema::hasColumn('schedule_slots', 'assignment_type')) {
                $table->dropIndex(['assignment_type']);
                $table->dropColumn('assignment_type');
            }
        });

        Schema::table('schedule_slot_groups', function (Blueprint $table): void {
            if (Schema::hasColumn('schedule_slot_groups', 'assignment_type')) {
                $table->dropIndex(['assignment_type']);
                $table->dropColumn('assignment_type');
            }
        });
    }
};
