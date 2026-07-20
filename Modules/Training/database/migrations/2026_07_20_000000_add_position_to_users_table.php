<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position')->nullable()->after('role')->index();
        });

        // Backfill cho dữ liệu dev sẵn có (nếu có) — giữ nguyên thẩm quyền duyệt hiện tại
        // bằng cách gán vị trí cao nhất trong role, không ảnh hưởng gì trên DB rỗng.
        DB::table('users')->where('role', 'department_staff')->whereNull('position')->update(['position' => 'department_head']);
        DB::table('users')->where('role', 'training_office')->whereNull('position')->update(['position' => 'training_head']);
        DB::table('users')->where('role', 'leadership')->whereNull('position')->update(['position' => 'principal']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
