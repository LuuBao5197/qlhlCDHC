<?php

namespace Modules\Schedule\Application\GetSchedule;

use Modules\Schedule\Application\Shared\ChangeRequestPageDataBuilder;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;

class GetScheduleHandler
{
    /**
     * Handle the get schedules request.
     */
    public function __construct(
        private ChangeRequestPageDataBuilder $changeRequestPageDataBuilder
    ) {}

    public function handle(GetScheduleRequest $request)
    {
        // Get all schedules with pagination
        $perPage = $request->input('per_page', 15);
        $schedules = Plans::query()
            ->with(['createdBy', 'submittedBy', 'trainingBatch.trainingProgram', 'adminBackfillLog.admin'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $changeRequestPageData = $this->changeRequestPageDataBuilder->build($request->user(), true);

        $statusStyles = [
            'draft' => 'badge-dark',
            'submitted' => 'badge-info',
            'returned' => 'badge-warning',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
        ];

        return view('schedule::index', [
            'schedules' => $schedules,
            'statusStyles' => $statusStyles,
            ...$changeRequestPageData,
        ]);
    }
}
