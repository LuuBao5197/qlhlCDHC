<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_slots', 'assignment_source')) {
                $table->string('assignment_source', 30)
                    ->default('internal')
                    ->after('teacher_id')
                    ->index();
            }

            if (! Schema::hasColumn('schedule_slots', 'teaching_support_request_item_id')) {
                $table->foreignId('teaching_support_request_item_id')
                    ->nullable()
                    ->after('assignment_source')
                    ->constrained('teaching_support_request_items')
                    ->nullOnDelete();
            } elseif (! $this->foreignKeyExists('schedule_slots', 'schedule_slots_teaching_support_request_item_id_foreign')) {
                $table->foreign('teaching_support_request_item_id', 'schedule_slots_teaching_support_request_item_id_foreign')
                    ->references('id')
                    ->on('teaching_support_request_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teaching_support_request_item_id');
            $table->dropColumn('assignment_source');
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::raw('database()'))
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
