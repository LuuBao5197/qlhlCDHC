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
        Schema::create('daily_training_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_slot_id')->constrained('schedule_slots')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('actual_date')->nullable();
            $table->unsignedTinyInteger('actual_period_number')->nullable();
            $table->string('result_status')->default('recorded')->index();
            $table->text('actual_content')->nullable();
            $table->text('issue_note')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['actual_date', 'result_status'], 'daily_training_logs_date_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_training_logs');
    }
};
