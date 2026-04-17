<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'department_id')) {
                try {
                    $table->dropForeign(['department_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('code')
                ->constrained('departments')
                ->onDelete('set null');
        });
    }
};
