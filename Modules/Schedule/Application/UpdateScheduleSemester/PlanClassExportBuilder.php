<?php

namespace Modules\Schedule\Application\UpdateScheduleSemester;

use Modules\Schedule\Models\Plans;
use Modules\Training\Models\TrainingClass;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Builds the per-class Excel file of a saved plan, in exactly the column layout
 * the import expects, so it can be edited and attached back to the class tab.
 */
class PlanClassExportBuilder
{
    public const HEADERS = [
        'row_type', 'class_code', 'start_date', 'end_date', 'weekdays', 'period_from', 'period_to',
        'subject', 'content', 'event_type', 'title', 'note', 'recurrence', 'occurrence',
    ];

    /**
     * @return array<int, array<int, string>> rows, header first
     */
    public function rows(Plans $plan, TrainingClass $class): array
    {
        $plan->loadMissing('planTemplates.subjects', 'semesterEvents');

        $rows = [self::HEADERS];

        $templates = $plan->planTemplates
            ->where('class_id', $class->id)
            ->sortBy(fn ($template) => $template->start_date?->toDateString() . '|' . $template->period_range);

        foreach ($templates as $template) {
            $weekdays = collect($template->days_of_week ?: [$template->day_of_week])
                ->filter(fn ($day) => is_numeric($day))
                ->unique()
                ->sort()
                ->implode(',');

            $period = preg_match('/^(\d+)\s*-\s*(\d+)$/', trim((string) $template->period_range), $m) ? [$m[1], $m[2]] : ['', ''];

            $rows[] = [
                'rule', $class->code,
                $template->start_date->toDateString(), $template->end_date->toDateString(),
                $weekdays, $period[0], $period[1],
                (string) ($template->subjects?->code ?? ''), (string) ($template->description ?? ''),
                '', '', '', '', '',
            ];
        }

        $events = $plan->semesterEvents
            ->where('class_id', $class->id)
            ->sortBy(fn ($event) => $event->start_date?->toDateString() . '|' . $event->period_from);

        foreach ($events as $event) {
            $rows[] = [
                'event', $class->code,
                $event->start_date->toDateString(), $event->end_date->toDateString(),
                '', (string) ($event->period_from ?? ''), (string) ($event->period_to ?? ''),
                '', '',
                (string) $event->event_type, (string) $event->title, (string) ($event->note ?? ''), '', '',
            ];
        }

        return $rows;
    }

    public function hasData(Plans $plan, int $classId): bool
    {
        return $plan->planTemplates->contains('class_id', $classId)
            || $plan->semesterEvents->contains('class_id', $classId);
    }

    /**
     * Writes the xlsx to a temp file and returns its path (caller deletes it).
     */
    public function writeXlsx(Plans $plan, TrainingClass $class): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($class->code, 0, 31));

        foreach ($this->rows($plan, $class) as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                // Strings only, so dates and "2,4" weekdays are never reinterpreted by Excel.
                $sheet->setCellValueExplicit([$colIndex + 1, $rowIndex + 1], (string) $value, DataType::TYPE_STRING);
            }
        }

        foreach (range(1, count(self::HEADERS)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'plan_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
