<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_lessons', function (Blueprint $table): void {
            if (! Schema::hasColumn('subject_lessons', 'is_regular_test')) {
                $table->boolean('is_regular_test')
                    ->default(false)
                    ->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subject_lessons', function (Blueprint $table): void {
            if (Schema::hasColumn('subject_lessons', 'is_regular_test')) {
                $table->dropColumn('is_regular_test');
            }
        });
    }
};
