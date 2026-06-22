<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_monthly_assignment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('year');
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['department_id', 'month', 'year'], 'department_monthly_assignment_batches_unique');
            $table->index(['department_id', 'status'], 'department_monthly_assignment_batches_department_status_index');
            $table->index(['month', 'year'], 'department_monthly_assignment_batches_month_year_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_monthly_assignment_batches');
    }
};
