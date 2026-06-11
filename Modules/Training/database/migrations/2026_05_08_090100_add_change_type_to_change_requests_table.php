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
        if (! Schema::hasTable('change_requests') || Schema::hasColumn('change_requests', 'change_type')) {
            return;
        }

        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('change_type')->default('general')->after('status')->index();
        });

        DB::table('change_requests')->whereNull('change_type')->update([
            'change_type' => 'general',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('change_requests') || ! Schema::hasColumn('change_requests', 'change_type')) {
            return;
        }

        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropIndex(['change_type']);
            $table->dropColumn('change_type');
        });
    }
};
