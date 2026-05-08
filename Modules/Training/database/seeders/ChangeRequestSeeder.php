<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Schedule\Models\MonthlySchedule;

class ChangeRequestSeeder extends Seeder
{
    public function run(): void
    {
        $requestedBy = User::where('email', 'department@example.com')->first() ?? User::query()->first();

        MonthlySchedule::query()->with('scheduleSlots')->take(2)->get()->each(function (MonthlySchedule $monthlySchedule) use ($requestedBy) {
            $slot = $monthlySchedule->scheduleSlots->first();
            $secondSlot = $monthlySchedule->scheduleSlots->skip(1)->first();

            $changeRequest = ChangeRequest::updateOrCreate(
                [
                    'monthly_schedule_id' => $monthlySchedule->id,
                    'schedule_slot_id' => $slot?->id,
                ],
                [
                    'requested_by' => $requestedBy?->id,
                    'reason' => 'Dieu chinh noi dung va phong hoc de phu hop thuc te',
                    'old_payload' => ['subject' => $slot?->subject, 'room_id' => $slot?->room_id],
                    'new_payload' => ['subject' => $slot?->subject, 'room_id' => $slot?->room_id],
                    'status' => 'pending',
                    'apply_mode' => 'all_or_none',
                    'apply_changes' => true,
                    'apply_summary' => null,
                    'submitted_at' => now(),
                    'resolved_at' => null,
                ]
            );

            $changeRequest->changeRequestItems()->delete();

            collect([$slot, $secondSlot])
                ->filter()
                ->each(function ($targetSlot) use ($changeRequest): void {
                    $changeRequest->changeRequestItems()->create([
                        'schedule_slot_id' => $targetSlot->id,
                        'old_payload' => [
                            'room_id' => $targetSlot->room_id,
                            'content' => $targetSlot->content,
                        ],
                        'new_payload' => [
                            'room_id' => $targetSlot->room_id,
                            'content' => $targetSlot->content,
                        ],
                        'apply_status' => null,
                        'apply_error' => null,
                        'applied_at' => null,
                    ]);
                });
        });
    }
}
