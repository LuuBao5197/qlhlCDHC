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

            ChangeRequest::updateOrCreate(
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
                    'submitted_at' => now(),
                    'resolved_at' => null,
                ]
            );
        });
    }
}
