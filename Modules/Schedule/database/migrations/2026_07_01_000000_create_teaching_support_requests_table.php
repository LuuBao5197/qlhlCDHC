<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_support_requests')) {
            $this->repairExistingTable();

            return;
        }

        Schema::create('teaching_support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_batch_id')->constrained('department_monthly_assignment_batches')->cascadeOnDelete();
            $table->foreignId('requesting_department_id')->constrained('departments')->cascadeOnDelete();
            $table->unsignedBigInteger('proposed_supporting_department_id');
            $table->unsignedBigInteger('assigned_supporting_department_id')->nullable();
            $table->string('status', 40)->default('pending_pdt')->index();
            $table->text('request_note')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('pdt_processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pdt_processed_at')->nullable();
            $table->text('pdt_note')->nullable();
            $table->timestamps();

            $table->index(['assignment_batch_id', 'status'], 'teaching_support_requests_batch_status_index');
            $table->index(['requesting_department_id', 'status'], 'teaching_support_requests_requesting_status_index');
            $table->index(['assigned_supporting_department_id', 'status'], 'teaching_support_requests_assigned_status_index');
            $table->index('proposed_supporting_department_id', 'tsr_proposed_supporting_department_idx');
            $table->index('assigned_supporting_department_id', 'tsr_assigned_supporting_department_idx');

            $table->foreign('proposed_supporting_department_id', 'tsr_proposed_supporting_department_fk')
                ->references('id')
                ->on('departments')
                ->cascadeOnDelete();

            $table->foreign('assigned_supporting_department_id', 'tsr_assigned_supporting_department_fk')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_support_requests');
    }

    private function repairExistingTable(): void
    {
        Schema::table('teaching_support_requests', function (Blueprint $table) {
            if (! $this->indexExists('teaching_support_requests', 'teaching_support_requests_status_index')) {
                $table->index('status', 'teaching_support_requests_status_index');
            }

            if (! $this->indexExists('teaching_support_requests', 'teaching_support_requests_batch_status_index')) {
                $table->index(['assignment_batch_id', 'status'], 'teaching_support_requests_batch_status_index');
            }

            if (! $this->indexExists('teaching_support_requests', 'teaching_support_requests_requesting_status_index')) {
                $table->index(['requesting_department_id', 'status'], 'teaching_support_requests_requesting_status_index');
            }

            if (! $this->indexExists('teaching_support_requests', 'teaching_support_requests_assigned_status_index')) {
                $table->index(['assigned_supporting_department_id', 'status'], 'teaching_support_requests_assigned_status_index');
            }

            if (! $this->indexExists('teaching_support_requests', 'tsr_proposed_supporting_department_idx')) {
                $table->index('proposed_supporting_department_id', 'tsr_proposed_supporting_department_idx');
            }

            if (! $this->indexExists('teaching_support_requests', 'tsr_assigned_supporting_department_idx')) {
                $table->index('assigned_supporting_department_id', 'tsr_assigned_supporting_department_idx');
            }

            if (! $this->indexExists('teaching_support_requests', 'teaching_support_requests_submitted_by_foreign')) {
                $table->index('submitted_by', 'teaching_support_requests_submitted_by_foreign');
            }

            if (! $this->indexExists('teaching_support_requests', 'teaching_support_requests_pdt_processed_by_foreign')) {
                $table->index('pdt_processed_by', 'teaching_support_requests_pdt_processed_by_foreign');
            }

            if (! $this->foreignKeyExists('teaching_support_requests', 'tsr_proposed_supporting_department_fk')) {
                $table->foreign('proposed_supporting_department_id', 'tsr_proposed_supporting_department_fk')
                    ->references('id')
                    ->on('departments')
                    ->cascadeOnDelete();
            }

            if (! $this->foreignKeyExists('teaching_support_requests', 'tsr_assigned_supporting_department_fk')) {
                $table->foreign('assigned_supporting_department_id', 'tsr_assigned_supporting_department_fk')
                    ->references('id')
                    ->on('departments')
                    ->nullOnDelete();
            }

            if (! $this->foreignKeyExists('teaching_support_requests', 'teaching_support_requests_submitted_by_foreign')) {
                $table->foreign('submitted_by', 'teaching_support_requests_submitted_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if (! $this->foreignKeyExists('teaching_support_requests', 'teaching_support_requests_pdt_processed_by_foreign')) {
                $table->foreign('pdt_processed_by', 'teaching_support_requests_pdt_processed_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::raw('database()'))
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::raw('database()'))
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
