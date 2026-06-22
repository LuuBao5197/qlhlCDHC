<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_slot_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_schedule_id')
                ->constrained('monthly_schedules')
                ->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('period_number');
            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();
            $table->foreignId('subject_lesson_id')
                ->nullable()
                ->constrained('subject_lessons')
                ->nullOnDelete();
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('teachers')
                ->nullOnDelete();
            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['monthly_schedule_id', 'date', 'period_number', 'subject_id', 'subject_lesson_id'],
                'schedule_slot_groups_lookup_index'
            );
        });

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->foreignId('schedule_slot_group_id')
                ->nullable()
                ->after('monthly_schedule_id')
                ->constrained('schedule_slot_groups')
                ->nullOnDelete();

            $table->index('schedule_slot_group_id', 'schedule_slots_schedule_slot_group_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropIndex('schedule_slots_schedule_slot_group_id_index');
            $table->dropConstrainedForeignId('schedule_slot_group_id');
        });

        Schema::dropIfExists('schedule_slot_groups');
    }
};
