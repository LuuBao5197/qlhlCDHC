<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use App\Http\Controllers\Controller;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\PlanTemplates;
use Modules\Training\Models\Subject;
use Modules\Training\Models\TrainingClass;

class UpdateScheduleSemesterController extends Controller
{
    public function __construct(
        private UpdateScheduleSemesterHandler $handler
    ) {}

    public function showForm($id)
    {
        $plan = Plans::with('planTemplates.trainingClass', 'planTemplates.subjects')
            ->findOrFail($id);

        $classes = TrainingClass::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

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

        // Convert PlanTemplates to class_tab_rules format for frontend
        $oldRules = $this->convertTemplatesToRules($plan->planTemplates);

        // Get selected class IDs
        $selectedClassIds = $plan->planTemplates
            ->pluck('class_id')
            ->unique()
            ->values()
            ->toArray();

        return view('schedule::ScheduleSemester.edit', [
            'plan' => $plan,
            'classes' => $classes,
            'subjects' => $subjects,
            'subjectSuggestions' => $subjectSuggestions,
            'weekdayOptions' => $weekdayOptions,
            'oldRules' => $oldRules,
            'selectedClassIds' => $selectedClassIds,
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
