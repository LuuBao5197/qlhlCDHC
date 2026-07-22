<?php

namespace Modules\Schedule\Application\BuildOrRefreshMonthlyAssignmentDossier;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlyAssignmentDossier;

/**
 * Tao/lam moi ban nhap MonthlyAssignmentDossier cho 1 thang: chi cho phep khi TAT CA Khoa
 * bat buoc gui phan cong cua thang do da co batch "ready" (da qua ca Lanh dao Khoa lan PDT duyet).
 */
class BuildOrRefreshMonthlyAssignmentDossier
{
    public function __construct(
        private ResolveMonthlyAssignmentDossierReadiness $readinessResolver
    ) {}

    public function handle(int $month, int $year, User $actor): MonthlyAssignmentDossier
    {
        $readiness = $this->readinessResolver->handle($month, $year);

        if (! $readiness['is_complete']) {
            $missing = collect($readiness['departments'])
                ->reject(fn (array $item): bool => $item['is_ready'])
                ->map(fn (array $item): string => sprintf(
                    '%s: %s',
                    $item['department_name'],
                    ResolveMonthlyAssignmentDossierReadiness::STATUS_LABELS[$item['readiness_status']] ?? $item['readiness_status']
                ))
                ->values();

            throw ValidationException::withMessages([
                'dossier' => sprintf(
                    'Chua du Khoa de tong hop hoc so thang %02d/%d. Con thieu: %s',
                    $month,
                    $year,
                    $missing->implode('; ')
                ),
            ]);
        }

        $dossier = MonthlyAssignmentDossier::query()
            ->where('month', $month)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($dossier && ! in_array($dossier->status, [MonthlyAssignmentDossier::STATUS_DRAFT, MonthlyAssignmentDossier::STATUS_RETURNED], true)) {
            throw ValidationException::withMessages([
                'dossier' => 'Ho so thang nay da duoc gui/da duyet. Khong the tao lai ban nhap.',
            ]);
        }

        if (! $dossier) {
            $dossier = new MonthlyAssignmentDossier();
            $dossier->month = $month;
            $dossier->year = $year;
            $dossier->status = MonthlyAssignmentDossier::STATUS_DRAFT;
            $dossier->version = 1;
        } else {
            $dossier->version = (int) ($dossier->version ?? 1) + 1;
            $dossier->status = MonthlyAssignmentDossier::STATUS_DRAFT;
        }

        $dossier->current_step = MonthlyAssignmentDossier::STEP_DRAFT;
        $dossier->created_by = $dossier->created_by ?? $actor->id;
        $dossier->submitted_by = null;
        $dossier->submitted_at = null;
        $dossier->reviewed_by = null;
        $dossier->reviewed_at = null;
        $dossier->completed_at = null;
        $dossier->save();

        $syncData = collect($readiness['ready_batches'])
            ->mapWithKeys(fn (DepartmentMonthlyAssignmentBatch $batch): array => [
                $batch->id => ['batch_version' => $batch->version],
            ])
            ->all();
        $dossier->batches()->sync($syncData);

        return $dossier->fresh(['createdBy', 'submittedBy', 'reviewedBy', 'batches.department']);
    }
}
