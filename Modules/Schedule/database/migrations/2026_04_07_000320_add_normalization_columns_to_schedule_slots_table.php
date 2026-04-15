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
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('monthly_schedule_id')->constrained('classes')->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->after('class_id')->constrained('users')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->after('teacher_id')->constrained('subjects')->nullOnDelete();
            $table->foreignId('subject_lesson_id')->nullable()->after('subject_id')->constrained('subject_lessons')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->after('subject_lesson_id')->constrained('rooms')->nullOnDelete();
            $table->string('slot_status')->default('planned')->after('room_id')->index();
            $table->text('actual_content')->nullable()->after('slot_status');
            $table->text('note')->nullable()->after('actual_content');

            $table->unique(
                ['monthly_schedule_id', 'date', 'period_number'],
                'schedule_slots_schedule_date_period_unique'
            );
            $table->index(['teacher_id', 'date'], 'schedule_slots_teacher_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropUnique('schedule_slots_schedule_date_period_unique');
            $table->dropIndex('schedule_slots_teacher_date_index');
            $table->dropConstrainedForeignId('class_id');
            $table->dropConstrainedForeignId('teacher_id');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropConstrainedForeignId('subject_lesson_id');
            $table->dropConstrainedForeignId('room_id');
            $table->dropColumn([
                'slot_status',
                'actual_content',
                'note',
            ]);
        });
    }
};
