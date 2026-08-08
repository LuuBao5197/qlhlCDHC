<?php

namespace Modules\Schedule\Application\Shared;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Training\Models\ChangeRequest;
use Modules\Training\Models\HolidayCalendar;
use Modules\Training\Models\Room;
use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Teacher;

class ChangeRequestPageDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(?User $user, bool $includeChangeRequests = false): array
    {
        $departmentId = $user?->department_id !== null ? (int) $user->department_id : null;
        $isDepartmentStaff = (bool) ($user?->isDepartmentStaff() ?? false);

        $monthlySchedules = MonthlySchedule::query()
            ->with([
                'plan',
                'createdBy',
                'scheduleSlots' => static function ($query): void {
                    $query
                        ->with(['trainingClass', 'room', 'subjectModel.department', 'teacher', 'subjectLesson', 'scheduleSlotGroup', 'teachingSupportRequestItems.request'])
                        ->where('slot_type', 'subject')
                        ->orderBy('date')
                        ->orderBy('period_number');
                },
            ])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $changeRequestMonthlySchedules = $this->buildChangeRequestMonthlySchedules($monthlySchedules, $user);

        $rooms = Room::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $teachersQuery = Teacher::query()
            ->orderBy('name');

        if ($isDepartmentStaff && $departmentId !== null) {
            $teachersQuery->where('department_id', $departmentId);
        } elseif ($isDepartmentStaff) {
            $teachersQuery->whereRaw('1 = 0');
        }

        $teachers = $teachersQuery
            ->select(['id', 'name', 'teacher_code as employee_code', 'department_id'])
            ->get();

        $teacherLookup = Teacher::query()
            ->orderBy('name')
            ->get(['id', 'name', 'teacher_code']);

        $subjectLessons = SubjectLesson::query()
            ->with(['subject:id,code,name'])
            ->orderBy('subject_id')
            ->orderBy('lesson_no')
            ->get(['id', 'subject_id', 'lesson_no', 'title']);

        $holidayCalendars = Schema::hasTable('holiday_calendars')
            ? HolidayCalendar::query()
                ->orderByDesc('date')
                ->get(['id', 'name', 'date', 'note', 'is_active'])
            : collect();

        $payload = [
            'monthlySchedules' => $monthlySchedules,
            'changeRequestMonthlySchedules' => $changeRequestMonthlySchedules,
            'rooms' => $rooms,
            'teachers' => $teachers,
            'teacherLookup' => $teacherLookup,
            'subjectLessons' => $subjectLessons,
            'holidayCalendars' => $holidayCalendars,
        ];

        if ($includeChangeRequests) {
            $payload['changeRequests'] = $this->loadChangeRequests();
            $payload['statusStyles'] = [
                'pending' => 'badge-warning',
                'approved' => 'badge-success',
                'rejected' => 'badge-danger',
            ];
        }

        return $payload;
    }

    /**
     * @return Collection<int, ChangeRequest>
     */
    private function loadChangeRequests(): Collection
    {
        return ChangeRequest::query()
            ->with([
                'monthlySchedule.plan',
                'scheduleSlot.trainingClass',
                'scheduleSlot.teacher',
                'scheduleSlot.subjectModel',
                'scheduleSlot.subjectLesson.subject',
                'scheduleSlot.room',
                'requestedBy.department',
                'changeRequestItems.scheduleSlot.trainingClass',
                'changeRequestItems.scheduleSlot.teacher',
                'changeRequestItems.scheduleSlot.subjectModel',
                'changeRequestItems.scheduleSlot.subjectLesson.subject',
                'changeRequestItems.scheduleSlot.room',
            ])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();
    }

    /**
     * @param Collection<int, MonthlySchedule> $monthlySchedules
     * @return Collection<int, MonthlySchedule>
     */
    private function buildChangeRequestMonthlySchedules(Collection $monthlySchedules, ?User $user): Collection
    {
        $departmentId = $user?->department_id !== null ? (int) $user->department_id : null;
        $isDepartmentStaff = (bool) ($user?->isDepartmentStaff() ?? false);
        $activeSupportStatuses = [
            TeachingSupportRequest::STATUS_PENDING_PDT,
            TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
            TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
        ];

        return $monthlySchedules
            ->map(function (MonthlySchedule $monthlySchedule) use ($departmentId, $isDepartmentStaff, $activeSupportStatuses) {
                $scheduleSlots = $monthlySchedule->scheduleSlots;
                $groupedSlots = $scheduleSlots->groupBy(function (ScheduleSlot $slot): string {
                    $groupId = $this->getActiveScheduleSlotGroupId($slot);

                    return $groupId !== null ? 'group:' . $groupId : 'slot:' . $slot->id;
                });

                $filteredSlots = collect();
                foreach ($groupedSlots as $groupSlots) {
                    $groupSlots = $groupSlots->values();
                    $groupId = $this->getActiveScheduleSlotGroupId($groupSlots->first());

                    if ($groupId !== null) {
                        $allEligible = $groupSlots->every(function (ScheduleSlot $slot) use ($departmentId, $isDepartmentStaff, $activeSupportStatuses): bool {
                            return $this->isChangeRequestEligibleSlot($slot, $departmentId, $isDepartmentStaff, $activeSupportStatuses);
                        });

                        if (! $allEligible) {
                            continue;
                        }

                        $filteredSlots = $filteredSlots->merge($groupSlots);
                        continue;
                    }

                    $filteredSlots = $filteredSlots->merge(
                        $groupSlots->filter(function (ScheduleSlot $slot) use ($departmentId, $isDepartmentStaff, $activeSupportStatuses): bool {
                            return $this->isChangeRequestEligibleSlot($slot, $departmentId, $isDepartmentStaff, $activeSupportStatuses);
                        })
                    );
                }

                $scheduleSlots = $filteredSlots
                    ->sortBy(function (ScheduleSlot $slot): string {
                        return sprintf(
                            '%010d|%03d|%010d',
                            $slot->date?->timestamp ?? 0,
                            (int) ($slot->period_number ?? 0),
                            (int) ($slot->id ?? 0)
                        );
                    })
                    ->values();

                $clone = clone $monthlySchedule;
                $clone->setRelation('scheduleSlots', $scheduleSlots);

                return $clone;
            })
            ->filter(fn (MonthlySchedule $monthlySchedule): bool => $monthlySchedule->scheduleSlots->isNotEmpty())
            ->values();
    }

    private function isChangeRequestEligibleSlot(
        ScheduleSlot $slot,
        ?int $departmentId,
        bool $isDepartmentStaff,
        array $activeSupportStatuses
    ): bool {
        if (($slot->slot_type ?? 'subject') !== 'subject') {
            return false;
        }

        if ((string) ($slot->assignment_source ?? 'internal') !== 'internal') {
            return false;
        }

        if ($slot->teachingSupportRequestItem !== null) {
            return false;
        }

        if (($slot->teachingSupportRequestItems ?? collect())->contains(function ($supportItem) use ($activeSupportStatuses): bool {
            return in_array($supportItem?->request?->status ?? null, $activeSupportStatuses, true);
        })) {
            return false;
        }

        if ($isDepartmentStaff && $departmentId !== null) {
            return (int) ($slot->subjectModel?->department_id ?? 0) === $departmentId;
        }

        return true;
    }

    private function getActiveScheduleSlotGroupId(ScheduleSlot $slot): ?int
    {
        if ($slot->scheduleSlotGroup?->status !== 'active') {
            return null;
        }

        return is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null;
    }
}
