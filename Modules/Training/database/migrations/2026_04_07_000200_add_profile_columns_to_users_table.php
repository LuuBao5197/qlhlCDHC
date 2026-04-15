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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('status')->constrained('departments')->nullOnDelete();
            $table->string('employee_code')->nullable()->after('department_id');
            $table->string('phone')->nullable()->after('employee_code');

            $table->unique('employee_code', 'users_employee_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_employee_code_unique');
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['employee_code', 'phone']);
        });
    }
};
