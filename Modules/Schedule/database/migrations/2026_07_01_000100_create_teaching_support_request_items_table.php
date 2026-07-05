<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_support_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('teaching_support_requests')->cascadeOnDelete();
            $table->foreignId('schedule_slot_id')->constrained('schedule_slots')->cascadeOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('assigned_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['request_id', 'schedule_slot_id'], 'teaching_support_request_items_unique');
            $table->index(['schedule_slot_id', 'status'], 'teaching_support_request_items_slot_status_index');
            $table->index(['assigned_teacher_id', 'status'], 'teaching_support_request_items_teacher_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_support_request_items');
    }
};
