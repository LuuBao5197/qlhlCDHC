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
        if (! Schema::hasTable('change_request_items')) {
            Schema::create('change_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('change_request_id')->constrained('change_requests')->cascadeOnDelete();
                $table->foreignId('schedule_slot_id')->nullable()->constrained('schedule_slots')->nullOnDelete();
                $table->json('old_payload')->nullable();
                $table->json('new_payload')->nullable();
                $table->string('apply_status')->nullable()->index();
                $table->text('apply_error')->nullable();
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();

                $table->index(['change_request_id', 'schedule_slot_id'], 'change_req_item_change_req_slot_idx');
            });
        }

        if (Schema::hasTable('change_requests')) {
            if (! Schema::hasColumn('change_requests', 'apply_mode')) {
                Schema::table('change_requests', function (Blueprint $table) {
                    $table->string('apply_mode')->default('all_or_none')->after('status');
                });
            }

            if (! Schema::hasColumn('change_requests', 'apply_changes')) {
                Schema::table('change_requests', function (Blueprint $table) {
                    $table->boolean('apply_changes')->default(true)->after('apply_mode');
                });
            }

            if (! Schema::hasColumn('change_requests', 'apply_summary')) {
                Schema::table('change_requests', function (Blueprint $table) {
                    $table->json('apply_summary')->nullable()->after('apply_changes');
                });
            }

            $changeRequests = DB::table('change_requests')->orderBy('id')->get();

            foreach ($changeRequests as $changeRequest) {
                $hasSlot = $changeRequest->schedule_slot_id !== null;
                $hasPayload = $changeRequest->old_payload !== null || $changeRequest->new_payload !== null;

                if (! $hasSlot && ! $hasPayload) {
                    continue;
                }

                $alreadyHasItem = DB::table('change_request_items')
                    ->where('change_request_id', $changeRequest->id)
                    ->exists();

                if ($alreadyHasItem) {
                    continue;
                }

                DB::table('change_request_items')->insert([
                    'change_request_id' => $changeRequest->id,
                    'schedule_slot_id' => $changeRequest->schedule_slot_id,
                    'old_payload' => $changeRequest->old_payload,
                    'new_payload' => $changeRequest->new_payload,
                    'apply_status' => null,
                    'apply_error' => null,
                    'applied_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('change_requests')->whereNull('apply_mode')->update(['apply_mode' => 'all_or_none']);
            DB::table('change_requests')->whereNull('apply_changes')->update(['apply_changes' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('change_requests')) {
            if (Schema::hasColumn('change_requests', 'apply_summary')) {
                Schema::table('change_requests', function (Blueprint $table) {
                    $table->dropColumn('apply_summary');
                });
            }

            if (Schema::hasColumn('change_requests', 'apply_changes')) {
                Schema::table('change_requests', function (Blueprint $table) {
                    $table->dropColumn('apply_changes');
                });
            }

            if (Schema::hasColumn('change_requests', 'apply_mode')) {
                Schema::table('change_requests', function (Blueprint $table) {
                    $table->dropColumn('apply_mode');
                });
            }
        }

        Schema::dropIfExists('change_request_items');
    }
};
