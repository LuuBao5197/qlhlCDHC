<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Schedule\Models\SemesterEvent;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;

class UpdateScheduleSemesterController extends Controller
{
    public function __construct(
        private UpdateScheduleSemesterHandler $handler
    ) {}

    public function showForm($id)
    {
        $plan = Plans::with('trainingBatch.trainingProgram', 'planTemplates.trainingClass', 'planTemplates.subjects')
            ->with(['semesterEvents.trainingClass'])
            ->findOrFail($id);

        $classesQuery = TrainingClass::query()
            ->orderBy('code');

        if ($plan->training_batch_id) {
            $classesQuery->where('training_batch_id', $plan->training_batch_id);
        }

        $classes = $classesQuery->get(['id', 'training_batch_id', 'code', 'name']);

        $subjects = Subject::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $subjectSuggestions = $subjects
            ->map(fn(Subject $subject) => [
                'code' => $subject->code,
                'name' => $subject->name,
            ])
            ->values();

        $weekdayOptions = [
            ['value' => 2, 'label' => 'Thu 2'],
            ['value' => 3, 'label' => 'Thu 3'],
            ['value' => 4, 'label' => 'Thu 4'],
            ['value' => 5, 'label' => 'Thu 5'],
            ['value' => 6, 'label' => 'Thu 6'],
            ['value' => 7, 'label' => 'Thu 7'],
            ['value' => 8, 'label' => 'Chu nhat'],
        ];

        $oldRules = $this->convertTemplatesToRules($plan->planTemplates);

        $selectedClassIds = $plan->planTemplates
            ->pluck('class_id')
            ->merge($plan->semesterEvents->pluck('class_id'))
            ->unique()
            ->values()
            ->toArray();

        $oldGlobalEvents = $this->convertSemesterEventsToForm($plan->semesterEvents->whereNull('class_id'));
        $oldClassEvents = $this->convertClassSemesterEventsToForm($plan->semesterEvents->whereNotNull('class_id'));

        $existingPlanKeys = Plans::query()
            ->whereNotNull('training_batch_id')
            ->where('id', '!=', $plan->id)
            ->get(['training_batch_id', 'semester', 'year'])
            ->map(fn (Plans $item) => implode('|', [
                $item->training_batch_id,
                $item->semester,
                $item->year,
            ]))
            ->values();

        $globalEventTypes = [
            ['value' => 'holiday', 'label' => 'Nghi le', 'color' => '#ffedd5'],
        ];

        $classEventTypes = [
            ['value' => 'review', 'label' => 'On thi', 'color' => '#dbeafe'],
            ['value' => 'exam', 'label' => 'Thi', 'color' => '#fee2e2'],
            ['value' => 'other', 'label' => 'Su kien khac', 'color' => '#ede9fe'],
        ];

        return view('schedule::ScheduleSemester.edit', [
            'plan' => $plan,
            'classes' => $classes,
            'subjects' => $subjects,
            'subjectSuggestions' => $subjectSuggestions,
            'weekdayOptions' => $weekdayOptions,
            'oldRules' => $oldRules,
            'oldGlobalEvents' => $oldGlobalEvents,
            'oldClassEvents' => $oldClassEvents,
            'selectedClassIds' => $selectedClassIds,
            'existingPlanKeys' => $existingPlanKeys,
            'globalEventTypes' => $globalEventTypes,
            'classEventTypes' => $classEventTypes,
        ]);
    }

    public function __invoke(UpdateScheduleSemesterRequest $request, $id)
    {
        return $this->handler->handle($request, $id);
    }

    private function convertTemplatesToRules($templates)
    {
        $rulesByClass = [];

        foreach ($templates as $template) {
            $classId = (string) $template->class_id;
            if (!isset($rulesByClass[$classId])) {
                $rulesByClass[$classId] = [];
            }

            $weekdaysRaw = $template->days_of_week;

            // days_of_week có thể là array cast sẵn, hoặc chuỗi JSON từ dữ liệu cũ
            if (is_string($weekdaysRaw)) {
                $decoded = json_decode($weekdaysRaw, true);
                $weekdaysRaw = is_array($decoded) ? $decoded : [];
            }

            if (!is_array($weekdaysRaw)) {
                $weekdaysRaw = [];
            }

            $weekdays = collect($weekdaysRaw)
                ->filter(fn($day) => is_numeric($day))
                ->map(fn($day) => (int) $day)
                ->filter(fn(int $day) => $day >= 2 && $day <= 8)
                ->unique()
                ->sort()
                ->values()
                ->all();

            // Fallback về day_of_week nếu days_of_week rỗng
            if ($weekdays === [] && is_numeric($template->day_of_week)) {
                $dow = (int) $template->day_of_week;
                if ($dow >= 2 && $dow <= 8) {
                    $weekdays = [$dow];
                }
            }

            $rulesByClass[$classId][] = [
                'start_date' => $template->start_date->toDateString(),
                'end_date' => $template->end_date->toDateString(),
                'period_from' => $this->getPeriodFrom($template->period_range),
                'period_to' => $this->getPeriodTo($template->period_range),
                'subject' => $template->subjects?->code ?? '',
                'content' => $template->description ?? '',
                'weekdays' => $weekdays,
            ];
        }

        return $rulesByClass;
    }

    private function convertSemesterEventsToForm($events): array
    {
        return $events->map(fn (SemesterEvent $event) => [
            'event_type' => $event->event_type,
            'title' => $event->title,
            'start_date' => optional($event->start_date)->toDateString() ?? '',
            'end_date' => optional($event->end_date)->toDateString() ?? '',
            'period_from' => $event->period_from,
            'period_to' => $event->period_to,
            'note' => $event->note,
            'sort_order' => $event->sort_order,
        ])->values()->all();
    }

    private function convertClassSemesterEventsToForm($events): array
    {
        return $events
            ->groupBy(fn (SemesterEvent $event) => (string) $event->class_id)
            ->map(fn ($group) => $group->map(fn (SemesterEvent $event) => [
                'event_type' => $event->event_type,
                'title' => $event->title,
                'start_date' => optional($event->start_date)->toDateString() ?? '',
                'end_date' => optional($event->end_date)->toDateString() ?? '',
                'period_from' => $event->period_from,
                'period_to' => $event->period_to,
                'note' => $event->note,
                'sort_order' => $event->sort_order,
            ])->values()->all())
            ->all();
    }

    private function getPeriodFrom($periodRange): ?int
    {
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', trim((string) $periodRange), $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    private function getPeriodTo($periodRange): ?int
    {
        if (preg_match('/^(\d+)\s*-\s*(\d+)$/', trim((string) $periodRange), $matches)) {
            return (int) $matches[2];
        }
        return null;
    }
}
