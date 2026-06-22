<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->string('slot_type', 20)
                ->default('subject')
                ->after('room_id')
                ->index();
            $table->foreignId('semester_event_id')
                ->nullable()
                ->after('slot_type')
                ->constrained('semester_events')
                ->nullOnDelete();
            $table->string('event_type', 50)
                ->nullable()
                ->after('semester_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_event_id');
            $table->dropColumn([
                'slot_type',
                'event_type',
            ]);
        });
    }
};
