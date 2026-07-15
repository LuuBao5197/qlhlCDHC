<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table): void {
            $table->index(['date', 'teacher_id', 'class_id'], 'schedule_slots_report_date_teacher_class_idx');
            $table->index(['date', 'subject_id', 'subject_lesson_id'], 'schedule_slots_report_date_subject_lesson_idx');
        });

        Schema::table('slot_evaluations', function (Blueprint $table): void {
            $table->index(['rating_level', 'schedule_slot_id'], 'slot_evaluations_report_rating_slot_idx');
        });
    }

    public function down(): void
    {
        Schema::table('slot_evaluations', function (Blueprint $table): void {
            $table->dropIndex('slot_evaluations_report_rating_slot_idx');
        });

        Schema::table('schedule_slots', function (Blueprint $table): void {
            $table->dropIndex('schedule_slots_report_date_teacher_class_idx');
            $table->dropIndex('schedule_slots_report_date_subject_lesson_idx');
        });
    }
};
