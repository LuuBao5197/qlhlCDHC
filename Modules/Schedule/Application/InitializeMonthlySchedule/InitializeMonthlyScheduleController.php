<?php

namespace Modules\Schedule\Application\InitializeMonthlySchedule;

use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\Plans;

class InitializeMonthlyScheduleController extends Controller
{
    public function __construct(
        private InitializeMonthlyScheduleHandler $handler
    ) {}

    public function showForm()
    {
        return view('schedule::initialize-monthly-schedule-form', [
            'activePlans' => Plans::whereIn('status', ['draft', 'submitted', 'approved'])->get(),
        ]);
    }

    public function __invoke(InitializeMonthlyScheduleRequest $request)
    {
        try {
            $result = $this->handler->handle($request);

            return redirect()->route('schedule.index')
                ->with(
                    'success',
                    "Da xu ly {$result['processed_plans']} ke hoach, tao moi {$result['created_schedules']} lich thang va {$result['created_slots']} tiet hoc."
                );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
