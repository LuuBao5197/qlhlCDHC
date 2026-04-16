<?php

namespace Modules\Schedule\Application\DeleteScheduleSemester;

use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\ScheduleSlot;
use Exception;

class DeleteScheduleSemesterHandler
{
    public function handle(DeleteScheduleSemesterRequest $request, $id)
    {
        $plan = Plans::findOrFail($id);

        try {
            DB::transaction(function () use ($plan) {
                // Delete all schedule slots related to this plan
                $monthlyScheduleIds = $plan->monthlySchedules()->pluck('id')->toArray();
                if (!empty($monthlyScheduleIds)) {
                    ScheduleSlot::whereIn('monthly_schedule_id', $monthlyScheduleIds)->delete();
                }

                // Delete all monthly schedules
                $plan->monthlySchedules()->delete();

                // Delete all plan templates
                $plan->planTemplates()->delete();

                // Delete the plan itself
                $plan->delete();
            });

            return redirect()->route('schedule.index')
                ->with('success', 'Da xoa ke hoach hoc ky va tat ca du lieu lien quan thanh cong.');
        } catch (Exception $e) {
            return back()
                ->with('error', 'Co loi khi xoa ke hoach: ' . $e->getMessage());
        }
    }
}
