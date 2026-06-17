<?php

namespace Modules\Schedule\Application\ManageSemesterEvents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\SemesterEvent;
use Modules\Training\Models\TrainingClass;

class ManageSemesterEventsController extends Controller
{
    public function index(int $id)
    {
        $plan = Plans::query()
            ->with('trainingBatch')
            ->findOrFail($id);

        $classesQuery = TrainingClass::query()->orderBy('code');
        if ($plan->training_batch_id) {
            $classesQuery->where('training_batch_id', $plan->training_batch_id);
        }

        $classes = $classesQuery->get(['id', 'code', 'name']);

        $events = SemesterEvent::query()
            ->with('trainingClass')
            ->where('plan_id', $plan->id)
            ->orderByRaw('CASE WHEN class_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        return view('schedule::ScheduleSemester.events', [
            'plan' => $plan,
            'classes' => $classes,
            'events' => $events,
            'eventTypes' => [
                ['value' => 'holiday', 'label' => 'Nghi le', 'color' => '#ffedd5'],
                ['value' => 'review', 'label' => 'On thi', 'color' => '#dbeafe'],
                ['value' => 'exam', 'label' => 'Thi', 'color' => '#fee2e2'],
                ['value' => 'other', 'label' => 'Su kien khac', 'color' => '#ede9fe'],
            ],
        ]);
    }

    public function store(Request $request, int $id)
    {
        $plan = Plans::query()->findOrFail($id);

        $data = $this->validatePayload($request, $plan->id, null);
        $this->assertNoConflicts($plan->id, $data);
        SemesterEvent::query()->create($data);

        return back()->with('success', 'Da them su kien hoc ky.');
    }

    public function update(Request $request, int $id, int $eventId)
    {
        $plan = Plans::query()->findOrFail($id);
        $event = SemesterEvent::query()->where('plan_id', $plan->id)->findOrFail($eventId);

        $data = $this->validatePayload($request, $plan->id, $event->id);
        $this->assertNoConflicts($plan->id, $data, $event->id);
        $event->update($data);

        return back()->with('success', 'Da cap nhat su kien hoc ky.');
    }

    public function destroy(Request $request, int $id, int $eventId)
    {
        $plan = Plans::query()->findOrFail($id);
        $event = SemesterEvent::query()->where('plan_id', $plan->id)->findOrFail($eventId);
        $event->delete();

        return back()->with('success', 'Da xoa su kien hoc ky.');
    }

    private function validatePayload(Request $request, int $planId, ?int $ignoreId): array
    {
        $plan = Plans::query()->findOrFail($planId);
        $validated = $request->validate([
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'event_type' => ['required', Rule::in(['holiday', 'review', 'exam', 'other'])],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'period_from' => ['nullable', 'integer', 'min:1', 'max:9'],
            'period_to' => ['nullable', 'integer', 'min:1', 'max:9'],
            'note' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validated['event_type'] === 'holiday') {
            $validated['class_id'] = null;
        } elseif (empty($validated['class_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'class_id' => 'Su kien cua lop phai chon lop ap dung.',
            ]);
        } elseif ($plan->training_batch_id) {
            $class = TrainingClass::query()->find((int) $validated['class_id']);

            if (!$class || (int) $class->training_batch_id !== (int) $plan->training_batch_id) {
                throw ValidationException::withMessages([
                    'class_id' => 'Lop duoc chon khong thuoc khoa dao tao cua ke hoach nay.',
                ]);
            }
        }

        $validated['plan_id'] = $planId;
        $validated['period_from'] = $validated['period_from'] ?? 1;
        $validated['period_to'] = $validated['period_to'] ?? 9;
        $validated['color'] = match ($validated['event_type']) {
            'holiday' => '#ffedd5',
            'review' => '#dbeafe',
            'exam' => '#fee2e2',
            default => '#ede9fe',
        };
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        unset($validated['_token'], $validated['_method']);

        return $validated;
    }

    private function assertNoConflicts(int $planId, array $candidate, ?int $ignoreId = null): void
    {
        $existingEvents = SemesterEvent::query()
            ->where('plan_id', $planId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->get();

        foreach ($existingEvents as $existing) {
            if (!$this->eventsCanConflict($candidate, $existing->toArray())) {
                continue;
            }

            if ($this->eventsOverlap($candidate, $existing->toArray())) {
                throw ValidationException::withMessages([
                    'start_date' => $this->conflictMessage($candidate, $existing->toArray()),
                ]);
            }
        }
    }

    private function eventsCanConflict(array $candidate, array $existing): bool
    {
        $candidateClassId = $candidate['class_id'] ?? null;
        $existingClassId = $existing['class_id'] ?? null;

        if ($candidateClassId === null || $existingClassId === null) {
            return true;
        }

        return (int) $candidateClassId === (int) $existingClassId;
    }

    private function eventsOverlap(array $first, array $second): bool
    {
        $firstStart = strtotime((string) ($first['start_date'] ?? ''));
        $firstEnd = strtotime((string) ($first['end_date'] ?? ''));
        $secondStart = strtotime((string) ($second['start_date'] ?? ''));
        $secondEnd = strtotime((string) ($second['end_date'] ?? ''));

        if ($firstStart === false || $firstEnd === false || $secondStart === false || $secondEnd === false) {
            return false;
        }

        $periodFrom = max((int) ($first['period_from'] ?? 1), (int) ($second['period_from'] ?? 1));
        $periodTo = min((int) ($first['period_to'] ?? 9), (int) ($second['period_to'] ?? 9));

        return $firstStart <= $secondEnd && $secondStart <= $firstEnd && $periodFrom <= $periodTo;
    }

    private function conflictMessage(array $candidate, array $existing): string
    {
        $candidateLabel = $candidate['title'] ?? 'su kien moi';
        $existingLabel = $existing['title'] ?? 'su kien da co';
        $scope = ($candidate['class_id'] ?? null) === null || ($existing['class_id'] ?? null) === null
            ? 'su kien chung'
            : 'su kien cua lop';

        return "Khong the luu {$scope} '{$candidateLabel}' vi bi trung ngay/tiet voi '{$existingLabel}'.";
    }
}
