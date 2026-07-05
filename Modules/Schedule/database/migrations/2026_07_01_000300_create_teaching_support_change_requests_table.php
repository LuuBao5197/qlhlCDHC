<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_support_change_requests')) {
            return;
        }

        Schema::create('teaching_support_change_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teaching_support_request_id');
            $table->unsignedBigInteger('requesting_department_id');
            $table->unsignedBigInteger('proposed_supporting_department_id')->nullable();
            $table->unsignedBigInteger('assigned_supporting_department_id')->nullable();
            $table->string('status', 40)->default('pending_pdt')->index();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('pdt_processed_by')->nullable();
            $table->timestamp('pdt_processed_at')->nullable();
            $table->text('pdt_note')->nullable();
            $table->unsignedInteger('revision_no')->default(1);
            $table->timestamps();

            $table->index(['teaching_support_request_id', 'status'], 'tscr_request_status_index');
            $table->index(['requesting_department_id', 'status'], 'tscr_requesting_status_index');
            $table->index(['assigned_supporting_department_id', 'status'], 'tscr_assigned_status_index');
            $table->unique(['teaching_support_request_id', 'revision_no'], 'tscr_request_revision_unique');
            $table->foreign('teaching_support_request_id', 'fk_tscr_request')
                ->references('id')
                ->on('teaching_support_requests')
                ->cascadeOnDelete();
            $table->foreign('requesting_department_id', 'fk_tscr_request_dept')
                ->references('id')
                ->on('departments')
                ->cascadeOnDelete();
            $table->foreign('submitted_by', 'fk_tscr_submitted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('pdt_processed_by', 'fk_tscr_pdt_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_support_change_requests');
    }
};
