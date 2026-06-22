<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('schedule_slots', 'template_id')) {
            return;
        }

        try {
            Schema::table('schedule_slots', function (Blueprint $table) {
                $table->dropForeign('schedule_slots_template_id_foreign');
            });
        } catch (\Exception $e) {
        }

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropColumn('template_id');
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
