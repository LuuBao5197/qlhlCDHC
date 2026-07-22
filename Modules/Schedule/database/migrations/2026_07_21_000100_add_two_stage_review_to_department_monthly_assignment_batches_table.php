<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'department_monthly_assignment_batches';

        Schema::table($table, function (Blueprint $table) {
            if (Schema::hasColumn('department_monthly_assignment_batches', 'reviewed_by')
                && ! Schema::hasColumn('department_monthly_assignment_batches', 'department_reviewed_by')) {
                $table->renameColumn('reviewed_by', 'department_reviewed_by');
            }
            if (Schema::hasColumn('department_monthly_assignment_batches', 'reviewed_at')
                && ! Schema::hasColumn('department_monthly_assignment_batches', 'department_reviewed_at')) {
                $table->renameColumn('reviewed_at', 'department_reviewed_at');
            }
            if (Schema::hasColumn('department_monthly_assignment_batches', 'review_note')
                && ! Schema::hasColumn('department_monthly_assignment_batches', 'department_review_note')) {
                $table->renameColumn('review_note', 'department_review_note');
            }
        });

        if (! Schema::hasColumn($table, 'current_step')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('current_step', 30)->default('draft')->after('status');
            });
        }

        if (! $this->indexExists($table, 'department_monthly_assignment_batches_current_step_index')) {
            Schema::table($table, function (Blueprint $table) {
                $table->index('current_step');
            });
        }

        if (! Schema::hasColumn($table, 'training_office_reviewed_by')) {
            Schema::table($table, function (Blueprint $table) {
                // Ten rang buoc mac dinh (department_monthly_assignment_batches_training_office_reviewed_by_foreign)
                // dai hon 64 ky tu, MySQL se tu choi tao migration nen phai dat ten rut gon thu cong.
                $table->foreignId('training_office_reviewed_by')->nullable()->after('department_review_note')
                    ->constrained(table: 'users', indexName: 'dmab_training_office_reviewed_by_foreign')
                    ->nullOnDelete();
            });
        } elseif (! $this->indexExists($table, 'dmab_training_office_reviewed_by_foreign')) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreign('training_office_reviewed_by', 'dmab_training_office_reviewed_by_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn($table, 'training_office_reviewed_at')) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('training_office_reviewed_at')->nullable()->after('training_office_reviewed_by');
            });
        }

        if (! Schema::hasColumn($table, 'training_office_review_note')) {
            Schema::table($table, function (Blueprint $table) {
                $table->text('training_office_review_note')->nullable()->after('training_office_reviewed_at');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = Schema::getConnection()->select(
            'SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?',
            [$indexName]
        );

        return count($result) > 0;
    }

    public function down(): void
    {
        Schema::table('department_monthly_assignment_batches', function (Blueprint $table) {
            $table->dropForeign('dmab_training_office_reviewed_by_foreign');
            $table->dropColumn([
                'training_office_reviewed_by',
                'training_office_reviewed_at',
                'training_office_review_note',
                'current_step',
            ]);
        });

        Schema::table('department_monthly_assignment_batches', function (Blueprint $table) {
            $table->renameColumn('department_reviewed_by', 'reviewed_by');
            $table->renameColumn('department_reviewed_at', 'reviewed_at');
            $table->renameColumn('department_review_note', 'review_note');
        });
    }
};
