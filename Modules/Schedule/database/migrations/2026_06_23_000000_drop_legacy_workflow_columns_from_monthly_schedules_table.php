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
        Schema::table('monthly_schedules', function (Blueprint $table): void {
            foreach (['submitted_by', 'approved_by', 'reviewed_by'] as $column) {
                if (! Schema::hasColumn('monthly_schedules', $column)) {
                    continue;
                }

                try {
                    $table->dropConstrainedForeignId($column);
                } catch (\Throwable) {
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Throwable) {
                    }

                    try {
                        $table->dropColumn($column);
                    } catch (\Throwable) {
                    }
                }
            }

            $columnsToDrop = array_values(array_filter([
                'status',
                'submitted_at',
                'approved_at',
                'rejection_reason',
                'reviewed_at',
                'review_note',
                'current_step',
            ], fn (string $column): bool => Schema::hasColumn('monthly_schedules', $column)));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table): void {
            $table->foreignId('submitted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft')->after('submitted_by');
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('approved_by');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable()->after('reviewed_by');
            $table->string('current_step')->default('draft')->after('review_note');
        });
    }
};
