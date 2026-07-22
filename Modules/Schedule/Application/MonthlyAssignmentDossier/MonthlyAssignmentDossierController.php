<?php

namespace Modules\Schedule\Application\MonthlyAssignmentDossier;

use App\Http\Controllers\Controller;
use App\Services\ApprovalAuthorityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Modules\Schedule\Application\BuildOrRefreshMonthlyAssignmentDossier\BuildOrRefreshMonthlyAssignmentDossier;
use Modules\Schedule\Application\BuildOrRefreshMonthlyAssignmentDossier\ResolveMonthlyAssignmentDossierReadiness;
use Modules\Schedule\Models\MonthlyAssignmentDossier;

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
        ]);

        try {
            $dossier = $this->buildOrRefreshDossier->handle((int) $validated['month'], (int) $validated['year'], $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('monthly-assignment-dossiers.show', $dossier->id)
            ->with('success', 'Da tao/lam moi ban nhap ho so phan cong thang tong hop.');
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
