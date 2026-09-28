<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_slot_subgroups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_slot_id')
                ->constrained('schedule_slots')
                ->cascadeOnDelete();
            $table->string('group_label', 100);
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();
            $table->foreignId('room_id')
                ->nullable()
                ->constrained('rooms')
                ->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['schedule_slot_id', 'teacher_id'], 'schedule_slot_subgroups_slot_teacher_unique');
            $table->index('schedule_slot_id', 'schedule_slot_subgroups_slot_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_slot_subgroups');
    }
};
