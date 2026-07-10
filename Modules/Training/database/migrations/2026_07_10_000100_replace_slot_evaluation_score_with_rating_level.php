<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slot_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('slot_evaluations', 'rating_level')) {
                $table->enum('rating_level', ['tot', 'kha', 'trung_binh', 'yeu'])
                    ->after('absent_count');
            }

            if (Schema::hasColumn('slot_evaluations', 'score')) {
                $table->dropColumn('score');
            }
        });
    }

    public function down(): void
    {
        Schema::table('slot_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('slot_evaluations', 'rating_level')) {
                $table->dropColumn('rating_level');
            }
        });
    }
};
