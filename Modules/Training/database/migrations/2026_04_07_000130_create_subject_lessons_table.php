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
        Schema::create('subject_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedInteger('lesson_no');
            $table->string('title');
            $table->unsignedInteger('expected_periods')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['subject_id', 'lesson_no'], 'subject_lessons_subject_lesson_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_lessons');
    }
};
