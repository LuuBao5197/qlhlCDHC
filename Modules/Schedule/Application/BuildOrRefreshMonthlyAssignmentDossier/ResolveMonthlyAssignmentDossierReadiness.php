<?php

namespace Modules\Schedule\Application\BuildOrRefreshMonthlyAssignmentDossier;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Training\Models\Department;

/**
 * Xac dinh cac Khoa bat buoc gui phan cong cho 1 thang (Khoa co tiet mon hoc trong thang do)
 * va trang thai batch hien tai cua tung Khoa, de biet Khoa nao da "san sang" (ready) cho PDT tong hop.
 */
class ResolveMonthlyAssignmentDossierReadiness
{
    public const STATUS_MISSING = 'missing';
    public const STATUS_PENDING_DEPARTMENT_REVIEW = 'pending_department_review';
    public const STATUS_PENDING_TRAINING_OFFICE_REVIEW = 'pending_training_office_review';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_READY = 'ready';

    public const STATUS_LABELS = [
        self::STATUS_MISSING => 'Chưa gửi',
        self::STATUS_PENDING_DEPARTMENT_REVIEW => 'Đang chờ Lãnh đạo Khoa duyệt',
        self::STATUS_PENDING_TRAINING_OFFICE_REVIEW => 'Đang chờ PĐT duyệt',
        self::STATUS_RETURNED => 'Đã bị trả về, chưa gửi lại',
        self::STATUS_READY => 'Sẵn sàng tổng hợp',
    ];

    /**
     * @return array{
     *     departments: Collection<int, array{
     *         department_id:int,
     *         department_name:string,
     *         batch_id:int|null,
     *         readiness_status:string,
     *         is_ready:bool
     *     }>,
     *     ready_batches: Collection<int, DepartmentMonthlyAssignmentBatch>,
     *     is_complete: bool
     * }
     */
    public function handle(int $month, int $year): array
    {
        $departmentIds = $this->resolveRequiredDepartmentIds($month, $year);

        $departments = Department::query()
            ->whereIn('id', $departmentIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $batches = DepartmentMonthlyAssignmentBatch::query()
            ->whereIn('department_id', $departmentIds)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('department_id');

        $departmentReadiness = collect($departmentIds)
            ->map(function (int $departmentId) use ($departments, $batches): array {
                $batch = $batches->get($departmentId);

                return [
                    'department_id' => $departmentId,
                    'department_name' => $departments->get($departmentId)?->name ?? ('Khoa #' . $departmentId),
                    'batch_id' => $batch?->id,
                    'readiness_status' => $this->resolveBatchReadinessStatus($batch),
                    'is_ready' => $this->isReady($batch),
                ];
            })
            ->sortBy('department_name')
            ->values();

        return [
            'departments' => $departmentReadiness,
            'ready_batches' => $batches->filter(fn (DepartmentMonthlyAssignmentBatch $batch): bool => $this->isReady($batch))->values(),
            'is_complete' => $departmentReadiness->isNotEmpty() && $departmentReadiness->every(fn (array $item): bool => $item['is_ready']),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveRequiredDepartmentIds(int $month, int $year): array
    {
        return DB::table('schedule_slots')
            ->join('monthly_schedules', 'monthly_schedules.id', '=', 'schedule_slots.monthly_schedule_id')
            ->join('subjects', 'subjects.id', '=', 'schedule_slots.subject_id')
            ->where('monthly_schedules.month', $month)
            ->where('monthly_schedules.year', $year)
            ->where('schedule_slots.slot_type', 'subject')
            ->whereNotNull('subjects.department_id')
            ->distinct()
            ->pluck('subjects.department_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function isReady(?DepartmentMonthlyAssignmentBatch $batch): bool
    {
        return $batch !== null
            && $batch->status === DepartmentMonthlyAssignmentBatch::STATUS_APPROVED
            && $batch->current_step === DepartmentMonthlyAssignmentBatch::STEP_COMPLETED;
    }

    private function resolveBatchReadinessStatus(?DepartmentMonthlyAssignmentBatch $batch): string
    {
        if ($batch === null || $batch->status === DepartmentMonthlyAssignmentBatch::STATUS_DRAFT) {
            return self::STATUS_MISSING;
        }

        if ($this->isReady($batch)) {
            return self::STATUS_READY;
        }

        if ($batch->status === DepartmentMonthlyAssignmentBatch::STATUS_RETURNED) {
            return self::STATUS_RETURNED;
        }

        if ($batch->status === DepartmentMonthlyAssignmentBatch::STATUS_SUBMITTED
            && $batch->current_step === DepartmentMonthlyAssignmentBatch::STEP_TRAINING_OFFICE_REVIEW) {
            return self::STATUS_PENDING_TRAINING_OFFICE_REVIEW;
        }

        return self::STATUS_PENDING_DEPARTMENT_REVIEW;
    }
}
