<?php
namespace Modules\Schedule\Application\InitializeMonthlySchedule;

use App\Http\Controllers\Controller;
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

            // return redirect()->route('monthly-schedule.index')
            return redirect()->route('schedule.index')

                ->with('success', "Da khoi tao {$result['created_schedules']} lich thang thanh cong.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
