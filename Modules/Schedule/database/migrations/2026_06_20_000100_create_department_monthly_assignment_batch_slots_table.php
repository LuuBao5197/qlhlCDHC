<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_monthly_assignment_batch_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('department_monthly_assignment_batches', 'id', 'dmabs_batch_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('schedule_slot_id')
                ->constrained('schedule_slots', 'id', 'dmabs_schedule_slot_id_foreign')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'schedule_slot_id'], 'department_monthly_assignment_batch_slots_unique');
            $table->index('schedule_slot_id', 'department_monthly_assignment_batch_slots_schedule_slot_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_monthly_assignment_batch_slots');
    }
};
