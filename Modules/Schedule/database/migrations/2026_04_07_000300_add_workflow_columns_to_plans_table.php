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
        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('description')->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->string('status')->default('draft')->after('submitted_at')->index();
            $table->string('current_step')->default('draft')->after('status')->index();
            $table->date('effective_from')->default(now())->after('current_step');
            $table->date('effective_to')->default(now())->after('effective_from');
            $table->unsignedInteger('approved_version')->default(1)->after('effective_to');

            // A unique constraint for (semester, year) is intentionally deferred.
            // The current experimental data already contains duplicate semester/year pairs.
            $table->index(['semester', 'year'], 'plans_semester_year_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex('plans_semester_year_index');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn([
                'submitted_at',
                'status',
                'current_step',
                'effective_from',
                'effective_to',
                'approved_version',
            ]);
        });
    }
};
