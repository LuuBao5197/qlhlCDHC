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
        // Moi lop co the co dung 1 tai khoan xem lich lop (chua cap thi de null).
        if (! Schema::hasColumn('classes', 'account_user_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->foreignId('account_user_id')
                    ->nullable()
                    ->unique()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        $now = now();
        DB::table('roles')->updateOrInsert(
            ['slug' => 'class_account'],
            ['name' => 'Tài khoản lớp', 'created_at' => $now, 'updated_at' => $now]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('classes', 'account_user_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_user_id');
            });
        }

        $roleId = DB::table('roles')->where('slug', 'class_account')->value('id');
        if ($roleId !== null) {
            DB::table('role_user')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
