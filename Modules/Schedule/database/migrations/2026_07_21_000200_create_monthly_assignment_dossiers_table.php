<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_assignment_dossiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('year');
            $table->string('status', 30)->default('draft')->index();
            $table->string('current_step', 30)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['month', 'year'], 'monthly_assignment_dossiers_month_year_unique');
        });

        Schema::create('monthly_assignment_dossier_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained('monthly_assignment_dossiers')->cascadeOnDelete();
            // Ten rang buoc mac dinh (monthly_assignment_dossier_batches_department_monthly_assignment_batch_id_foreign)
            // dai hon 64 ky tu, MySQL se tu choi tao migration nen phai dat ten rut gon thu cong.
            $table->foreignId('department_monthly_assignment_batch_id')
                ->constrained(
                    table: 'department_monthly_assignment_batches',
                    indexName: 'monthly_assignment_dossier_batches_batch_id_foreign'
                )
                ->cascadeOnDelete();
            $table->unsignedInteger('batch_version')->default(1);
            $table->timestamps();

            $table->unique(
                ['dossier_id', 'department_monthly_assignment_batch_id'],
                'monthly_assignment_dossier_batches_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_assignment_dossier_batches');
        Schema::dropIfExists('monthly_assignment_dossiers');
    }
};
