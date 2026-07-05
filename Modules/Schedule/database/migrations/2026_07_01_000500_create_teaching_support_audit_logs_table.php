<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_support_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id')->nullable();
            $table->unsignedBigInteger('request_item_id')->nullable();
            $table->unsignedBigInteger('schedule_slot_id')->nullable();
            $table->unsignedBigInteger('change_request_id')->nullable();
            $table->string('action', 80)->index();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_name_snapshot')->nullable();
            $table->string('actor_role_snapshot')->nullable();
            $table->unsignedBigInteger('actor_department_id')->nullable();
            $table->string('actor_department_name_snapshot')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();

            $table->index(['request_id', 'occurred_at'], 'tsal_request_occurred_index');
            $table->index(['change_request_id', 'occurred_at'], 'tsal_change_occurred_index');
            $table->foreign('request_id', 'fk_tsal_request')
                ->references('id')
                ->on('teaching_support_requests')
                ->cascadeOnDelete();
            $table->foreign('request_item_id', 'fk_tsal_request_item')
                ->references('id')
                ->on('teaching_support_request_items')
                ->cascadeOnDelete();
            $table->foreign('schedule_slot_id', 'fk_tsal_slot')
                ->references('id')
                ->on('schedule_slots')
                ->cascadeOnDelete();
            $table->foreign('change_request_id', 'fk_tsal_change')
                ->references('id')
                ->on('teaching_support_change_requests')
                ->cascadeOnDelete();
            $table->foreign('actor_user_id', 'fk_tsal_actor')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('actor_department_id', 'fk_tsal_actor_dept')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_support_audit_logs');
    }
};
