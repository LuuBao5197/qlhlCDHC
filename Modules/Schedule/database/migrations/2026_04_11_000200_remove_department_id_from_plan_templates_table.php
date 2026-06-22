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
        if (! Schema::hasColumn('plan_templates', 'department_id')) {
            return;
        }

        try {
            Schema::table('plan_templates', function (Blueprint $table) {
                $table->dropForeign('plan_templates_department_id_foreign');
            });
        } catch (\Exception $e) {
        }

        Schema::table('plan_templates', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_templates', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }
};
