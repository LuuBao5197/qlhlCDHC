<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
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

        if ($user->isAdmin() || $user->isTrainingOffice()) {
            return true;
        }

        if (! $user->isDepartmentStaff()) {
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

        $departmentId = $monthlySchedule->department_id ?? $monthlySchedule->trainingClass?->department_id;

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
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.id' => ['required', 'integer', 'exists:schedule_slots,id'],
            'slots.*.teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'slots.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'slots.*.subject_lesson_id' => ['nullable', 'integer', 'exists:subject_lessons,id'],
            'slots.*.room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'slots.*.content' => ['nullable', 'string', 'max:500'],
            'slots.*.note' => ['nullable', 'string', 'max:500'],
            'slots.*.slot_status' => ['nullable', 'string', 'max:50'],
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
                    'slots',
                    'Khong xac dinh duoc khoa hien tai de tong hop phan cong.'
                );
                return;
            }

            $departmentId = $scope['department_id'];
            $departmentSubjectIds = $scope['department_subject_ids'];
            $aggregateMonthlyScheduleIds = $scope['monthly_schedule_ids'];

            $submittedSlots = $this->input('slots', []);

            $slotIds = collect($submittedSlots)
                ->pluck('id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $scheduleSlots = ScheduleSlot::query()
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
                ->whereIn('monthly_schedule_id', $aggregateMonthlyScheduleIds)
                ->where('slot_type', 'subject')
                ->when(
                    $departmentSubjectIds !== [],
                    fn ($query) => $query->whereIn('subject_id', $departmentSubjectIds)
                )
                ->whereIn('id', $slotIds)
                ->get()
                ->keyBy('id');

            $submittedSlotsById = [];
            foreach ($submittedSlots as $index => $slotData) {
                $slotId = isset($slotData['id']) && is_numeric($slotData['id']) ? (int) $slotData['id'] : null;
                if ($slotId === null) {
                    continue;
                }

                $submittedSlotsById[$slotId] = [
                    'index' => $index,
                    'data' => $slotData,
                ];
            }

            foreach ($submittedSlotsById as $slotId => $slotInfo) {
                if ($scheduleSlots->has($slotId)) {
                    continue;
                }

                $validator->errors()->add(
                    "slots.{$slotInfo['index']}.id",
                    'Tiet hoc khong thuoc lich thang dang cap nhat.'
                );
            }

            $submittedSlotIds = array_keys($submittedSlotsById);

            $teacherIdsForLabels = collect($submittedSlots)
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

            $subjectIdsForLabels = collect($submittedSlots)
                ->pluck('subject_id')
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $subjectsById = Subject::query()
                ->whereIn('id', $subjectIdsForLabels)
                ->get()
                ->keyBy('id')
                ->all();

            $teacherAssignments = [];

            foreach ($submittedSlots as $index => $slot) {
                $slotId = isset($slot['id']) ? (int) $slot['id'] : null;
                if ($slotId === null || ! $scheduleSlots->has($slotId)) {
                    continue;
                }

                $scheduleSlot = $scheduleSlots[$slotId];
                $isEventSlot = ($scheduleSlot->slot_type ?? 'subject') === 'event';
                $subjectId = $slot['subject_id'] ?? null;

                if (! $isEventSlot && $subjectId !== null && $departmentId !== null && ! in_array((int) $subjectId, $departmentSubjectIds, true)) {
                    $validator->errors()->add(
                        "slots.{$index}.subject_id",
                        'Mon hoc duoc chon khong thuoc khoa phu trach.'
                    );
                }

                $teacherId = $slot['teacher_id'] ?? null;
                if ($teacherId === null || $teacherId === '') {
                    continue;
                }

                $teacherId = (int) $teacherId;
                $date = $scheduleSlot->date?->format('Y-m-d');
                $period = $scheduleSlot->period_number;
                $conflictKey = "{$teacherId}_{$date}_{$period}";
                $groupId = $this->getActiveScheduleSlotGroupId($scheduleSlot);

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
                        "slots.{$index}.teacher_id",
                        $this->buildTeacherConflictMessage(
                            $scheduleSlot,
                            $slot,
                            $conflictSlot,
                            $conflictPayload,
                            $teachersById,
                            $subjectsById
                        )
                    );
                }
            }

            $activeGroupIds = collect($scheduleSlots->all())
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
                            'slots.' . $assignment['index'] . '.teacher_id',
                            $this->buildTeacherConflictMessage(
                                $assignment['slot'],
                                $currentPayload,
                                $conflictAssignment['slot'],
                                $conflictPayload,
                                $teachersById,
                                $subjectsById
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
            'slots.required' => 'Khong co du lieu tiet hoc de cap nhat.',
            'slots.*.id.exists' => 'Co tiet hoc khong ton tai trong he thong.',
            'slots.*.teacher_id.exists' => 'Giang vien duoc chon khong hop le.',
            'slots.*.subject_id.exists' => 'Mon hoc duoc chon khong hop le.',
            'slots.*.room_id.exists' => 'Phong hoc duoc chon khong hop le.',
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

        $referenceSlot = $slotsInGroup->first();
        if (! $referenceSlot instanceof ScheduleSlot) {
            return;
        }

        $referenceIndex = $submittedSlotsById[$referenceSlot->id]['index'] ?? null;
        $referencePayload = $submittedSlotsById[$referenceSlot->id]['data'] ?? [];
        $dateLabel = $this->formatGroupDateLabel($referenceSlot);
        $periodLabel = $referenceSlot->period_number !== null ? (string) $referenceSlot->period_number : '-';

        $missingSlotIds = $slotsInGroup
            ->pluck('id')
            ->filter(fn ($id) => ! isset($submittedSlotsById[$id]))
            ->values();

        if ($missingSlotIds->isNotEmpty()) {
            $message = sprintf(
                'Nhom ghep lop ngay %s tiet %s phai duoc luu day du tat ca cac tiet trong nhom.',
                $dateLabel,
                $periodLabel
            );

            if ($referenceIndex !== null) {
                $validator->errors()->add("slots.{$referenceIndex}.teacher_id", $message);
            }

            return;
        }

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
                    "slots.{$slotIndex}.slot_status",
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
                            "slots.{$slotIndex}.slot_status",
                            sprintf(
                                'Tiết su kien ngay %s tiet %s khong duoc thuoc nhom ghep lop.',
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
                        "slots.{$slotIndex}.{$field}",
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

        if ($value === '' || $value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
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
     * @param array<int, Subject> $subjectsById
     * @param array<string, mixed> $currentPayload
     * @param array<string, mixed> $conflictPayload
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

        $classId = $slotData['class_id'] ?? $slot->class_id;
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
        $subjectId = $slotData['subject_id'] ?? $slot->subject_id;
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

        $subjectLabel = $slotData['subject'] ?? $slot->subject ?? null;
        if (is_string($subjectLabel) && trim($subjectLabel) !== '') {
            return trim($subjectLabel);
        }

        if (is_numeric($subjectId)) {
            return 'Mon ID: ' . (int) $subjectId;
        }

        return 'Mon hoc chua xac dinh';
    }
}
