<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slot_evaluations', function (Blueprint $table) {
            $table->foreignId('schedule_slot_subgroup_id')
                ->nullable()
                ->after('schedule_slot_id')
                ->constrained('schedule_slot_subgroups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('slot_evaluations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_slot_subgroup_id');
        });
    }
};
