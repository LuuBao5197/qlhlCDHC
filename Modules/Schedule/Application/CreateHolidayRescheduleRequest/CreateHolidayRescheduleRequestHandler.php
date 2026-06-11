<?php

namespace Modules\Schedule\Application\CreateHolidayRescheduleRequest;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\ChangeRequest;
use Modules\Training\Models\HolidayCalendar;
use Throwable;

class CreateHolidayRescheduleRequestHandler
{
    public function handle(CreateHolidayRescheduleRequestRequest $request)
    {
        if (! (bool) config('schedule.holiday_reschedule.enabled', true)) {
            return back()->withInput()->with('error', 'Tinh nang doi lich nghi le/tet dang tam tat.');
        }

        $validated = $request->validated();
        $monthlyScheduleId = (int) $validated['monthly_schedule_id'];
        $applyMode = (string) ($validated['apply_mode'] ?? 'best_effort');
        $targetDate = Carbon::parse($validated['target_date'])->startOfDay();

        $holidayDates = $this->buildHolidayDateSet($validated);
        if ($holidayDates->isEmpty()) {
            return back()->withInput()->with('error', 'Khong xac dinh duoc ngay nghi hop le de xu ly.');
        }

        $monthlySchedule = MonthlySchedule::query()->findOrFail($monthlyScheduleId);
        $monthStart = Carbon::createFromDate((int) $monthlySchedule->year, (int) $monthlySchedule->month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth()->startOfDay();
        if ($targetDate->lt($monthStart) || $targetDate->gt($monthEnd)) {
            return back()->withInput()->with('error', 'Ngay dich nam ngoai pham vi lich thang duoc chon.');
        }

        $monthlySlots = ScheduleSlot::query()
            ->where('monthly_schedule_id', $monthlyScheduleId)
            ->orderBy('date')
            ->orderBy('period_number')
            ->orderBy('id')
            ->get();

        if ($monthlySlots->isEmpty()) {
            return back()->withInput()->with('error', 'Khong co tiet hoc nao trong lich thang da chon.');
        }

        $holidaySlotIndexes = [];
        foreach ($monthlySlots as $index => $slot) {
            $slotDate = $slot->date?->format('Y-m-d');
            if ($slotDate !== null && $holidayDates->contains($slotDate)) {
                $holidaySlotIndexes[] = $index;
            }
        }

        if ($holidaySlotIndexes === []) {
            return back()->withInput()->with('error', 'Khong co tiet hoc nao trung ngay nghi da chon.');
        }

        $firstMovableIndex = min($holidaySlotIndexes);
        $movableSlots = $monthlySlots->slice($firstMovableIndex)->values();
        $movableSlotIds = $movableSlots->pluck('id')->map(static fn ($id) => (int) $id)->values()->all();

        if ($movableSlots->isEmpty()) {
            return back()->withInput()->with('error', 'Khong xac dinh duoc tap tiet hoc can dich day chuyen.');
        }

        $nonWorkingDates = $this->buildNonWorkingDateSet();
        $planMonthlyScheduleIds = $this->resolvePlanMonthlyScheduleIds((int) $monthlySchedule->plan_id, (int) $monthlySchedule->id);
        $templatesByKey = $this->buildTemplateMapForPlan((int) $monthlySchedule->plan_id);

        $classAssignments = [];
        $teacherAssignments = [];

        $schedulableItems = [];
        $droppedItems = [];
        $cursorDate = $targetDate->copy();

        $slotContexts = $movableSlots
            ->map(function (ScheduleSlot $slot) use ($templatesByKey): array {
                $templateCheck = $this->resolveTemplateContextForSlot($slot, $templatesByKey);

                return [
                    'slot' => $slot,
                    'templates' => $templateCheck['templates'],
                ];
            })
            ->values();

        foreach ($slotContexts as $slotContext) {
            /** @var ScheduleSlot $slot */
            $slot = $slotContext['slot'];

            $candidate = $this->findCandidateDateWithinMonthForChain(
                $slot,
                $cursorDate,
                $monthStart,
                $monthEnd,
                $holidayDates,
                $nonWorkingDates,
                $slotContext['templates'],
                $classAssignments,
                $teacherAssignments,
                $planMonthlyScheduleIds,
                $movableSlotIds
            );

            if ($candidate === null) {
                $droppedItems[] = [
                    'slot_id' => (int) $slot->id,
                    'old_date' => $slot->date?->format('Y-m-d'),
                    'period_number' => (int) $slot->period_number,
                    'error' => 'Khong con vi tri hop le trong pham vi thang hien tai.',
                ];
                continue;
            }

            $newDate = $candidate->copy()->startOfDay();
            $newPayload = [
                'date' => $newDate->format('Y-m-d'),
                // Use module convention: 2..8 (Thu 2..Chu nhat)
                'day_of_week' => $newDate->dayOfWeekIso + 1,
                'period_number' => (int) $slot->period_number,
            ];

            $schedulableItems[] = [
                'slot' => $slot,
                'new_payload' => $newPayload,
            ];

            $cursorDate = $newDate;
        }

        if ($schedulableItems === []) {
            $detail = collect($droppedItems)
                ->unique(static fn(array $item): string => ($item['old_date'] ?? '-') . '|' . ($item['period_number'] ?? '-') . '|' . ($item['error'] ?? '-'))
                ->take(3)
                ->map(static function (array $item): string {
                    $date = $item['old_date'] ?? '-';
                    $period = $item['period_number'] ?? '-';
                    $error = $item['error'] ?? 'Khong ro nguyen nhan.';

                    return "Ngay {$date}, tiet {$period}: {$error}";
                })
                ->implode(' | ');

            $message = 'Khong tim duoc tiet nao co the doi lich. Vui long dieu chinh ngay dich.';
            if ($detail !== '') {
                $message .= ' Chi tiet: ' . $detail;
            }

            return back()->withInput()->with('error', $message);
        }

        if ($applyMode === 'all_or_none' && $droppedItems !== []) {
            return back()->withInput()->with(
                'error',
                'Che do all_or_none yeu cau tat ca tiet phai doi duoc lich. Hien co ' . count($droppedItems) . ' tiet bi loai do tran thang/khong hop le.'
            );
        }

        try {
            DB::transaction(function () use ($request, $validated, $monthlySchedule, $applyMode, $movableSlots, $schedulableItems, $droppedItems): void {
                $first = $schedulableItems[0];
                /** @var ScheduleSlot $firstSlot */
                $firstSlot = $first['slot'];

                $changeRequest = ChangeRequest::query()->create([
                    'monthly_schedule_id' => $monthlySchedule->id,
                    'schedule_slot_id' => $firstSlot->id,
                    'requested_by' => $request->user()?->id,
                    'reason' => (string) $validated['reason'],
                    'old_payload' => $this->extractSlotPayload($firstSlot),
                    'new_payload' => $first['new_payload'],
                    'status' => 'pending',
                    'change_type' => 'holiday_reschedule',
                    'apply_mode' => $applyMode,
                    'apply_changes' => true,
                    'apply_summary' => [
                        'mode' => $applyMode,
                        'attempted' => $movableSlots->count(),
                        'schedulable' => count($schedulableItems),
                        'dropped' => count($droppedItems),
                        'unschedulable' => count($droppedItems),
                        'errors' => $droppedItems,
                    ],
                    'submitted_at' => now(),
                    'resolved_at' => null,
                ]);

                foreach ($schedulableItems as $item) {
                    /** @var ScheduleSlot $slot */
                    $slot = $item['slot'];

                    $changeRequest->changeRequestItems()->create([
                        'schedule_slot_id' => $slot->id,
                        'old_payload' => $this->extractSlotPayload($slot),
                        'new_payload' => $item['new_payload'],
                        'apply_status' => null,
                        'apply_error' => null,
                        'applied_at' => null,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            return back()->withInput()->with(
                'error',
                'Khong the tao phieu doi lich nghi le: ' . $exception->getMessage()
            );
        }

        $message = 'Da tao phieu doi lich nghi le thanh cong. So tiet de nghi doi: ' . count($schedulableItems);
        if ($droppedItems !== []) {
            $message .= '. Co ' . count($droppedItems) . ' tiet bi loai do tran pham vi thang/khong hop le.';
        }

        return back()->with('success', $message);
    }

    private function buildHolidayDateSet(array $validated): Collection
    {
        $dates = collect();

        $start = $validated['holiday_start_date'] ?? null;
        $end = $validated['holiday_end_date'] ?? null;

        if ($start !== null && $end !== null) {
            $cursor = Carbon::parse($start)->startOfDay();
            $limit = Carbon::parse($end)->startOfDay();

            while ($cursor->lte($limit)) {
                $dates->push($cursor->format('Y-m-d'));
                $cursor->addDay();
            }
        }

        $explicitDates = collect($validated['holiday_dates'] ?? [])
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

        return $dates
            ->merge($explicitDates)
            ->unique()
            ->values();
    }

    private function buildNonWorkingDateSet(): Collection
    {
        if (! Schema::hasTable('holiday_calendars')) {
            return collect();
        }

        $holidayDates = HolidayCalendar::query()
            ->where('is_active', true)
            ->pluck('date')
            ->map(static fn ($date) => Carbon::parse($date)->format('Y-m-d'));

        return $holidayDates->unique()->values();
    }

    /**
     * @return array<string, Collection<int, PlanTemplates>>
     */
    private function buildTemplateMapForPlan(int $planId): array
    {
        if ($planId <= 0) {
            return [];
        }

        /** @var Collection<int, PlanTemplates> $templates */
        $templates = PlanTemplates::query()
            ->where('plan_id', $planId)
            ->get();

        $mapped = [];
        foreach ($templates as $template) {
            $classId = is_numeric($template->class_id) ? (int) $template->class_id : null;
            $subjectId = is_numeric($template->subject_id) ? (int) $template->subject_id : null;
            if ($classId === null || $subjectId === null) {
                continue;
            }

            $key = $classId . '|' . $subjectId;
            if (! isset($mapped[$key])) {
                $mapped[$key] = collect();
            }

            $mapped[$key]->push($template);
        }

        return $mapped;
    }

    /**
     * @param array<string, Collection<int, PlanTemplates>> $templatesByKey
     * @return array{requires_template: bool, templates: Collection<int, PlanTemplates>}
     */
    private function resolveTemplateContextForSlot(ScheduleSlot $slot, array $templatesByKey): array
    {
        $classId = is_numeric($slot->class_id) ? (int) $slot->class_id : null;
        $subjectId = is_numeric($slot->subject_id) ? (int) $slot->subject_id : null;

        if ($classId === null || $subjectId === null) {
            return [
                'requires_template' => false,
                'templates' => collect(),
            ];
        }

        $session = $this->normalizeSessionValue((int) $slot->period_number <= 5 ? 'sang' : 'chieu');
        $periodNumber = (int) $slot->period_number;
        $key = $classId . '|' . $subjectId;
        $templates = $templatesByKey[$key] ?? collect();

        $matchedTemplates = $templates->filter(function (PlanTemplates $template) use ($session, $periodNumber): bool {
            $templateSession = $this->normalizeSessionValue((string) ($template->session ?? ''));
            if ($templateSession !== '' && $templateSession !== $session) {
                return false;
            }

            return $this->isPeriodInRange((string) ($template->period_range ?? ''), $periodNumber);
        })->values();

        return [
            'requires_template' => $matchedTemplates->isNotEmpty(),
            'templates' => $matchedTemplates,
        ];
    }

    private function calculateTemplateFlexibility(Collection $templates): int
    {
        if ($templates->isEmpty()) {
            return 99;
        }

        $allowedDays = $templates
            ->flatMap(fn(PlanTemplates $template) => $this->extractTemplateAllowedDays($template))
            ->filter(static fn($day) => is_int($day) || is_numeric($day))
            ->map(static fn($day) => (int) $day)
            ->unique()
            ->values();

        if ($allowedDays->isEmpty()) {
            return 7;
        }

        return $allowedDays->count();
    }

    /**
     * @param Collection<int, PlanTemplates> $templates
     * @param array<string, int> $classAssignments
     * @param array<string, int> $teacherAssignments
     * @param array<int> $planMonthlyScheduleIds
     * @param array<int> $movableSlotIds
     */
    private function findCandidateDateWithinMonthForChain(
        ScheduleSlot $slot,
        Carbon $startDate,
        Carbon $monthStart,
        Carbon $monthEnd,
        Collection $holidayDates,
        Collection $nonWorkingDates,
        Collection $templates,
        array &$classAssignments,
        array &$teacherAssignments,
        array $planMonthlyScheduleIds,
        array $movableSlotIds
    ): ?Carbon {
        $periodNumber = (int) $slot->period_number;
        $classId = is_numeric($slot->class_id) ? (int) $slot->class_id : null;
        $teacherId = $slot->teacher_id !== null ? (int) $slot->teacher_id : null;

        $candidate = $startDate->copy()->startOfDay();
        if ($candidate->lt($monthStart)) {
            $candidate = $monthStart->copy();
        }
        while ($candidate->lte($monthEnd)) {
            $candidateDate = $candidate->format('Y-m-d');

            if ($holidayDates->contains($candidateDate) || $nonWorkingDates->contains($candidateDate)) {
                $candidate->addDay();
                continue;
            }

            $matchesTemplate = $templates->isEmpty() || $this->hasMatchingTemplateForDate($templates, $candidate);
            if (! $matchesTemplate) {
                $candidate->addDay();
                continue;
            }

            if ($candidate->isWeekend()) {
                $allowWeekendByTemplate = (bool) config(
                    'schedule.holiday_reschedule.allow_weekend_if_template_allows',
                    true
                ) && ! $templates->isEmpty() && $matchesTemplate;

                if (! $allowWeekendByTemplate) {
                    $candidate->addDay();
                    continue;
                }
            }

            $classKey = null;
            $teacherKey = null;

            if ($classId !== null) {
                $classKey = $classId . '|' . $candidateDate . '|' . $periodNumber;
                if (isset($classAssignments[$classKey]) && $classAssignments[$classKey] !== (int) $slot->id) {
                    $candidate->addDay();
                    continue;
                }

                $hasClassCollision = ScheduleSlot::query()
                    ->whereIn('monthly_schedule_id', $planMonthlyScheduleIds)
                    ->where('class_id', $classId)
                    ->whereDate('date', $candidateDate)
                    ->where('period_number', $periodNumber)
                    ->where('id', '!=', $slot->id)
                    ->when(
                        $movableSlotIds !== [],
                        static fn ($query) => $query->whereNotIn('id', $movableSlotIds)
                    )
                    ->exists();

                if ($hasClassCollision) {
                    $candidate->addDay();
                    continue;
                }
            }

            if ($teacherId !== null) {
                $teacherKey = $teacherId . '|' . $candidateDate . '|' . $periodNumber;
                if (isset($teacherAssignments[$teacherKey]) && $teacherAssignments[$teacherKey] !== (int) $slot->id) {
                    $candidate->addDay();
                    continue;
                }

                $hasTeacherCollision = ScheduleSlot::query()
                    ->where('teacher_id', $teacherId)
                    ->whereDate('date', $candidateDate)
                    ->where('period_number', $periodNumber)
                    ->where('id', '!=', $slot->id)
                    ->when(
                        $movableSlotIds !== [],
                        static fn ($query) => $query->whereNotIn('id', $movableSlotIds)
                    )
                    ->exists();

                if ($hasTeacherCollision) {
                    $candidate->addDay();
                    continue;
                }
            }

            if ($classKey !== null) {
                $classAssignments[$classKey] = (int) $slot->id;
            }

            if ($teacherKey !== null) {
                $teacherAssignments[$teacherKey] = (int) $slot->id;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @return array<int>
     */
    private function resolvePlanMonthlyScheduleIds(int $planId, int $fallbackMonthlyScheduleId): array
    {
        if ($planId > 0) {
            $ids = MonthlySchedule::query()
                ->where('plan_id', $planId)
                ->pluck('id')
                ->map(static fn($id) => (int) $id)
                ->values()
                ->all();

            if ($ids !== []) {
                return $ids;
            }
        }

        return [$fallbackMonthlyScheduleId];
    }

    private function hasMatchingTemplateForDate(Collection $templates, Carbon $candidate): bool
    {
        return $templates->contains(function (PlanTemplates $template) use ($candidate): bool {
            $allowedDays = $this->extractTemplateAllowedDays($template);
            $candidateLegacyDay = $candidate->dayOfWeekIso + 1;

            if ($allowedDays !== [] && ! in_array($candidateLegacyDay, $allowedDays, true)) {
                return false;
            }

            if ($template->start_date !== null && $candidate->lt($template->start_date->copy()->startOfDay())) {
                return false;
            }

            if ($template->end_date !== null && $candidate->gt($template->end_date->copy()->startOfDay())) {
                return false;
            }

            return true;
        });
    }

    /**
     * @return array<int>
     */
    private function extractTemplateAllowedDays(PlanTemplates $template): array
    {
        $rawDays = is_array($template->days_of_week)
            ? $template->days_of_week
            : (json_decode((string) $template->days_of_week, true) ?: []);

        return $this->normalizeTemplateWeekdays($rawDays, $template->day_of_week);
    }

    /**
     * @param array<int|string, mixed> $rawDays
     * @return array<int>
     */
    private function normalizeTemplateWeekdays(array $rawDays, mixed $fallbackDay): array
    {
        $values = collect($rawDays)
            ->filter(static fn($item) => is_numeric($item))
            ->map(static fn($item) => (int) $item)
            ->values();

        if ($values->isEmpty() && is_numeric($fallbackDay)) {
            $values = collect([(int) $fallbackDay]);
        }

        // Current module convention: 2..8 (Thu 2..Chu nhat)
        $currentConvention = $values
            ->filter(static fn(int $dow) => $dow >= 2 && $dow <= 8)
            ->unique()
            ->sort()
            ->values();

        if ($currentConvention->isNotEmpty()) {
            return $currentConvention->all();
        }

        // Legacy ISO convention: 1..7 -> 2..8
        $isoConvention = $values
            ->filter(static fn(int $dow) => $dow >= 1 && $dow <= 7)
            ->map(static fn(int $dow) => $dow + 1)
            ->unique()
            ->sort()
            ->values();

        if ($isoConvention->isNotEmpty()) {
            return $isoConvention->all();
        }

        // Legacy convention: 0..6 (CN..Thu 7) -> 8,2..7
        return $values
            ->filter(static fn(int $dow) => $dow >= 0 && $dow <= 6)
            ->map(static fn(int $dow) => $dow === 0 ? 8 : $dow + 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function normalizeSessionValue(string $value): string
    {
        $normalized = mb_strtolower(trim($value), 'UTF-8');
        $normalized = str_replace(['á', 'à', 'ạ', 'ả', 'ã', 'ă', 'ắ', 'ằ', 'ặ', 'ẳ', 'ẵ', 'â', 'ấ', 'ầ', 'ậ', 'ẩ', 'ẫ'], 'a', $normalized);
        $normalized = str_replace(['é', 'è', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ế', 'ề', 'ệ', 'ể', 'ễ'], 'e', $normalized);
        $normalized = str_replace(['í', 'ì', 'ị', 'ỉ', 'ĩ'], 'i', $normalized);
        $normalized = str_replace(['ó', 'ò', 'ọ', 'ỏ', 'õ', 'ô', 'ố', 'ồ', 'ộ', 'ổ', 'ỗ', 'ơ', 'ớ', 'ờ', 'ợ', 'ở', 'ỡ'], 'o', $normalized);
        $normalized = str_replace(['ú', 'ù', 'ụ', 'ủ', 'ũ', 'ư', 'ứ', 'ừ', 'ự', 'ử', 'ữ'], 'u', $normalized);
        $normalized = str_replace(['ý', 'ỳ', 'ỵ', 'ỷ', 'ỹ'], 'y', $normalized);
        $normalized = str_replace('đ', 'd', $normalized);

        if (in_array($normalized, ['sang', 'morning'], true)) {
            return 'sang';
        }

        if (in_array($normalized, ['chieu', 'afternoon'], true)) {
            return 'chieu';
        }

        return $normalized;
    }

    private function isPeriodInRange(string $range, int $periodNumber): bool
    {
        if ($periodNumber <= 0) {
            return false;
        }

        $parsed = trim($range);
        if ($parsed === '') {
            return false;
        }

        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $parsed, $matches) === 1) {
            $start = (int) $matches[1];
            $end = (int) $matches[2];
            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }

            return $periodNumber >= $start && $periodNumber <= $end;
        }

        if (preg_match('/^\d+$/', $parsed) === 1) {
            return $periodNumber === (int) $parsed;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSlotPayload(ScheduleSlot $slot): array
    {
        return [
            'class_id' => $slot->class_id,
            'teacher_id' => $slot->teacher_id,
            'subject_id' => $slot->subject_id,
            'subject_lesson_id' => $slot->subject_lesson_id,
            'room_id' => $slot->room_id,
            'date' => $slot->date?->format('Y-m-d'),
            'day_of_week' => $slot->day_of_week,
            'period' => $slot->period,
            'period_number' => $slot->period_number,
            'subject' => $slot->subject,
            'content' => $slot->content,
            'slot_status' => $slot->slot_status,
            'actual_content' => $slot->actual_content,
            'note' => $slot->note,
        ];
    }
}
