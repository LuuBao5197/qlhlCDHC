<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('department_id')
                ->constrained('users')
                ->nullOnDelete()
                ->unique();
        });

        $teacherMappings = DB::table('teachers as t')
            ->join('users as u', 'u.employee_code', '=', 't.teacher_code')
            ->whereNull('t.user_id')
            ->where('u.role', 'teacher')
            ->select('t.id as teacher_id', 'u.id as user_id')
            ->get();

        foreach ($teacherMappings as $mapping) {
            DB::table('teachers')
                ->where('id', (int) $mapping->teacher_id)
                ->update(['user_id' => (int) $mapping->user_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropUnique('teachers_user_id_unique');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
