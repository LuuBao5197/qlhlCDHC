<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_UNIQUE = 'subject_lessons_subject_lesson_unique';

    private const NEW_UNIQUE = 'subject_lessons_subject_program_lesson_unique';

    /**
     * Run the migrations.
     *
     * Every step is guarded so this migration can be safely re-run if it
     * previously failed partway through (MySQL DDL statements auto-commit,
     * so a mid-migration failure can otherwise leave the schema half-applied).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('subject_lessons', 'training_program_id')) {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->foreignId('training_program_id')
                    ->nullable()
                    ->after('subject_id')
                    ->constrained('training_programs')
                    ->cascadeOnDelete();
            });
        }

        // Backfill existing lessons using the (only) training program already
        // linked to their subject, when one can be determined unambiguously.
        DB::table('subject_lessons')
            ->whereNull('training_program_id')
            ->orderBy('id')
            ->chunkById(500, function ($lessons) {
                foreach ($lessons as $lesson) {
                    $linkedProgramIds = DB::table('subject_training_program')
                        ->where('subject_id', $lesson->subject_id)
                        ->pluck('training_program_id');

                    if ($linkedProgramIds->count() === 1) {
                        DB::table('subject_lessons')
                            ->where('id', $lesson->id)
                            ->update(['training_program_id' => $linkedProgramIds->first()]);
                    }
                }
            });

        // The new unique index must be created before the old one is dropped:
        // MySQL uses subject_lessons_subject_lesson_unique (subject_id, lesson_no)
        // to satisfy the subject_id foreign key, and refuses to drop it while it's
        // the only index covering that column.
        if (!$this->hasIndex('subject_lessons', self::NEW_UNIQUE)) {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->unique(['subject_id', 'training_program_id', 'lesson_no'], self::NEW_UNIQUE);
            });
        }

        if ($this->hasIndex('subject_lessons', self::OLD_UNIQUE)) {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->dropUnique(self::OLD_UNIQUE);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Same ordering constraint as up(): create the covering index for the
        // subject_id foreign key before dropping the one currently providing it.
        if (!$this->hasIndex('subject_lessons', self::OLD_UNIQUE)) {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->unique(['subject_id', 'lesson_no'], self::OLD_UNIQUE);
            });
        }

        if ($this->hasIndex('subject_lessons', self::NEW_UNIQUE)) {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->dropUnique(self::NEW_UNIQUE);
            });
        }

        if (Schema::hasColumn('subject_lessons', 'training_program_id')) {
            Schema::table('subject_lessons', function (Blueprint $table) {
                $table->dropConstrainedForeignId('training_program_id');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};
