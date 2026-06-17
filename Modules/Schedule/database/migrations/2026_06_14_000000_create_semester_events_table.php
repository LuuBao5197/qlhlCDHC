<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semester_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('title', 255);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('period_from')->nullable();
            $table->unsignedTinyInteger('period_to')->nullable();
            $table->string('color', 20)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['plan_id', 'start_date', 'end_date'], 'semester_events_plan_date_idx');
            $table->index(['plan_id', 'event_type'], 'semester_events_plan_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_events');
    }
};
