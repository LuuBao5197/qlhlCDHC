<?php

namespace Modules\Reports\Application\TeachingStatistics;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeachingStatisticsController extends Controller
{
    public function __construct(private readonly TeachingStatisticsService $statistics)
    {
    }

    public function overview(Request $request): View
    {
        $this->authorizeReportAccess($request);

        $filters = $this->statistics->normalizeFilters($request->query());

        return view('reports::teaching.overview', [
            'filters' => $filters,
            'filterOptions' => $this->statistics->filterOptions($request->user()),
            'ratingLabels' => TeachingStatisticsService::RATING_LABELS,
            'data' => $this->statistics->overview($filters, $request->user()),
        ]);
    }

    public function teachers(Request $request): View
    {
        $this->authorizeReportAccess($request);

        $filters = $this->statistics->normalizeFilters($request->query());

        return view('reports::teaching.teachers', [
            'filters' => $filters,
            'filterOptions' => $this->statistics->filterOptions($request->user()),
            'ratingLabels' => TeachingStatisticsService::RATING_LABELS,
            'teachers' => $this->statistics->teacherWorkload($filters, $request->user()),
            'groupingNote' => $this->statistics->groupingNote(),
        ]);
    }

    public function quality(Request $request): View
    {
        $this->authorizeReportAccess($request);

        $filters = $this->statistics->normalizeFilters($request->query());

        return view('reports::teaching.quality', [
            'filters' => $filters,
            'filterOptions' => $this->statistics->filterOptions($request->user()),
            'ratingLabels' => TeachingStatisticsService::RATING_LABELS,
            'data' => $this->statistics->quality($filters, $request->user()),
        ]);
    }

    public function classes(Request $request): View
    {
        $this->authorizeReportAccess($request);

        $filters = $this->statistics->normalizeFilters($request->query());

        return view('reports::teaching.classes', [
            'filters' => $filters,
            'filterOptions' => $this->statistics->filterOptions($request->user()),
            'ratingLabels' => TeachingStatisticsService::RATING_LABELS,
            'data' => $this->statistics->classReports($filters, $request->user()),
        ]);
    }

    public function departments(Request $request): View
    {
        $this->authorizeReportAccess($request);

        $filters = $this->statistics->normalizeFilters($request->query());

        return view('reports::teaching.departments', [
            'filters' => $filters,
            'filterOptions' => $this->statistics->filterOptions($request->user()),
            'ratingLabels' => TeachingStatisticsService::RATING_LABELS,
            'data' => $this->statistics->departmentReports($filters, $request->user()),
        ]);
    }

    private function authorizeReportAccess(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user && (
                $user->isAdmin()
                || $user->isTrainingOffice()
                || $user->isLeadership()
                || $user->isDepartmentStaff()
                || $user->isTeacher()
            ),
            403
        );
    }
}
