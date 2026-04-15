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
        Schema::table('departments', function (Blueprint $table) {
            $table->unique('name', 'departments_name_unique');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->unique('name', 'classes_name_unique');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->unique('name', 'subjects_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_name_unique');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_name_unique');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique('departments_name_unique');
        });
    }
};
