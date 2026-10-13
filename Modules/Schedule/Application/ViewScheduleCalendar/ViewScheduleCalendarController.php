<?php

namespace Modules\Schedule\Application\ViewScheduleCalendar;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\Training\Models\Department;
use Modules\Training\Models\TrainingClass;

class ViewScheduleCalendarController extends Controller
{
    public function __construct(
        private ScheduleCalendarAccess $access,
        private ScheduleCalendarSlotLoader $loader,
        private ScheduleCalendarPresenter $presenter,
    ) {}

    public function __invoke(ViewScheduleCalendarRequest $request)
    {
        /** @var User $user */
        $user = $request->user();
        [$scope, $scopeId] = $request->resolvedScope();
        $period = $request->period();
        $mode = $request->mode();

        $slots = $this->loader->load($scope, $scopeId, $this->access->teacherId($user), $period);
        $calendar = $this->presenter->present($slots, $period);

        $step = $mode === 'day' ? '1 day' : '1 week';
        // Ngay nguoi dung chon (khong phai thu 2 dau tuan) de o chon ngay va nut Truoc/Sau giu dung ngay.
        $anchor = $request->filled('date') ? CarbonImmutable::parse($request->input('date')) : CarbonImmutable::today();

        return view('schedule::calendar', [
            'calendar' => $calendar,
            'period' => $period,
            'mode' => $mode,
            'scope' => $scope,
            'scopeId' => $scopeId,
            'selectedDate' => $anchor->toDateString(),
            'prevDate' => $anchor->modify('-' . $step)->toDateString(),
            'nextDate' => $anchor->modify('+' . $step)->toDateString(),
            'departments' => $this->selectableDepartments($user),
            'classes' => $this->selectableClasses($user),
            'canViewSchool' => $this->access->canViewSchool($user),
            'canViewMine' => $this->access->teacherId($user) !== null,
        ]);
    }

    private function selectableDepartments(User $user)
    {
        $query = Department::query()->orderBy('name');
        if (! $this->access->canViewSchool($user)) {
            $query->whereIn('id', $this->access->departmentIds($user));
        }

        return $query->get(['id', 'code', 'name']);
    }

    private function selectableClasses(User $user)
    {
        $query = TrainingClass::query()->orderBy('code');

        if ($this->access->canViewSchool($user)) {
            return $query->get(['id', 'code', 'name']);
        }

        $departmentIds = $this->access->departmentIds($user);
        $ownClassIds = $this->access->ownClassIds($user);

        return $query->where(function ($q) use ($departmentIds, $ownClassIds): void {
            $q->whereIn('id', $ownClassIds)
                ->orWhereHas('scheduleSlots.subjectModel', fn ($s) => $s->whereIn('department_id', $departmentIds));
        })->get(['id', 'code', 'name']);
    }
}
