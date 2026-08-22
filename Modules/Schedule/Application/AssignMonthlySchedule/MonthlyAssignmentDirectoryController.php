<?php

namespace Modules\Schedule\Application\AssignMonthlySchedule;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Training\Models\Department;

/**
 * Danh sach cac to hop (Khoa + thang/nam) co lich giang day, de dieu huong toi
 * man "Phan cong giang day theo thang" (monthly-schedule.assignment) cho bat ky
 * thang/nam/khoa nao - ke ca du lieu lich su - thay vi chi 3 the goi y gan nhat.
 */
class MonthlyAssignmentDirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorizeDirectory($user);

        $filters = $this->resolveFilters($request, $user);

        $inner = DB::table('schedule_slots')
            ->join('monthly_schedules', 'monthly_schedules.id', '=', 'schedule_slots.monthly_schedule_id')
            ->join('subjects', 'subjects.id', '=', 'schedule_slots.subject_id')
            ->where('schedule_slots.slot_type', 'subject')
            ->whereNotNull('subjects.department_id')
            ->select([
                'subjects.department_id',
                'monthly_schedules.month',
                'monthly_schedules.year',
                DB::raw('MIN(monthly_schedules.id) as anchor_monthly_schedule_id'),
                DB::raw('COUNT(DISTINCT schedule_slots.id) as slot_count'),
            ])
            ->groupBy('subjects.department_id', 'monthly_schedules.month', 'monthly_schedules.year');

        $rows = DB::table(DB::raw("({$inner->toSql()}) as directory"))
            ->mergeBindings($inner)
            ->when($filters['department_id'] !== null, fn ($q) => $q->where('department_id', $filters['department_id']))
            ->when($filters['month'] !== null, fn ($q) => $q->where('month', $filters['month']))
            ->when($filters['year'] !== null, fn ($q) => $q->where('year', $filters['year']))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('department_id')
            ->paginate(20)
            ->withQueryString();

        $departmentIds = collect($rows->items())->pluck('department_id')->unique()->all();
        $departments = Department::query()
            ->whereIn('id', $departmentIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $batches = DepartmentMonthlyAssignmentBatch::query()
            ->whereIn('department_id', $departmentIds !== [] ? $departmentIds : [0])
            ->get()
            ->keyBy(fn (DepartmentMonthlyAssignmentBatch $batch): string => sprintf('%d|%d|%d', $batch->department_id, $batch->month, $batch->year));

        $rows->getCollection()->transform(function (object $row) use ($departments, $batches): array {
            $batch = $batches->get(sprintf('%d|%d|%d', $row->department_id, $row->month, $row->year));

            return [
                'department_id' => (int) $row->department_id,
                'department_name' => $departments->get($row->department_id)?->name ?? ('Khoa #' . $row->department_id),
                'month' => (int) $row->month,
                'year' => (int) $row->year,
                'anchor_monthly_schedule_id' => (int) $row->anchor_monthly_schedule_id,
                'slot_count' => (int) $row->slot_count,
                'batch_status' => $batch?->status,
                'batch_id' => $batch?->id,
            ];
        });

        return response()->view('schedule::monthly-schedule-directory.index', [
            'rows' => $rows,
            'filters' => $filters,
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'isDepartmentStaff' => $user->isDepartmentStaff() && ! $user->isAdmin() && ! $user->isTrainingOffice(),
        ]);
    }

    private function authorizeDirectory(?User $user): void
    {
        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin() || $user->isTrainingOffice() || $user->isLeadership() || $user->isDepartmentStaff()) {
            return;
        }

        abort(403);
    }

    /**
     * @return array{department_id:int|null, month:int|null, year:int|null}
     */
    private function resolveFilters(Request $request, User $user): array
    {
        $departmentId = $request->integer('department_id');
        $departmentId = $departmentId > 0 ? $departmentId : null;

        // Khoa (khong phai admin/PDT) chi duoc xem du lieu cua khoa minh.
        if ($user->isDepartmentStaff() && ! $user->isAdmin() && ! $user->isTrainingOffice()) {
            $departmentId = (int) $user->department_id;
        }

        $month = $request->integer('month');
        $month = $month >= 1 && $month <= 12 ? $month : null;

        $year = $request->integer('year');
        $year = $year > 0 ? $year : null;

        return [
            'department_id' => $departmentId,
            'month' => $month,
            'year' => $year,
        ];
    }
}
