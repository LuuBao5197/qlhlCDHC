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
        Schema::table('users', function (Blueprint $table) {
            $table->string('requested_role')->nullable()->after('role');
            $table->foreignId('requested_department_id')
                ->nullable()
                ->after('department_id')
                ->constrained('departments')
                ->nullOnDelete();
        });

        DB::table('users')
            ->where('status', 'pending')
            ->update([
                'requested_role' => DB::raw('role'),
                'requested_department_id' => DB::raw('department_id'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_department_id');
            $table->dropColumn('requested_role');
        });
    }
};
