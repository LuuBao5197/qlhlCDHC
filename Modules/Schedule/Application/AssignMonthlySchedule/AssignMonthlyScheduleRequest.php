<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Validator;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\TeachingSupportRequest;
use Modules\Training\Models\Room;
use Modules\Training\Models\Subject;
use Modules\Training\Models\Teacher;

class AssignMonthlyScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $scheduleId = (int) $this->route('id');
        if ($scheduleId <= 0) {
            return true;
        }

        $monthlySchedule = MonthlySchedule::query()->with('trainingClass')->find($scheduleId);
        if (! $monthlySchedule) {
            return false;
        }

        $departmentId = $user->department_id;
        if ($departmentId === null) {
            $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $user);
            $departmentId = $scope['department_id'] ?? $monthlySchedule->department_id ?? $monthlySchedule->trainingClass?->department_id;
        }

        $currentBatch = DepartmentMonthlyAssignmentBatch::query()
            ->where('department_id', $departmentId)
            ->where('month', (int) $monthlySchedule->month)
            ->where('year', (int) $monthlySchedule->year)
            ->first();

        if ($currentBatch && in_array($currentBatch->status, ['submitted', 'approved'], true)) {
            return false;
        }

        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        if (! $user->isDepartmentStaff()) {
            return false;
        }

        if ($departmentId === null || $user->department_id === null) {
            return true;
        }

        return (int) $departmentId === (int) $user->department_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'changes' => ['required', 'array', 'max:5000'],
            'changes.*.slot_id' => ['required', 'integer', 'distinct', 'exists:schedule_slots,id'],
            'changes.*.teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'changes.*.assignment_type' => ['nullable', 'string', 'in:self_study'],
            'changes.*.subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'changes.*.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'changes.*.content' => ['nullable', 'string', 'max:500'],
            'changes.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $scheduleId = (int) $this->route('id');
            if ($scheduleId <= 0) {
                return;
            }

            $monthlySchedule = MonthlySchedule::query()
                ->with(['trainingClass.department', 'plan'])
                ->find($scheduleId);
            if (! $monthlySchedule) {
                return;
            }

            $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $this->user());
            if ($scope === null) {
                $validator->errors()->add(
                    'changes',
                    'Khong xac dinh duoc khoa hien tai de tong hop phan cong.'
                );
                return;
            }

            $submittedChanges = array_values((array) $this->input('changes', []));
            $slotIds = collect($submittedChanges)
                ->pluck('slot_id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $submittedSlots = ScheduleSlot::query()
                ->with([
                    'monthlySchedule.plan',
                    'monthlySchedule.trainingClass.department',
                    'scheduleSlotGroup',
                    'trainingClass',
                    'teacher',
                    'subjectModel.department',
                    'subjectLesson',
                    'room',
                ])
                ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
                ->where('slot_type', 'subject')
                ->where('assignment_source', 'internal')
                ->whereIn('id', $slotIds)
                ->get()
                ->keyBy('id');

            $submittedSlotsById = [];
            foreach ($submittedChanges as $index => $change) {
                $slotId = isset($change['slot_id']) && is_numeric($change['slot_id'])
                    ? (int) $change['slot_id']
                    : null;

                if ($slotId === null) {
                    continue;
                }

                $submittedSlotsById[$slotId] = [
                    'index' => $index,
                    'data' => $change,
                ];
            }

            foreach ($submittedSlotsById as $slotId => $slotInfo) {
                if ($submittedSlots->has($slotId)) {
                    continue;
                }

                $validator->errors()->add(
                    "changes.{$slotInfo['index']}.slot_id",
                    'Tiet hoc khong thuoc lich thang dang cap nhat.'
                );
            }

            $lockedSupportSlotIds = ScheduleSlot::query()
                ->whereIn('id', array_keys($submittedSlotsById))
                ->where(function ($query): void {
                    $query->where('assignment_source', 'department_support')
                        ->orWhereHas('teachingSupportRequestItem.request', function ($nested): void {
                            $nested->whereIn('status', [
                                TeachingSupportRequest::STATUS_PENDING_PDT,
                                TeachingSupportRequest::STATUS_ASSIGNED_TO_DEPARTMENT,
                                TeachingSupportRequest::STATUS_DEPARTMENT_ASSIGNING,
                                TeachingSupportRequest::STATUS_COMPLETED,
                            ]);
                        });
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($lockedSupportSlotIds as $lockedSlotId) {
                $slotInfo = $submittedSlotsById[$lockedSlotId] ?? null;
                if ($slotInfo === null) {
                    continue;
                }

                $validator->errors()->add(
                    "changes.{$slotInfo['index']}.slot_id",
                    'Tiet hoc dang nam trong workflow ho tro lien khoa va khong the cap nhat boi phan cong no bo.'
                );
            }

            $teacherIdsForLabels = collect($submittedChanges)
                ->pluck('teacher_id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $teachersById = Teacher::query()
                ->whereIn('id', $teacherIdsForLabels)
                ->get()
                ->keyBy('id')
                ->all();

            $roomIdsForLabels = collect($submittedChanges)
                ->pluck('room_id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $roomsById = Room::query()
                ->whereIn('id', $roomIdsForLabels)
                ->get()
                ->keyBy('id')
                ->all();

            $teacherAssignments = [];
            $roomAssignments = [];
            $submittedSlotIds = array_keys($submittedSlotsById);

            foreach ($submittedChanges as $index => $change) {
                $slotId = isset($change['slot_id']) ? (int) $change['slot_id'] : null;
                if ($slotId === null || ! $submittedSlots->has($slotId)) {
                    continue;
                }

                $scheduleSlot = $submittedSlots[$slotId];
                $isEventSlot = ($scheduleSlot->slot_type ?? 'subject') === 'event';
                $date = $scheduleSlot->date?->format('Y-m-d');
                $period = $scheduleSlot->period_number;
                $groupId = $this->getActiveScheduleSlotGroupId($scheduleSlot);

                $teacherId = $change['teacher_id'] ?? null;
                $assignmentType = $this->normalizeAssignmentType($change['assignment_type'] ?? null);
                $subjectLessonId = array_key_exists('subject_lesson_id', $change)
                    ? $this->normalizeNullableNumber($change['subject_lesson_id'])
                    : null;

                if ($this->isSpecialAssignmentType($assignmentType) && $teacherId !== null && $teacherId !== '') {
                    $validator->errors()->add(
                        "changes.{$index}.teacher_id",
                        'Tiet tu quan / tu nghien cuu khong duoc gan giang vien.'
                    );
                }

                if ($this->isSpecialAssignmentType($assignmentType) && $subjectLessonId !== null) {
                    $validator->errors()->add(
                        "changes.{$index}.subject_lesson_id",
                        'Tiet tu nghien cuu khong duoc chon bai hoc.'
                    );
                }

                if (! $this->isSpecialAssignmentType($assignmentType) && $teacherId !== null && $teacherId !== '') {
                    $teacherId = (int) $teacherId;
                    $conflictKey = "{$teacherId}_{$date}_{$period}";

                    $teacherAssignments[$conflictKey][] = [
                        'index' => $index,
                        'slot' => $scheduleSlot,
                        'group_id' => $groupId,
                    ];

                    $existingConflicts = ScheduleSlot::query()
                        ->with(['scheduleSlotGroup', 'trainingClass', 'teacher', 'subjectModel', 'subjectLesson'])
                        ->where('teacher_id', $teacherId)
                        ->whereDate('date', $date)
                        ->where('period_number', $period)
                        ->whereNotIn('id', $submittedSlotIds)
                        ->get();

                    $blockingConflicts = $existingConflicts->filter(
                        fn (ScheduleSlot $conflictSlot): bool => ! $this->isSameActiveMergeGroup($scheduleSlot, $conflictSlot)
                    );

                    if ($blockingConflicts->isNotEmpty()) {
                        $conflictSlot = $blockingConflicts->first();
                        $conflictPayload = $submittedSlotsById[$conflictSlot->id]['data'] ?? [];

                        $validator->errors()->add(
                            "changes.{$index}.teacher_id",
                            $this->buildTeacherConflictMessage(
                                $scheduleSlot,
                                $change,
                                $conflictSlot,
                                $conflictPayload,
                                $teachersById,
                                []
                            )
                        );
                    }
                }

                $roomId = $change['room_id'] ?? null;
                if ($roomId === null || $roomId === '') {
                    continue;
                }

                $roomId = (int) $roomId;
                $roomConflictKey = "{$roomId}_{$date}_{$period}";
                $roomAssignments[$roomConflictKey][] = [
                    'index' => $index,
                    'slot' => $scheduleSlot,
                    'group_id' => $groupId,
                ];

                $existingRoomConflicts = ScheduleSlot::query()
                    ->with(['scheduleSlotGroup', 'trainingClass', 'room', 'subjectModel', 'subjectLesson'])
                    ->where('room_id', $roomId)
                    ->whereDate('date', $date)
                    ->where('period_number', $period)
                    ->whereNotIn('id', $submittedSlotIds)
                    ->get();

                $blockingRoomConflicts = $existingRoomConflicts->filter(
                    fn (ScheduleSlot $conflictSlot): bool => ! $this->isSameActiveMergeGroup($scheduleSlot, $conflictSlot)
                );

                if ($blockingRoomConflicts->isNotEmpty()) {
                    $conflictSlot = $blockingRoomConflicts->first();
                    $conflictPayload = $submittedSlotsById[$conflictSlot->id]['data'] ?? [];

                    $validator->errors()->add(
                        "changes.{$index}.room_id",
                        $this->buildRoomConflictMessage(
                            $scheduleSlot,
                            $change,
                            $conflictSlot,
                            $conflictPayload,
                            $roomsById,
                            []
                        )
                    );
                }
            }

            $activeGroupIds = collect($submittedSlots->all())
                ->filter(fn (ScheduleSlot $slot) => $this->getActiveScheduleSlotGroupId($slot) !== null)
                ->map(fn (ScheduleSlot $slot) => $this->getActiveScheduleSlotGroupId($slot))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($activeGroupIds !== []) {
                $groupSlots = ScheduleSlot::query()
                    ->with('scheduleSlotGroup')
                    ->whereIn('schedule_slot_group_id', $activeGroupIds)
                    ->get()
                    ->filter(fn (ScheduleSlot $slot) => $this->getActiveScheduleSlotGroupId($slot) !== null)
                    ->groupBy(fn (ScheduleSlot $slot) => $this->getActiveScheduleSlotGroupId($slot));

                foreach ($groupSlots as $groupId => $slotsInGroup) {
                    $this->validateActiveMergeGroupConsistency(
                        $validator,
                        (int) $groupId,
                        collect($slotsInGroup)->values(),
                        $submittedSlotsById
                    );
                }
            }

            foreach ($teacherAssignments as $assignments) {
                if (count($assignments) < 2) {
                    continue;
                }

                $groupIds = collect($assignments)
                    ->pluck('group_id')
                    ->filter(fn ($groupId) => $groupId !== null)
                    ->map(fn ($groupId) => (int) $groupId)
                    ->unique()
                    ->values();

                $groupId = $groupIds->count() === 1 ? (int) $groupIds->first() : null;
                $groupStatus = $groupId !== null
                    ? ($assignments[0]['slot']->scheduleSlotGroup?->status ?? null)
                    : null;

                if ($groupId === null || $groupStatus !== 'active') {
                    foreach ($assignments as $assignment) {
                        $conflictAssignment = collect($assignments)
                            ->first(fn (array $item): bool => $item['index'] !== $assignment['index']);

                        if (! $conflictAssignment) {
                            continue;
                        }

                        $currentPayload = $submittedSlotsById[$assignment['slot']->id]['data'] ?? [];
                        $conflictPayload = $submittedSlotsById[$conflictAssignment['slot']->id]['data'] ?? [];

                        $validator->errors()->add(
                            'changes.' . $assignment['index'] . '.teacher_id',
                            $this->buildTeacherConflictMessage(
                                $assignment['slot'],
                                $currentPayload,
                                $conflictAssignment['slot'],
                                $conflictPayload,
                                $teachersById,
                                []
                            )
                        );
                    }
                }
            }

            foreach ($roomAssignments as $assignments) {
                if (count($assignments) < 2) {
                    continue;
                }

                $groupIds = collect($assignments)
                    ->pluck('group_id')
                    ->filter(fn ($groupId) => $groupId !== null)
                    ->map(fn ($groupId) => (int) $groupId)
                    ->unique()
                    ->values();

                $groupId = $groupIds->count() === 1 ? (int) $groupIds->first() : null;
                $groupStatus = $groupId !== null
                    ? ($assignments[0]['slot']->scheduleSlotGroup?->status ?? null)
                    : null;

                if ($groupId === null || $groupStatus !== 'active') {
                    foreach ($assignments as $assignment) {
                        $conflictAssignment = collect($assignments)
                            ->first(fn (array $item): bool => $item['index'] !== $assignment['index']);

                        if (! $conflictAssignment) {
                            continue;
                        }

                        $currentPayload = $submittedSlotsById[$assignment['slot']->id]['data'] ?? [];
                        $conflictPayload = $submittedSlotsById[$conflictAssignment['slot']->id]['data'] ?? [];

                        $validator->errors()->add(
                            'changes.' . $assignment['index'] . '.room_id',
                            $this->buildRoomConflictMessage(
                                $assignment['slot'],
                                $currentPayload,
                                $conflictAssignment['slot'],
                                $conflictPayload,
                                $roomsById,
                                []
                            )
                        );
                    }
                }
            }
        });
    }

    /**
     * Extra validation constraints.
     */
    public function messages(): array
    {
        return [
            'changes.required' => 'Khong co du lieu tiet hoc de cap nhat.',
            'changes.*.slot_id.distinct' => 'Tiet hoc bi gui trung.',
            'changes.*.slot_id.exists' => 'Co tiet hoc khong ton tai trong he thong.',
            'changes.*.teacher_id.exists' => 'Giang vien duoc chon khong hop le.',
            'changes.*.assignment_type.in' => 'Loai phan cong khong hop le.',
            'changes.*.subject_lesson_id.exists' => 'Bai hoc duoc chon khong hop le.',
            'changes.*.room_id.exists' => 'Phong hoc duoc chon khong hop le.',
        ];
    }

    private function getActiveScheduleSlotGroupId(ScheduleSlot $slot): ?int
    {
        $group = $slot->scheduleSlotGroup;

        if (! $group || ($group->status ?? null) !== 'active') {
            return null;
        }

        return is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null;
    }

    private function isSameActiveMergeGroup(ScheduleSlot $first, ScheduleSlot $second): bool
    {
        $firstGroupId = $this->getActiveScheduleSlotGroupId($first);
        $secondGroupId = $this->getActiveScheduleSlotGroupId($second);

        if ($firstGroupId === null || $secondGroupId === null) {
            return false;
        }

        return $firstGroupId === $secondGroupId;
    }

    /**
     * Ensure all slots in an active merge group stay consistent before save.
     *
     * @param array<int, array{index:int, data:array<string, mixed>}> $submittedSlotsById
     */
    private function validateActiveMergeGroupConsistency(
        Validator $validator,
        int $groupId,
        $slotsInGroup,
        array $submittedSlotsById
    ): void {
        $slotsInGroup = collect($slotsInGroup)->values();
        if ($slotsInGroup->isEmpty()) {
            return;
        }

        $submittedGroupSlots = $slotsInGroup
            ->filter(fn (ScheduleSlot $slot): bool => isset($submittedSlotsById[$slot->id]))
            ->values();

        if ($submittedGroupSlots->isEmpty()) {
            return;
        }

        $referenceSlot = $submittedGroupSlots->first();
        if (! $referenceSlot instanceof ScheduleSlot) {
            return;
        }

        $referenceIndex = $submittedSlotsById[$referenceSlot->id]['index'] ?? null;
        $referencePayload = $submittedSlotsById[$referenceSlot->id]['data'] ?? [];
        $dateLabel = $this->formatGroupDateLabel($referenceSlot);
        $periodLabel = $referenceSlot->period_number !== null ? (string) $referenceSlot->period_number : '-';

        $eventSlotIds = $slotsInGroup
            ->filter(fn (ScheduleSlot $slot) => ($slot->slot_type ?? 'subject') === 'event')
            ->pluck('id')
            ->values();

        if ($eventSlotIds->isNotEmpty()) {
            foreach ($eventSlotIds as $eventSlotId) {
                $slotIndex = $submittedSlotsById[$eventSlotId]['index'] ?? null;
                if ($slotIndex === null) {
                    continue;
                }

                $validator->errors()->add(
                    "changes.{$slotIndex}.slot_id",
                    sprintf(
                        'Tiet su kien ngay %s tiet %s khong duoc thuoc nhom ghep lop.',
                        $dateLabel,
                        $periodLabel
                    )
                );
            }

            return;
        }

        $fieldLabels = [
            'subject_id' => 'mon hoc',
            'subject_lesson_id' => 'bai hoc',
            'teacher_id' => 'giang vien',
            'assignment_type' => 'loai phan cong',
            'room_id' => 'phong hoc',
            'slot_status' => 'trang thai tiet hoc',
        ];

        foreach ($fieldLabels as $field => $label) {
            $referenceValue = $this->getEffectiveGroupFieldValue($referenceSlot, $referencePayload, $field);

            foreach ($slotsInGroup as $slot) {
                if (! $slot instanceof ScheduleSlot) {
                    continue;
                }

                if (($slot->slot_type ?? 'subject') === 'event') {
                    $slotIndex = $submittedSlotsById[$slot->id]['index'] ?? null;
                    if ($slotIndex !== null) {
                        $validator->errors()->add(
                            "changes.{$slotIndex}.slot_id",
                            sprintf(
                                'Tiet su kien ngay %s tiet %s khong duoc thuoc nhom ghep lop.',
                                $dateLabel,
                                $periodLabel
                            )
                        );
                    }
                    continue;
                }

                $slotPayload = $submittedSlotsById[$slot->id]['data'] ?? [];
                $currentValue = $this->getEffectiveGroupFieldValue($slot, $slotPayload, $field);

                if ($this->valuesDifferForGroup($referenceValue, $currentValue)) {
                    $slotIndex = $submittedSlotsById[$slot->id]['index'] ?? null;
                    if ($slotIndex === null) {
                        continue;
                    }

                    $validator->errors()->add(
                        "changes.{$slotIndex}.{$field}",
                        sprintf(
                            'Nhom ghep lop ngay %s tiet %s phai dung cung %s.',
                            $dateLabel,
                            $periodLabel,
                            $label
                        )
                    );
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $slotData
     */
    private function getEffectiveGroupFieldValue(ScheduleSlot $slot, array $slotData, string $field): mixed
    {
        if (array_key_exists($field, $slotData)) {
            return $this->normalizeGroupFieldValue($slotData[$field], $field);
        }

        return $this->normalizeGroupFieldValue($slot->{$field} ?? null, $field);
    }

    private function normalizeGroupFieldValue(mixed $value, string $field): mixed
    {
        if ($field === 'slot_status') {
            return is_string($value) ? trim($value) : $value;
        }

        if ($field === 'assignment_type') {
            return $this->normalizeAssignmentType($value);
        }

        if ($value === '' || $value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    private function normalizeAssignmentType(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return in_array($value, [
            ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY,
        ], true) ? $value : null;
    }

    private function normalizeNullableNumber(mixed $value): ?int
    {
        if ($value === '' || $value === null || $value === false) {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function isSpecialAssignmentType(?string $assignmentType): bool
    {
        return $assignmentType === ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY;
    }

    private function valuesDifferForGroup(mixed $firstValue, mixed $secondValue): bool
    {
        if ($firstValue === null && $secondValue === null) {
            return false;
        }

        return $firstValue !== $secondValue;
    }

    private function formatGroupDateLabel(ScheduleSlot $slot): string
    {
        if ($slot->date) {
            return $slot->date->format('d/m/Y');
        }

        return '-';
    }

    /**
     * @param array<int, Teacher> $teachersById
     */
    private function buildTeacherConflictMessage(
        ScheduleSlot $currentSlot,
        array $currentPayload,
        ?ScheduleSlot $conflictSlot,
        array $conflictPayload,
        array $teachersById,
        array $subjectsById
    ): string {
        $teacherId = $currentPayload['teacher_id'] ?? $currentSlot->teacher_id;
        $teacherLabel = $this->resolveTeacherLabel($teacherId, $teachersById);

        $currentClassLabel = $this->resolveClassLabel($currentSlot, $currentPayload);
        $currentSubjectLabel = $this->resolveSubjectLabel($currentSlot, $currentPayload, $subjectsById);
        $currentDateLabel = $currentSlot->date?->format('d/m/Y') ?? '-';
        $currentPeriodLabel = $currentSlot->period_number !== null ? (string) $currentSlot->period_number : '-';

        if (! $conflictSlot instanceof ScheduleSlot) {
            return sprintf(
                'Giang vien %s da duoc phan cong lop %s, mon %s vao ngay %s, tiet %s. Khong the tiep tuc phan cong cung giang vien o cung thoi gian, tru khi cac lop thuoc cung mot nhom ghep dang hoat dong.',
                $teacherLabel,
                $currentClassLabel,
                $currentSubjectLabel,
                $currentDateLabel,
                $currentPeriodLabel
            );
        }

        $conflictClassLabel = $this->resolveClassLabel($conflictSlot, $conflictPayload);
        $conflictSubjectLabel = $this->resolveSubjectLabel($conflictSlot, $conflictPayload, $subjectsById);
        $conflictDateLabel = $conflictSlot->date?->format('d/m/Y') ?? '-';
        $conflictPeriodLabel = $conflictSlot->period_number !== null ? (string) $conflictSlot->period_number : '-';

        return sprintf(
            'Giang vien %s da duoc phan cong lop %s, mon %s vao ngay %s, tiet %s. Khong the tiep tuc phan cong cung giang vien cho lop %s, mon %s vao ngay %s, tiet %s, tru khi cac lop thuoc cung mot nhom ghep dang hoat dong.',
            $teacherLabel,
            $currentClassLabel,
            $currentSubjectLabel,
            $currentDateLabel,
            $currentPeriodLabel,
            $conflictClassLabel,
            $conflictSubjectLabel,
            $conflictDateLabel,
            $conflictPeriodLabel
        );
    }

    /**
     * @param array<int, Room> $roomsById
     */
    private function buildRoomConflictMessage(
        ScheduleSlot $currentSlot,
        array $currentPayload,
        ?ScheduleSlot $conflictSlot,
        array $conflictPayload,
        array $roomsById,
        array $subjectsById
    ): string {
        $roomId = $currentPayload['room_id'] ?? $currentSlot->room_id;
        $roomLabel = $this->resolveRoomLabel($roomId, $roomsById);

        $currentClassLabel = $this->resolveClassLabel($currentSlot, $currentPayload);
        $currentSubjectLabel = $this->resolveSubjectLabel($currentSlot, $currentPayload, $subjectsById);
        $currentDateLabel = $currentSlot->date?->format('d/m/Y') ?? '-';
        $currentPeriodLabel = $currentSlot->period_number !== null ? (string) $currentSlot->period_number : '-';

        if (! $conflictSlot instanceof ScheduleSlot) {
            return sprintf(
                'Phong %s da duoc dung cho lop %s, mon %s vao ngay %s, tiet %s. Khong the tiep tuc xep cung phong o cung thoi gian, tru khi cac lop thuoc cung mot nhom ghep dang hoat dong.',
                $roomLabel,
                $currentClassLabel,
                $currentSubjectLabel,
                $currentDateLabel,
                $currentPeriodLabel
            );
        }

        $conflictClassLabel = $this->resolveClassLabel($conflictSlot, $conflictPayload);
        $conflictSubjectLabel = $this->resolveSubjectLabel($conflictSlot, $conflictPayload, $subjectsById);
        $conflictDateLabel = $conflictSlot->date?->format('d/m/Y') ?? '-';
        $conflictPeriodLabel = $conflictSlot->period_number !== null ? (string) $conflictSlot->period_number : '-';

        return sprintf(
            'Phong %s da duoc dung cho lop %s, mon %s vao ngay %s, tiet %s. Khong the tiep tuc xep cung phong cho lop %s, mon %s vao ngay %s, tiet %s, tru khi cac lop thuoc cung mot nhom ghep dang hoat dong.',
            $roomLabel,
            $currentClassLabel,
            $currentSubjectLabel,
            $currentDateLabel,
            $currentPeriodLabel,
            $conflictClassLabel,
            $conflictSubjectLabel,
            $conflictDateLabel,
            $conflictPeriodLabel
        );
    }

    /**
     * @param array<int, Teacher> $teachersById
     */
    private function resolveTeacherLabel(mixed $teacherId, array $teachersById): string
    {
        if (! is_numeric($teacherId)) {
            return 'Giang vien chua xac dinh';
        }

        $teacherId = (int) $teacherId;
        $teacher = $teachersById[$teacherId] ?? null;
        if ($teacher instanceof Teacher) {
            $teacherName = trim((string) ($teacher->name ?? ''));
            $teacherCode = trim((string) ($teacher->teacher_code ?? ''));

            if ($teacherName !== '' && $teacherCode !== '') {
                return $teacherName . ' (' . $teacherCode . ')';
            }

            if ($teacherName !== '') {
                return $teacherName;
            }

            if ($teacherCode !== '') {
                return $teacherCode;
            }
        }

        return 'Giang vien ID: ' . $teacherId;
    }

    /**
     * @param array<int, Room> $roomsById
     */
    private function resolveRoomLabel(mixed $roomId, array $roomsById): string
    {
        if (! is_numeric($roomId)) {
            return 'Phong hoc chua xac dinh';
        }

        $roomId = (int) $roomId;
        $room = $roomsById[$roomId] ?? null;
        if ($room instanceof Room) {
            $roomCode = trim((string) ($room->code ?? ''));
            $roomName = trim((string) ($room->name ?? ''));

            if ($roomCode !== '' && $roomName !== '') {
                return $roomCode === $roomName ? $roomCode : $roomCode . ' - ' . $roomName;
            }

            if ($roomCode !== '') {
                return $roomCode;
            }

            if ($roomName !== '') {
                return $roomName;
            }
        }

        return 'Phong ID: ' . $roomId;
    }

    private function resolveClassLabel(ScheduleSlot $slot, array $slotData = []): string
    {
        $trainingClass = $slot->trainingClass;
        if ($trainingClass) {
            $classCode = trim((string) ($trainingClass->code ?? ''));
            $className = trim((string) ($trainingClass->name ?? ''));

            if ($classCode !== '' && $className !== '') {
                return $classCode === $className ? $classCode : $classCode . ' - ' . $className;
            }

            if ($classCode !== '') {
                return $classCode;
            }

            if ($className !== '') {
                return $className;
            }
        }

        $classId = Arr::get($slotData, 'class_id', $slot->class_id);
        if (is_numeric($classId)) {
            return 'Lop ID: ' . (int) $classId;
        }

        return 'Lop chua xac dinh';
    }

    /**
     * @param array<int, Subject> $subjectsById
     */
    private function resolveSubjectLabel(ScheduleSlot $slot, array $slotData, array $subjectsById): string
    {
        $subjectId = Arr::get($slotData, 'subject_id', $slot->subject_id);
        if (is_numeric($subjectId)) {
            $subjectId = (int) $subjectId;
            $subject = $subjectsById[$subjectId] ?? null;
            if ($subject instanceof Subject) {
                $subjectCode = trim((string) ($subject->code ?? ''));
                $subjectName = trim((string) ($subject->name ?? ''));

                if ($subjectCode !== '' && $subjectName !== '') {
                    return $subjectCode === $subjectName ? $subjectCode : $subjectCode . ' - ' . $subjectName;
                }

                if ($subjectCode !== '') {
                    return $subjectCode;
                }

                if ($subjectName !== '') {
                    return $subjectName;
                }
            }
        }

        $subjectLabel = Arr::get($slotData, 'subject', $slot->subject ?? null);
        if (is_string($subjectLabel) && trim($subjectLabel) !== '') {
            return trim($subjectLabel);
        }

        if (is_numeric($subjectId)) {
            return 'Mon ID: ' . (int) $subjectId;
        }

        return 'Mon hoc chua xac dinh';
    }
}
