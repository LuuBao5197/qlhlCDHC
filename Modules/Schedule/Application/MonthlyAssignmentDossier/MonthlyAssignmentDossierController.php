<?php

namespace Modules\Schedule\Application\MonthlyAssignmentDossier;

use App\Http\Controllers\Controller;
use App\Services\ApprovalAuthorityService;
use App\Support\AdminBackfillContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\BuildOrRefreshMonthlyAssignmentDossier\BuildOrRefreshMonthlyAssignmentDossier;
use Modules\Schedule\Application\BuildOrRefreshMonthlyAssignmentDossier\ResolveMonthlyAssignmentDossierReadiness;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;

class MonthlyAssignmentDossierController extends Controller
{
    public function __construct(
        private BuildOrRefreshMonthlyAssignmentDossier $buildOrRefreshDossier,
        private ResolveMonthlyAssignmentDossierReadiness $readinessResolver,
        private ApprovalAuthorityService $approvalAuthority
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MonthlyAssignmentDossier::class);

        $month = $request->integer('month') >= 1 && $request->integer('month') <= 12
            ? $request->integer('month')
            : (int) now()->month;
        $year = $request->integer('year') > 0 ? $request->integer('year') : (int) now()->year;

        $readiness = $this->readinessResolver->handle($month, $year);

        $dossiers = MonthlyAssignmentDossier::query()
            ->with(['submittedBy', 'reviewedBy', 'createdBy'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(12)
            ->withQueryString();

        return response()->view('schedule::monthly-assignment-dossier.index', [
            'dossiers' => $dossiers,
            'readiness' => $readiness,
            'statusLabels' => ResolveMonthlyAssignmentDossierReadiness::STATUS_LABELS,
            'month' => $month,
            'year' => $year,
            'canBuild' => $request->user()->can('build', MonthlyAssignmentDossier::class),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('build', MonthlyAssignmentDossier::class);

        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ] + AdminBackfillContext::rules(), AdminBackfillContext::messages());

        $isAdminBackfill = AdminBackfillContext::isActive($request);

        try {
            $dossier = DB::transaction(function () use ($request, $validated, $isAdminBackfill): MonthlyAssignmentDossier {
                $dossier = $this->buildOrRefreshDossier->handle((int) $validated['month'], (int) $validated['year'], $request->user());

                // Admin bo sung du lieu cu: duyet nhanh ho so qua ca PDT va BGH ngay khi tao,
                // khong can cho tung cap phe duyet thu cong.
                if ($isAdminBackfill) {
                    $dossier = $this->finalizeAdminBackfillDossier($dossier, $request);
                }

                return $dossier;
            });
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        AdminBackfillContext::log($request, 'monthly_assignment_dossier_build', $dossier);

        return redirect()
            ->route('monthly-assignment-dossiers.show', $dossier->id)
            ->with('success', $isAdminBackfill
                ? 'Da tao va duyet nhanh ho so phan cong thang tong hop (bo sung du lieu cu).'
                : 'Da tao/lam moi ban nhap ho so phan cong thang tong hop.');
    }

    private function finalizeAdminBackfillDossier(MonthlyAssignmentDossier $dossier, Request $request): MonthlyAssignmentDossier
    {
        $now = now();
        $actorId = $request->user()->id;

        $dossier->update([
            'submitted_by' => $actorId,
            'submitted_at' => $now,
            'reviewed_by' => $actorId,
            'reviewed_at' => $now,
            'status' => MonthlyAssignmentDossier::STATUS_APPROVED,
            'current_step' => MonthlyAssignmentDossier::STEP_COMPLETED,
            'completed_at' => $now,
        ]);

        $approvalRequest = ApprovalRequest::query()->firstOrNew([
            'entity_type' => MonthlyAssignmentDossier::class,
            'entity_id' => $dossier->id,
        ]);
        $approvalRequest->fill([
            'submitted_by' => $actorId,
            'current_step' => MonthlyAssignmentDossier::STEP_LEADERSHIP_REVIEW,
            'status' => 'approved',
            'submitted_at' => $now,
            'completed_at' => $now,
        ]);
        $approvalRequest->save();

        ApprovalAction::query()->create([
            'approval_request_id' => $approvalRequest->id,
            'step_code' => 'admin_backfill',
            'action' => 'approve',
            'acted_by' => $actorId,
            'acted_at' => $now,
            'comment' => $request->input('admin_backfill_reason'),
        ]);

        return $dossier->fresh(['createdBy', 'submittedBy', 'reviewedBy', 'batches.department']);
    }

    public function show(Request $request, int $id): Response
    {
        $dossier = MonthlyAssignmentDossier::query()
            ->with([
                'createdBy',
                'submittedBy',
                'reviewedBy',
                'batches.department',
                'batches.submittedBy',
                'batches.departmentReviewedBy',
                'batches.trainingOfficeReviewedBy',
                'batches.batchSlots',
            ])
            ->withCount('batches')
            ->findOrFail($id);

        $this->authorize('view', $dossier);

        $user = $request->user();

        return response()->view('schedule::monthly-assignment-dossier.show', [
            'dossier' => $dossier,
            'canSubmit' => $user->can('build', MonthlyAssignmentDossier::class)
                && in_array($dossier->status, [MonthlyAssignmentDossier::STATUS_DRAFT, MonthlyAssignmentDossier::STATUS_RETURNED], true),
            'canTrainingOfficeReview' => $this->approvalAuthority->canApproveAsTrainingOffice($user)
                && $dossier->status === MonthlyAssignmentDossier::STATUS_SUBMITTED
                && $dossier->current_step === MonthlyAssignmentDossier::STEP_TRAINING_OFFICE_REVIEW,
            'canLeadershipReview' => $this->approvalAuthority->canApproveAsLeadership($user)
                && $dossier->status === MonthlyAssignmentDossier::STATUS_SUBMITTED
                && $dossier->current_step === MonthlyAssignmentDossier::STEP_LEADERSHIP_REVIEW,
        ]);
    }
}
