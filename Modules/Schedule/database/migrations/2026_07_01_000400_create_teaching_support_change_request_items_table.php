<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_support_change_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('change_request_id');
            $table->unsignedBigInteger('support_request_item_id')->nullable();
            $table->unsignedBigInteger('schedule_slot_id');
            $table->string('action', 20)->index();
            $table->json('old_snapshot')->nullable();
            $table->json('proposed_snapshot')->nullable();
            $table->boolean('requires_reconfirmation')->default(false);
            $table->unsignedBigInteger('previous_teacher_id')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['change_request_id', 'status'], 'tscri_change_status_index');
            $table->index(['schedule_slot_id', 'action'], 'tscri_slot_action_index');
            $table->foreign('change_request_id', 'fk_tscri_change')
                ->references('id')
                ->on('teaching_support_change_requests')
                ->cascadeOnDelete();
            $table->foreign('support_request_item_id', 'fk_tscri_request_item')
                ->references('id')
                ->on('teaching_support_request_items')
                ->nullOnDelete();
            $table->foreign('schedule_slot_id', 'fk_tscri_slot')
                ->references('id')
                ->on('schedule_slots')
                ->cascadeOnDelete();
            $table->foreign('previous_teacher_id', 'fk_tscri_prev_teacher')
                ->references('id')
                ->on('teachers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_support_change_request_items');
    }
};
