<?php

namespace Modules\Schedule\Application\Shared;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Teacher;

class TeacherAvailabilityService
{
    /**
     * @param array<int, array{
     *     error_key:string,
     *     slot:ScheduleSlot,
     *     teacher_id:int
     * }> $assignments
     */
    public function assertAssignmentsAreAvailable(array $assignments): void
    {
        $normalizedAssignments = collect($assignments)
            ->filter(fn (array $assignment): bool => isset($assignment['slot']) && $assignment['slot'] instanceof ScheduleSlot)
            ->values();

        if ($normalizedAssignments->isEmpty()) {
            return;
        }

        $errors = [];
        $teachersById = Teacher::query()
            ->whereIn('id', $normalizedAssignments->pluck('teacher_id')->filter(fn ($teacherId) => is_numeric($teacherId))->map(fn ($teacherId) => (int) $teacherId)->unique()->values()->all())
            ->get()
            ->keyBy('id');

        $seenWithinPayload = [];

        foreach ($normalizedAssignments as $index => $assignment) {
            $slot = $assignment['slot'];
            $teacherId = (int) $assignment['teacher_id'];
            $errorKey = $assignment['error_key'] ?? "assignments.{$index}.teacher_id";

            $date = $slot->date?->format('Y-m-d');
            $period = $slot->period_number;
            if ($date === null || $period === null) {
                continue;
            }

            $conflictKey = $teacherId . '|' . $date . '|' . $period;
            if (isset($seenWithinPayload[$conflictKey])) {
                $firstSlot = $seenWithinPayload[$conflictKey];
                if (! $this->isSameActiveMergeGroup($firstSlot, $slot)) {
                    $errors[$errorKey] = $this->buildConflictMessage($slot, $firstSlot, $teacherId, $teachersById);
                    continue;
                }
            } else {
                $seenWithinPayload[$conflictKey] = $slot;
            }

            $conflicts = ScheduleSlot::query()
                ->with(['scheduleSlotGroup', 'trainingClass', 'teacher', 'subjectModel.department', 'room'])
                ->where('teacher_id', $teacherId)
                ->whereDate('date', $date)
                ->where('period_number', $period)
                ->where('id', '!=', (int) $slot->id)
                ->lockForUpdate()
                ->get()
                ->filter(fn (ScheduleSlot $conflict): bool => ! $this->isSameActiveMergeGroup($slot, $conflict));

            if ($conflicts->isEmpty()) {
                continue;
            }

            $conflict = $conflicts->first();
            $errors[$errorKey] = $this->buildConflictMessage($slot, $conflict, $teacherId, $teachersById);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function isTeacherAvailableForSlot(int $teacherId, ScheduleSlot $slot, ?int $ignoreSlotId = null): bool
    {
        return $this->previewConflictingSlots($teacherId, $slot, $ignoreSlotId)->isEmpty();
    }

    public function previewConflictingSlots(int $teacherId, ScheduleSlot $slot, ?int $ignoreSlotId = null): EloquentCollection
    {
        $date = $slot->date?->format('Y-m-d');
        $period = $slot->period_number;

        if ($date === null || $period === null) {
            return new EloquentCollection();
        }

        $query = ScheduleSlot::query()
            ->with(['scheduleSlotGroup'])
            ->where('teacher_id', $teacherId)
            ->whereDate('date', $date)
            ->where('period_number', $period);

        if ($ignoreSlotId !== null) {
            $query->where('id', '!=', $ignoreSlotId);
        }

        return $query->get()->filter(fn (ScheduleSlot $conflict): bool => ! $this->isSameActiveMergeGroup($slot, $conflict));
    }

    public function getConflictingSlots(int $teacherId, ScheduleSlot $slot, ?int $ignoreSlotId = null): EloquentCollection
    {
        $date = $slot->date?->format('Y-m-d');
        $period = $slot->period_number;

        if ($date === null || $period === null) {
            return new EloquentCollection();
        }

        $query = ScheduleSlot::query()
            ->with(['scheduleSlotGroup', 'trainingClass', 'teacher', 'subjectModel.department', 'room'])
            ->where('teacher_id', $teacherId)
            ->whereDate('date', $date)
            ->where('period_number', $period);

        if ($ignoreSlotId !== null) {
            $query->where('id', '!=', $ignoreSlotId);
        }

        return $query->lockForUpdate()->get()->filter(
            fn (ScheduleSlot $conflict): bool => ! $this->isSameActiveMergeGroup($slot, $conflict)
        );
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

    private function getActiveScheduleSlotGroupId(ScheduleSlot $slot): ?int
    {
        if ($slot->scheduleSlotGroup?->status !== 'active') {
            return null;
        }

        return is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null;
    }

    /**
     * @param EloquentCollection<int, ScheduleSlot> $conflicts
     * @param EloquentCollection<int, Teacher>|array<int, Teacher> $teachersById
     */
    private function buildConflictMessage(
        ScheduleSlot $currentSlot,
        ScheduleSlot $conflictSlot,
        int $teacherId,
        iterable $teachersById
    ): string {
        $teacherLabel = $this->resolveTeacherLabel($teacherId, $teachersById);
        $currentClassLabel = $this->resolveClassLabel($currentSlot);
        $currentSubjectLabel = $this->resolveSubjectLabel($currentSlot);
        $currentDateLabel = $currentSlot->date?->format('d/m/Y') ?? '-';
        $currentPeriodLabel = $currentSlot->period_number !== null ? (string) $currentSlot->period_number : '-';

        $conflictClassLabel = $this->resolveClassLabel($conflictSlot);
        $conflictSubjectLabel = $this->resolveSubjectLabel($conflictSlot);
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
     * @param iterable<int, Teacher>|array<int, Teacher> $teachersById
     */
    private function resolveTeacherLabel(int $teacherId, iterable $teachersById): string
    {
        $teacher = null;
        foreach ($teachersById as $id => $candidate) {
            if ((int) $id === $teacherId) {
                $teacher = $candidate;
                break;
            }
        }

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

    private function resolveClassLabel(ScheduleSlot $slot): string
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

        if (is_numeric($slot->class_id)) {
            return 'Lop ID: ' . (int) $slot->class_id;
        }

        return 'Lop chua xac dinh';
    }

    private function resolveSubjectLabel(ScheduleSlot $slot): string
    {
        $subject = $slot->subjectModel;
        if ($subject) {
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

        $subjectLabel = trim((string) ($slot->subject ?? ''));
        if ($subjectLabel !== '') {
            return $subjectLabel;
        }

        if (is_numeric($slot->subject_id)) {
            return 'Mon ID: ' . (int) $slot->subject_id;
        }

        return 'Mon hoc chua xac dinh';
    }
}
