<?php

namespace Modules\Schedule\Application\ExportDepartmentSchedule;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Schedule\Application\AssignMonthlySchedule\MonthlyAssignmentScopeResolver;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
use Modules\Training\Models\Department;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDepartmentScheduleController extends Controller
{
    private const XLSX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(
        private MonthlyAssignmentScopeResolver $scopeResolver,
        private DepartmentScheduleRowBuilder $rowBuilder,
        private DepartmentScheduleXlsxWriter $xlsxWriter,
    ) {}

    public function __invoke(ExportDepartmentScheduleRequest $request, int $id): StreamedResponse
    {
        $anchor = MonthlySchedule::query()->findOrFail($id);

        $scope = $this->scopeResolver->resolve($anchor, $request->user(), $request->integer('department_id') ?: null);
        if ($scope === null) {
            abort(422, 'Khong xac dinh duoc khoa de xuat bao cao phan cong.');
        }

        $department = Department::query()->findOrFail($scope['department_id']);
        $period = $request->period($anchor);

        $slots = $this->loadSlots($scope['department_subject_ids'], $period);
        $rows = $this->rowBuilder->build($slots, $this->loadPriorUsage($slots, $period));

        $spreadsheet = $this->xlsxWriter->write(
            $rows,
            $period,
            (string) $department->code,
            (string) $department->name,
            trim((string) $request->input('document_number')) ?: sprintf('01/KH-%s', $department->code),
            $request->issuedDate(),
            trim((string) $request->input('signer_name')) ?: $this->departmentHeadName($department),
        );

        $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '', Str::ascii((string) $department->code));
        $fileName = sprintf('PhanCong_%s_%s.xlsx', $safeCode, $period->fileSuffix());

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, ['Content-Type' => self::XLSX_CONTENT_TYPE]);
    }

    /**
     * Lay slot theo mon cua khoa trong khoang ngay (khong gioi han theo lich thang de tuan vat thang van du).
     *
     * @param array<int, int> $departmentSubjectIds
     * @return Collection<int, ScheduleSlot>
     */
    private function loadSlots(array $departmentSubjectIds, DepartmentScheduleExportPeriod $period): Collection
    {
        if ($departmentSubjectIds === []) {
            return collect();
        }

        return ScheduleSlot::query()
            ->with([
                'trainingClass',
                'teacher',
                'subjectModel',
                'subjectLesson',
                'room',
                'scheduleSlotGroup.teacher',
                'scheduleSlotGroup.room',
                'scheduleSlotSubgroups.teacher',
                'scheduleSlotSubgroups.room',
            ])
            ->where('slot_type', 'subject')
            ->where('slot_status', '!=', 'cancelled')
            ->whereIn('subject_id', $departmentSubjectIds)
            ->whereBetween('date', [$period->start->startOfDay(), $period->end->endOfDay()])
            ->orderBy('date')
            ->orderBy('period_number')
            ->orderBy('class_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * So tiet moi (lop, bai hoc) da hoc truoc ky bao cao, de danh dau "(tiếp)".
     *
     * @param Collection<int, ScheduleSlot> $slots
     * @return array<int, array<int, int>>
     */
    private function loadPriorUsage(Collection $slots, DepartmentScheduleExportPeriod $period): array
    {
        $classIds = $slots->pluck('class_id')->filter()->unique()->values();
        $lessonIds = $slots->pluck('subject_lesson_id')->filter()->unique()->values();
        if ($classIds->isEmpty() || $lessonIds->isEmpty()) {
            return [];
        }

        $usage = [];
        $rows = ScheduleSlot::query()
            ->selectRaw('class_id, subject_lesson_id, COUNT(*) as used_periods')
            ->where('slot_type', 'subject')
            ->where('slot_status', '!=', 'cancelled')
            ->whereIn('class_id', $classIds)
            ->whereIn('subject_lesson_id', $lessonIds)
            ->where('date', '<', $period->start->startOfDay())
            ->groupBy('class_id', 'subject_lesson_id')
            ->get();

        foreach ($rows as $row) {
            $usage[(int) $row->class_id][(int) $row->subject_lesson_id] = (int) $row->used_periods;
        }

        return $usage;
    }

    private function departmentHeadName(Department $department): string
    {
        $head = User::query()
            ->where('department_id', $department->id)
            ->whereHas('roles', fn ($query) => $query->where('slug', Role::DEPARTMENT_HEAD))
            ->orderBy('id')
            ->first();

        return (string) ($head?->name ?? '');
    }
}
