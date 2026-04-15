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
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('plan_id')->constrained('departments')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->after('class_name')->constrained('classes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('year')->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft')->after('created_by')->index();
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');

            $table->unique(
                ['plan_id', 'class_id', 'month', 'year'],
                'monthly_schedules_plan_class_month_year_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->dropUnique('monthly_schedules_plan_class_month_year_unique');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('class_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'status',
                'submitted_at',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
