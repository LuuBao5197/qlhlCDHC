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
        Schema::table('plan_templates', function (Blueprint $table) {
            if (Schema::hasColumn('plan_templates', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_templates', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->after('subject_id')
                ->constrained('departments')
                ->onDelete('restrict');
        });
    }
};
