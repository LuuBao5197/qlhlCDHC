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
        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('monthly_schedule_id')
                ->constrained('monthly_schedules')
                ->cascadeOnDelete();

            $table->dateTime('date');
            $table->integer('day_of_week')->nullable();
            $table->string('period');
            $table->string('subject');
            $table->string('content')->nullable();

            $table->timestamps();

            // Index tối ưu
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_slots');
    }
};
