<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_backfill_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('feature', 100);
            $table->nullableMorphs('loggable');
            $table->foreignId('admin_id')->constrained('users');
            $table->text('reason');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['feature', 'created_at'], 'admin_backfill_logs_feature_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_backfill_logs');
    }
};
