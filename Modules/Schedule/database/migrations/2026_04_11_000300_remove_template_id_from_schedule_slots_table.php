<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            if (Schema::hasColumn('schedule_slots', 'template_id')) {
                $table->dropForeign(['template_id']);
                $table->dropColumn('template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->foreignId('template_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('plan_templates')
                ->onDelete('set null');
        });
    }
};
