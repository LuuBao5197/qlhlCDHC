<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\Shared\TeacherAvailabilityService;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Schedule\Models\ScheduleSlotGroup;
use Throwable;

class AssignMonthlyScheduleHandler
{
    public function __construct(
        private TeacherAvailabilityService $teacherAvailabilityService
    ) {}

    /**
     * Save monthly slot assignments from department staff.
     */
    public function handle(AssignMonthlyScheduleRequest $request, int $id)
    {
        $monthlySchedule = MonthlySchedule::query()->findOrFail($id);
        $validated = $request->validated();
        $scope = app(MonthlyAssignmentScopeResolver::class)->resolve($monthlySchedule, $request->user());

        if ($scope === null) {
            return $this->respondFailure(
                $request,
                422,
                'Khong xac dinh duoc khoa hien tai de tong hop phan cong.',
                ['changes' => ['Khong xac dinh duoc khoa hien tai de tong hop phan cong.']]
            );
        }

        $submittedChanges = collect($validated['changes'] ?? [])->values();
        $submittedChangeCount = $submittedChanges->count();
        $slotIds = $submittedChanges
            ->pluck('slot_id')
            ->filter(fn ($slotId) => is_numeric($slotId))
            ->map(fn ($slotId) => (int) $slotId)
            ->unique()
            ->values()
            ->all();

        $validSlots = ScheduleSlot::query()
            ->with('scheduleSlotGroup')
            ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
            ->where('slot_type', 'subject')
            ->where('assignment_source', 'internal')
            ->whereIn('id', $slotIds)
            ->get()
            ->keyBy('id');

        try {
            $updatedSlotIds = DB::transaction(function () use ($request, $monthlySchedule, $submittedChanges, $validSlots, $scope): array {
                $lockedValidSlots = ScheduleSlot::query()
                    ->with(['scheduleSlotGroup', 'trainingClass', 'teacher', 'subjectModel.department', 'subjectLesson', 'room'])
                    ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
                    ->where('slot_type', 'subject')
                    ->where('assignment_source', 'internal')
                    ->whereIn('id', $validSlots->keys()->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $availabilityChecks = [];

                foreach ($submittedChanges as $index => $change) {
                    $slotId = (int) $change['slot_id'];

                    /** @var ScheduleSlot|null $slot */
                    $slot = $lockedValidSlots->get($slotId);
                    if (! $slot instanceof ScheduleSlot) {
                        continue;
                    }

                    $teacherId = array_key_exists('teacher_id', $change)
                        ? $this->normalizeNullableInteger($change['teacher_id'])
                        : null;

                    if ($teacherId !== null) {
                        $availabilityChecks[] = [
                            'error_key' => 'changes.' . $index . '.teacher_id',
                            'slot' => $slot,
                            'teacher_id' => (int) $teacherId,
                        ];
                    }
                }

                if ($availabilityChecks !== []) {
                    $this->teacherAvailabilityService->assertAssignmentsAreAvailable($availabilityChecks);
                }

                $updatedSlotIds = [];
                foreach ($submittedChanges as $change) {
                    $slotId = (int) $change['slot_id'];

                    /** @var ScheduleSlot|null $slot */
                    $slot = $lockedValidSlots->get($slotId);
                    if (! $slot instanceof ScheduleSlot) {
                        continue;
                    }

                    if (array_key_exists('teacher_id', $change)) {
                        $slot->teacher_id = $this->normalizeNullableInteger($change['teacher_id']);
                    }

                    if (array_key_exists('subject_lesson_id', $change)) {
                        $slot->subject_lesson_id = $this->normalizeNullableInteger($change['subject_lesson_id']);
                    }

                    if (array_key_exists('room_id', $change)) {
                        $slot->room_id = $this->normalizeNullableInteger($change['room_id']);
                    }

                    if (array_key_exists('content', $change)) {
                        $slot->content = $this->normalizeNullableString($change['content']);
                    }

                    if (array_key_exists('note', $change)) {
                        $slot->note = $this->normalizeNullableString($change['note']);
                    }

                    if ($slot->isDirty()) {
                        $slot->save();
                        $updatedSlotIds[] = $slot->id;
                    }
                }

                $activeGroupIds = ScheduleSlot::query()
                    ->with('scheduleSlotGroup')
                    ->whereIn('monthly_schedule_id', $scope['monthly_schedule_ids'])
                    ->whereIn('id', $validSlots->keys()->all())
                    ->get()
                    ->filter(fn (ScheduleSlot $slot) => $slot->scheduleSlotGroup?->status === 'active')
                    ->pluck('schedule_slot_group_id')
                    ->filter(fn ($groupId) => is_numeric($groupId))
                    ->map(fn ($groupId) => (int) $groupId)
                    ->unique()
                    ->values();

                if ($activeGroupIds->isNotEmpty()) {
                    $groups = ScheduleSlotGroup::query()
                        ->whereIn('id', $activeGroupIds)
                        ->get();

                    foreach ($groups as $group) {
                        $groupSlot = ScheduleSlot::query()
                            ->where('schedule_slot_group_id', $group->id)
                            ->orderBy('id')
                            ->first();

                        if (! $groupSlot) {
                            continue;
                        }

                        $group->subject_id = $groupSlot->subject_id;
                        $group->subject_lesson_id = $groupSlot->subject_lesson_id;
                        $group->teacher_id = $groupSlot->teacher_id;
                        $group->room_id = $groupSlot->room_id;
                        $group->save();
                    }
                }

                $touchUpdates = [];
                if ($monthlySchedule->created_by === null && $request->user()) {
                    $touchUpdates['created_by'] = $request->user()->id;
                }

                if ($monthlySchedule->department_id === null && $request->user()?->department_id !== null) {
                    $touchUpdates['department_id'] = $request->user()->department_id;
                }

                if ($touchUpdates !== []) {
                    $monthlySchedule->update($touchUpdates);
                }

                return array_values(array_unique($updatedSlotIds));
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->respondFailure(
                $request,
                500,
                'Khong the luu phan cong lich thang: ' . $exception->getMessage()
            );
        }

        $message = count($updatedSlotIds) === 0
            ? sprintf(
                'Khong co thay doi moi de luu. Da kiem tra %d tiet trong pham vi tong hop khoa + thang hien tai va khong phat hien conflict.',
                $submittedChangeCount
            )
            : sprintf(
                'Da luu phan cong lich giang day theo thang thanh cong. Cap nhat %d tiet.',
                count($updatedSlotIds)
            );

        return $this->respondSuccess($request, $message, $updatedSlotIds);
    }

    private function normalizeNullableInteger(mixed $value): ?int
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? $value : (string) $value;

        return trim($value) === '' ? null : $value;
    }

    /**
     * @param array<string, mixed>|null $errors
     */
    private function respondFailure(AssignMonthlyScheduleRequest $request, int $status, string $message, ?array $errors = null): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            $payload = [
                'success' => false,
                'message' => $message,
            ];

            if ($errors !== null) {
                $payload['errors'] = $errors;
            }

            return response()->json($payload, $status);
        }

        return back()->withInput()->with('error', $message);
    }

    private function respondSuccess(AssignMonthlyScheduleRequest $request, string $message, array $updatedSlotIds): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'updated_slot_ids' => array_values($updatedSlotIds),
            ]);
        }

        return back()->with('success', $message);
    }
}
