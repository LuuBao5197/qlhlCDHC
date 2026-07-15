<?php

namespace Modules\Reports\Application\TeachingStatistics;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\ScheduleSlot;

class TeachingStatisticsService
{
    public const RATING_LABELS = [
        'tot' => 'Tốt',
        'kha' => 'Khá',
        'trung_binh' => 'Trung bình',
        'yeu' => 'Yếu',
    ];

    public const LOW_RATING_LEVELS = ['trung_binh', 'yeu'];

    public function normalizeFilters(array $input): array
    {
        $periodType = (string) ($input['period_type'] ?? 'month');
        $today = CarbonImmutable::today();

        $from = null;
        $to = null;

        if ($periodType === 'week') {
            $anchor = $this->parseDate($input['week_start'] ?? null) ?? $today;
            $from = $anchor->startOfWeek();
            $to = $anchor->endOfWeek();
        } elseif ($periodType === 'semester') {
            $year = (int) ($input['semester_year'] ?? $today->year);
            $semester = (string) ($input['semester'] ?? ($today->month <= 6 ? '1' : '2'));
            $from = CarbonImmutable::create($year, $semester === '2' ? 7 : 1, 1)->startOfDay();
            $to = CarbonImmutable::create($year, $semester === '2' ? 12 : 6, 1)->endOfMonth()->endOfDay();
        } elseif ($periodType === 'custom') {
            $from = $this->parseDate($input['date_from'] ?? null) ?? $today->startOfMonth();
            $to = $this->parseDate($input['date_to'] ?? null) ?? $today->endOfMonth();
        } else {
            $periodType = 'month';
            $month = preg_match('/^\d{4}-\d{2}$/', (string) ($input['month'] ?? ''))
                ? (string) $input['month']
                : $today->format('Y-m');
            $from = CarbonImmutable::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
            $to = $from->endOfMonth();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [
            'period_type' => $periodType,
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'month' => (string) ($input['month'] ?? $from->format('Y-m')),
            'week_start' => (string) ($input['week_start'] ?? $from->toDateString()),
            'semester_year' => (int) ($input['semester_year'] ?? $from->year),
            'semester' => (string) ($input['semester'] ?? ($from->month <= 6 ? '1' : '2')),
            'department_id' => $this->nullableInt($input['department_id'] ?? null),
            'teacher_id' => $this->nullableInt($input['teacher_id'] ?? null),
            'class_id' => $this->nullableInt($input['class_id'] ?? null),
            'subject_id' => $this->nullableInt($input['subject_id'] ?? null),
            'subject_lesson_id' => $this->nullableInt($input['subject_lesson_id'] ?? null),
            'rating_level' => $this->validRatingLevel($input['rating_level'] ?? null),
        ];
    }

    public function filterOptions(User $user): array
    {
        $departmentId = $this->forcedDepartmentId($user);
        $teacherId = $this->forcedTeacherId($user);

        $departments = DB::table('departments')
            ->when($departmentId, fn (Builder $query) => $query->where('id', $departmentId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $teachers = DB::table('teachers')
            ->when($departmentId, fn (Builder $query) => $query->where('department_id', $departmentId))
            ->when($teacherId, fn (Builder $query) => $query->where('id', $teacherId))
            ->orderBy('name')
            ->get(['id', 'name', 'teacher_code', 'department_id']);

        $subjects = DB::table('subjects')
            ->when($departmentId, fn (Builder $query) => $query->where('department_id', $departmentId))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'department_id']);

        $lessons = DB::table('subject_lessons as sl')
            ->join('subjects as s', 's.id', '=', 'sl.subject_id')
            ->when($departmentId, fn (Builder $query) => $query->where('s.department_id', $departmentId))
            ->orderBy('s.name')
            ->orderBy('sl.lesson_no')
            ->get([
                'sl.id',
                'sl.subject_id',
                'sl.lesson_no',
                'sl.title',
                's.name as subject_name',
            ]);

        return [
            'departments' => $departments,
            'teachers' => $teachers,
            'classes' => DB::table('classes')->orderBy('name')->get(['id', 'name', 'code']),
            'subjects' => $subjects,
            'subjectLessons' => $lessons,
            'ratingLabels' => self::RATING_LABELS,
        ];
    }

    public function overview(array $filters, User $user): array
    {
        $actualGroups = $this->actualTeachingGroupsQuery($filters, $user);
        $totalActual = $this->countRows($actualGroups);
        $evaluatedActual = $this->countRows(
            DB::query()->fromSub($this->actualTeachingGroupsQuery($filters, $user), 'g')
                ->where('g.has_evaluation', '>', 0)
        );
        $qualityQuery = $this->qualityBaseQuery($filters, $user);
        $attendance = $this->attendanceTotals($qualityQuery);

        return [
            'cards' => [
                'actual_periods' => $totalActual,
                'evaluated_periods' => $evaluatedActual,
                'unevaluated_periods' => max(0, $totalActual - $evaluatedActual),
                'evaluation_rate' => $this->percent($evaluatedActual, $totalActual),
                'quality_turns' => $this->countQualityRows($qualityQuery),
                'absent_count' => $attendance['absent_count'],
                'absence_rate' => $this->percent($attendance['absent_count'], $attendance['attendance_count']),
                'attendance_count' => $attendance['attendance_count'],
            ],
            'ratingDistribution' => $this->ratingDistribution($qualityQuery),
            'topTeachers' => $this->topTeachers($filters, $user),
            'topLowClasses' => $this->topLowClasses($filters, $user),
            'topLowLessons' => $this->topLowLessons($filters, $user),
            'groupingNote' => $this->groupingNote(),
        ];
    }

    public function teacherWorkload(array $filters, User $user, int $perPage = 15): LengthAwarePaginator
    {
        $aggregate = DB::query()
            ->fromSub($this->actualTeachingGroupsQuery($filters, $user), 'g')
            ->join('teachers as t', 't.id', '=', 'g.teacher_id')
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->selectRaw('
                g.teacher_id,
                t.teacher_code,
                t.name as teacher_name,
                d.name as department_name,
                COUNT(*) as actual_periods,
                COUNT(DISTINCT g.teaching_date) as teaching_days,
                SUM(CASE WHEN g.class_slot_count > 1 THEN 1 ELSE 0 END) as merged_periods,
                SUM(CASE WHEN g.class_slot_count <= 1 THEN 1 ELSE 0 END) as single_periods
            ')
            ->groupBy('g.teacher_id', 't.teacher_code', 't.name', 'd.name')
            ->orderByDesc('actual_periods')
            ->orderBy('t.name');

        $paginator = $aggregate->paginate($perPage)->withQueryString();
        $teacherIds = $paginator->getCollection()->pluck('teacher_id')->map(fn ($id) => (int) $id)->all();

        $classCounts = $this->distinctSlotCountByTeacher($filters, $user, $teacherIds, 'ss.class_id');
        $lessonCounts = $this->distinctSlotCountByTeacher(
            $filters,
            $user,
            $teacherIds,
            'COALESCE(ss.subject_lesson_id, ss.subject_id)',
            'ss.subject_id'
        );
        $evaluatedGroups = $this->evaluatedActualGroupsByTeacher($filters, $user, $teacherIds);
        $ratings = $this->ratingDistributionBy('ss.teacher_id', $filters, $user, $teacherIds);

        $paginator->getCollection()->transform(function ($row) use ($classCounts, $lessonCounts, $evaluatedGroups, $ratings) {
            $teacherId = (int) $row->teacher_id;
            $row->class_count = (int) ($classCounts[$teacherId] ?? 0);
            $row->lesson_count = (int) ($lessonCounts[$teacherId] ?? 0);
            $row->evaluated_periods = (int) ($evaluatedGroups[$teacherId] ?? 0);
            $row->evaluation_rate = $this->percent($row->evaluated_periods, (int) $row->actual_periods);
            $row->ratings = $this->emptyRatingBuckets($ratings[$teacherId] ?? []);

            return $row;
        });

        return $paginator;
    }

    public function quality(array $filters, User $user, int $perPage = 15): array
    {
        $query = $this->qualityBaseQuery($filters, $user);
        $total = $this->countQualityRows($query);
        $ratings = $this->ratingDistribution($query);

        $details = $this->qualityDetailsQuery($filters, $user)
            ->when(
                $filters['rating_level'],
                fn (Builder $builder) => $builder->where('se.rating_level', $filters['rating_level']),
                fn (Builder $builder) => $builder->whereIn('se.rating_level', self::LOW_RATING_LEVELS)
            )
            ->orderByDesc('ss.date')
            ->orderBy('ss.period_number')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'cards' => [
                'quality_turns' => $total,
                'tot' => $ratings['tot']['count'],
                'kha' => $ratings['kha']['count'],
                'trung_binh' => $ratings['trung_binh']['count'],
                'yeu' => $ratings['yeu']['count'],
            ],
            'ratingDistribution' => $ratings,
            'details' => $details,
        ];
    }

    public function classReports(array $filters, User $user, int $perPage = 15): array
    {
        $aggregate = $this->scheduleSlotsQuery($filters, $user)
            ->leftJoin('classes as c', 'c.id', '=', 'ss.class_id')
            ->leftJoin('slot_evaluations as se', 'se.schedule_slot_id', '=', 'ss.id')
            ->whereNotNull('ss.class_id')
            ->selectRaw('
                ss.class_id,
                c.code as class_code,
                c.name as class_name,
                COUNT(DISTINCT ss.id) as total_periods,
                COUNT(DISTINCT se.schedule_slot_id) as evaluated_periods
            ')
            ->groupBy('ss.class_id', 'c.code', 'c.name')
            ->orderBy('c.name');

        $paginator = $aggregate->paginate($perPage)->withQueryString();
        $classIds = $paginator->getCollection()->pluck('class_id')->map(fn ($id) => (int) $id)->all();
        $attendance = $this->attendanceTotalsBy('ss.class_id', $filters, $user, $classIds);
        $ratings = $this->ratingDistributionBy('ss.class_id', $filters, $user, $classIds);

        $paginator->getCollection()->transform(function ($row) use ($attendance, $ratings) {
            $classId = (int) $row->class_id;
            $rowAttendance = $attendance[$classId] ?? ['attendance_count' => 0, 'absent_count' => 0];
            $row->attendance_count = (int) $rowAttendance['attendance_count'];
            $row->absent_count = (int) $rowAttendance['absent_count'];
            $row->absence_rate = $this->percent($row->absent_count, $row->attendance_count);
            $row->evaluation_rate = $this->percent((int) $row->evaluated_periods, (int) $row->total_periods);
            $row->ratings = $this->emptyRatingBuckets($ratings[$classId] ?? []);

            return $row;
        });

        $lowDetails = $this->qualityDetailsQuery($filters, $user)
            ->whereIn('se.rating_level', self::LOW_RATING_LEVELS)
            ->orderByDesc('ss.date')
            ->orderBy('c.name')
            ->paginate($perPage, ['*'], 'low_page')
            ->withQueryString();

        return [
            'classes' => $paginator,
            'lowDetails' => $lowDetails,
        ];
    }

    public function departmentReports(array $filters, User $user, int $perPage = 15): array
    {
        $aggregate = DB::query()
            ->fromSub($this->actualTeachingGroupsQuery($filters, $user), 'g')
            ->join('departments as d', 'd.id', '=', 'g.teacher_department_id')
            ->selectRaw('
                g.teacher_department_id as department_id,
                d.name as department_name,
                COUNT(*) as actual_periods,
                COUNT(DISTINCT g.teacher_id) as teacher_count
            ')
            ->whereNotNull('g.teacher_department_id')
            ->groupBy('g.teacher_department_id', 'd.name')
            ->orderBy('d.name');

        $paginator = $aggregate->paginate($perPage)->withQueryString();
        $departmentIds = $paginator->getCollection()->pluck('department_id')->map(fn ($id) => (int) $id)->all();
        $classCounts = $this->classCountsByTeacherDepartment($filters, $user, $departmentIds);
        $qualityCounts = $this->qualityCountsByTeacherDepartment($filters, $user, $departmentIds);
        $attendance = $this->attendanceTotalsByTeacherDepartment($filters, $user, $departmentIds);

        $paginator->getCollection()->transform(function ($row) use ($classCounts, $qualityCounts, $attendance) {
            $departmentId = (int) $row->department_id;
            $rowAttendance = $attendance[$departmentId] ?? ['attendance_count' => 0, 'absent_count' => 0];
            $row->class_count = (int) ($classCounts[$departmentId] ?? 0);
            $row->quality_turns = (int) ($qualityCounts[$departmentId]['total'] ?? 0);
            $row->ratings = $this->emptyRatingBuckets($qualityCounts[$departmentId]['ratings'] ?? []);
            $row->attendance_count = (int) $rowAttendance['attendance_count'];
            $row->absent_count = (int) $rowAttendance['absent_count'];
            $row->absence_rate = $this->percent($row->absent_count, $row->attendance_count);

            return $row;
        });

        return [
            'departments' => $paginator,
            'topLowTeachers' => $this->topLowTeachersByDepartment($filters, $user),
            'topLowClasses' => $this->topLowClassesByDepartment($filters, $user),
        ];
    }

    public function groupingNote(): string
    {
        return 'Số tiết dạy thực tế ưu tiên group theo schedule_slot_group_id. Slot chưa có group_id được fallback theo teacher_id + date + period_number/period + subject_lesson_id/subject_id + room_id, có rủi ro gom nhầm nếu hai tiết độc lập trùng toàn bộ khóa này.';
    }

    private function scheduleSlotsQuery(array $filters, User $user): Builder
    {
        $query = DB::table('schedule_slots as ss')
            ->leftJoin('teachers as t_scope', 't_scope.id', '=', 'ss.teacher_id')
            ->leftJoin('subjects as s_scope', 's_scope.id', '=', 'ss.subject_id')
            ->whereNotNull('ss.teacher_id')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('ss.assignment_type')
                    ->orWhere('ss.assignment_type', '!=', ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY);
            });

        $this->applyCommonFilters($query, $filters);
        $this->applyAccessScope($query, $filters, $user);

        return $query;
    }

    private function qualityBaseQuery(array $filters, User $user): Builder
    {
        $query = DB::table('slot_evaluations as se')
            ->join('schedule_slots as ss', 'ss.id', '=', 'se.schedule_slot_id')
            ->leftJoin('teachers as t_scope', 't_scope.id', '=', 'ss.teacher_id')
            ->leftJoin('subjects as s_scope', 's_scope.id', '=', 'ss.subject_id')
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('ss.assignment_type')
                    ->orWhere('ss.assignment_type', '!=', ScheduleSlot::ASSIGNMENT_TYPE_SELF_STUDY);
            });

        $this->applyCommonFilters($query, $filters);
        $this->applyAccessScope($query, $filters, $user);

        if ($filters['rating_level']) {
            $query->where('se.rating_level', $filters['rating_level']);
        }

        return $query;
    }

    private function qualityDetailsQuery(array $filters, User $user): Builder
    {
        return $this->qualityBaseQuery($filters, $user)
            ->leftJoin('teachers as t', 't.id', '=', 'ss.teacher_id')
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->leftJoin('classes as c', 'c.id', '=', 'ss.class_id')
            ->leftJoin('subjects as s', 's.id', '=', 'ss.subject_id')
            ->leftJoin('subject_lessons as sl', 'sl.id', '=', 'ss.subject_lesson_id')
            ->leftJoin('rooms as r', 'r.id', '=', 'ss.room_id')
            ->select([
                'se.id',
                'se.rating_level',
                'se.attendance_count',
                'se.absent_count',
                'se.comment',
                'ss.date',
                'ss.period',
                'ss.period_number',
                't.name as teacher_name',
                'd.name as department_name',
                'c.name as class_name',
                'c.code as class_code',
                's.name as subject_name',
                'sl.title as lesson_title',
                'r.name as room_name',
            ]);
    }

    private function actualTeachingGroupsQuery(array $filters, User $user): Builder
    {
        $slots = $this->scheduleSlotsQuery($filters, $user)
            ->leftJoin('slot_evaluations as se_group', 'se_group.schedule_slot_id', '=', 'ss.id')
            ->selectRaw('
                ss.id,
                ss.teacher_id,
                t_scope.department_id as teacher_department_id,
                ss.schedule_slot_group_id,
                DATE(ss.date) as teaching_date,
                COALESCE(ss.period_number, 0) as period_bucket,
                COALESCE(ss.period, \'\') as period_text,
                COALESCE(ss.subject_id, 0) as subject_id,
                COALESCE(ss.subject_lesson_id, 0) as subject_lesson_id,
                COALESCE(ss.room_id, 0) as room_id,
                CASE WHEN se_group.id IS NULL THEN 0 ELSE 1 END as evaluation_flag
            ');

        return DB::query()
            ->fromSub($slots, 'slot_units')
            ->selectRaw('
                MIN(id) as representative_slot_id,
                teacher_id,
                teacher_department_id,
                schedule_slot_group_id,
                teaching_date,
                period_bucket,
                period_text,
                subject_id,
                subject_lesson_id,
                room_id,
                COUNT(DISTINCT id) as class_slot_count,
                MAX(evaluation_flag) as has_evaluation
            ')
            ->groupBy(
                'teacher_id',
                'teacher_department_id',
                'schedule_slot_group_id',
                'teaching_date',
                'period_bucket',
                'period_text',
                'subject_id',
                'subject_lesson_id',
                'room_id'
            );
    }

    private function applyCommonFilters(Builder $query, array $filters): void
    {
        $query->whereDate('ss.date', '>=', $filters['date_from'])
            ->whereDate('ss.date', '<=', $filters['date_to']);

        if ($filters['teacher_id']) {
            $query->where('ss.teacher_id', $filters['teacher_id']);
        }

        if ($filters['class_id']) {
            $query->where('ss.class_id', $filters['class_id']);
        }

        if ($filters['subject_id']) {
            $query->where('ss.subject_id', $filters['subject_id']);
        }

        if ($filters['subject_lesson_id']) {
            $query->where('ss.subject_lesson_id', $filters['subject_lesson_id']);
        }
    }

    private function applyAccessScope(Builder $query, array $filters, User $user): void
    {
        $teacherId = $this->forcedTeacherId($user);
        if ($teacherId) {
            $query->where('ss.teacher_id', $teacherId);

            return;
        }

        $departmentId = $this->forcedDepartmentId($user) ?: $filters['department_id'];
        if ($departmentId) {
            $query->where(function (Builder $builder) use ($departmentId): void {
                $builder
                    ->where('t_scope.department_id', $departmentId)
                    ->orWhere('s_scope.department_id', $departmentId);
            });
        }
    }

    private function countRows(Builder $query): int
    {
        return (int) DB::query()->fromSub($query, 'countable_rows')->count();
    }

    private function countQualityRows(Builder $query): int
    {
        return (int) (clone $query)->count('se.id');
    }

    private function topTeachers(array $filters, User $user): Collection
    {
        return DB::query()
            ->fromSub($this->actualTeachingGroupsQuery($filters, $user), 'g')
            ->join('teachers as t', 't.id', '=', 'g.teacher_id')
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->selectRaw('t.id, t.teacher_code, t.name, d.name as department_name, COUNT(*) as actual_periods')
            ->groupBy('t.id', 't.teacher_code', 't.name', 'd.name')
            ->orderByDesc('actual_periods')
            ->limit(10)
            ->get();
    }

    private function topLowClasses(array $filters, User $user): Collection
    {
        return $this->qualityBaseQuery($filters, $user)
            ->join('classes as c', 'c.id', '=', 'ss.class_id')
            ->whereIn('se.rating_level', self::LOW_RATING_LEVELS)
            ->selectRaw('c.id, c.code, c.name, COUNT(*) as low_count')
            ->groupBy('c.id', 'c.code', 'c.name')
            ->orderByDesc('low_count')
            ->limit(10)
            ->get();
    }

    private function topLowLessons(array $filters, User $user): Collection
    {
        return $this->qualityBaseQuery($filters, $user)
            ->leftJoin('subjects as s', 's.id', '=', 'ss.subject_id')
            ->leftJoin('subject_lessons as sl', 'sl.id', '=', 'ss.subject_lesson_id')
            ->whereIn('se.rating_level', self::LOW_RATING_LEVELS)
            ->selectRaw('
                COALESCE(sl.id, 0) as lesson_id,
                s.name as subject_name,
                sl.title as lesson_title,
                COUNT(*) as low_count
            ')
            ->groupBy('lesson_id', 's.name', 'sl.title')
            ->orderByDesc('low_count')
            ->limit(10)
            ->get();
    }

    private function ratingDistribution(Builder $qualityQuery): array
    {
        $rows = (clone $qualityQuery)
            ->selectRaw('se.rating_level, COUNT(*) as total')
            ->groupBy('se.rating_level')
            ->pluck('total', 'se.rating_level')
            ->all();

        return $this->emptyRatingBuckets($rows);
    }

    private function emptyRatingBuckets(array $counts): array
    {
        $total = array_sum(array_map('intval', $counts));
        $buckets = [];

        foreach (self::RATING_LABELS as $level => $label) {
            $count = (int) ($counts[$level] ?? 0);
            $buckets[$level] = [
                'label' => $label,
                'count' => $count,
                'rate' => $this->percent($count, $total),
            ];
        }

        return $buckets;
    }

    private function attendanceTotals(Builder $qualityQuery): array
    {
        $row = (clone $qualityQuery)
            ->selectRaw('
                COALESCE(SUM(se.attendance_count), 0) as attendance_count,
                COALESCE(SUM(se.absent_count), 0) as absent_count
            ')
            ->first();

        return [
            'attendance_count' => (int) ($row->attendance_count ?? 0),
            'absent_count' => (int) ($row->absent_count ?? 0),
        ];
    }

    private function distinctSlotCountByTeacher(
        array $filters,
        User $user,
        array $teacherIds,
        string $distinctExpression,
        ?string $notNullColumn = null
    ): array
    {
        if ($teacherIds === []) {
            return [];
        }

        return $this->scheduleSlotsQuery($filters, $user)
            ->whereIn('ss.teacher_id', $teacherIds)
            ->whereNotNull($notNullColumn ?: $distinctExpression)
            ->select('ss.teacher_id')
            ->selectRaw('COUNT(DISTINCT ' . $distinctExpression . ') as total')
            ->groupBy('ss.teacher_id')
            ->pluck('total', 'ss.teacher_id')
            ->all();
    }

    private function evaluatedActualGroupsByTeacher(array $filters, User $user, array $teacherIds): array
    {
        if ($teacherIds === []) {
            return [];
        }

        return DB::query()
            ->fromSub($this->actualTeachingGroupsQuery($filters, $user), 'g')
            ->whereIn('g.teacher_id', $teacherIds)
            ->selectRaw('g.teacher_id, SUM(CASE WHEN g.has_evaluation > 0 THEN 1 ELSE 0 END) as total')
            ->groupBy('g.teacher_id')
            ->pluck('total', 'g.teacher_id')
            ->all();
    }

    private function ratingDistributionBy(string $entityColumn, array $filters, User $user, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $rows = $this->qualityBaseQuery($filters, $user)
            ->whereIn($entityColumn, $entityIds)
            ->selectRaw($entityColumn . ' as entity_id, se.rating_level, COUNT(*) as total')
            ->groupBy('entity_id', 'se.rating_level')
            ->get();

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(int) $row->entity_id][$row->rating_level] = (int) $row->total;
        }

        return $mapped;
    }

    private function attendanceTotalsBy(string $entityColumn, array $filters, User $user, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $rows = $this->qualityBaseQuery($filters, $user)
            ->whereIn($entityColumn, $entityIds)
            ->selectRaw($entityColumn . ' as entity_id, COALESCE(SUM(se.attendance_count), 0) as attendance_count, COALESCE(SUM(se.absent_count), 0) as absent_count')
            ->groupBy('entity_id')
            ->get();

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(int) $row->entity_id] = [
                'attendance_count' => (int) $row->attendance_count,
                'absent_count' => (int) $row->absent_count,
            ];
        }

        return $mapped;
    }

    private function classCountsByTeacherDepartment(array $filters, User $user, array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        return $this->scheduleSlotsQuery($filters, $user)
            ->whereIn('t_scope.department_id', $departmentIds)
            ->whereNotNull('ss.class_id')
            ->selectRaw('t_scope.department_id, COUNT(DISTINCT ss.class_id) as total')
            ->groupBy('t_scope.department_id')
            ->pluck('total', 't_scope.department_id')
            ->all();
    }

    private function qualityCountsByTeacherDepartment(array $filters, User $user, array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        $rows = $this->qualityBaseQuery($filters, $user)
            ->whereIn('t_scope.department_id', $departmentIds)
            ->selectRaw('t_scope.department_id as department_id, se.rating_level, COUNT(*) as total')
            ->groupBy('t_scope.department_id', 'se.rating_level')
            ->get();

        $mapped = [];
        foreach ($rows as $row) {
            $departmentId = (int) $row->department_id;
            $mapped[$departmentId]['ratings'][$row->rating_level] = (int) $row->total;
            $mapped[$departmentId]['total'] = (int) (($mapped[$departmentId]['total'] ?? 0) + $row->total);
        }

        return $mapped;
    }

    private function attendanceTotalsByTeacherDepartment(array $filters, User $user, array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        $rows = $this->qualityBaseQuery($filters, $user)
            ->whereIn('t_scope.department_id', $departmentIds)
            ->selectRaw('t_scope.department_id as department_id, COALESCE(SUM(se.attendance_count), 0) as attendance_count, COALESCE(SUM(se.absent_count), 0) as absent_count')
            ->groupBy('t_scope.department_id')
            ->get();

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[(int) $row->department_id] = [
                'attendance_count' => (int) $row->attendance_count,
                'absent_count' => (int) $row->absent_count,
            ];
        }

        return $mapped;
    }

    private function topLowTeachersByDepartment(array $filters, User $user): Collection
    {
        return $this->qualityBaseQuery($filters, $user)
            ->join('teachers as t', 't.id', '=', 'ss.teacher_id')
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->whereIn('se.rating_level', self::LOW_RATING_LEVELS)
            ->selectRaw('d.name as department_name, t.name as teacher_name, COUNT(*) as low_count')
            ->groupBy('d.name', 't.name')
            ->orderByDesc('low_count')
            ->limit(10)
            ->get();
    }

    private function topLowClassesByDepartment(array $filters, User $user): Collection
    {
        return $this->qualityBaseQuery($filters, $user)
            ->join('classes as c', 'c.id', '=', 'ss.class_id')
            ->leftJoin('teachers as t', 't.id', '=', 'ss.teacher_id')
            ->leftJoin('departments as d', 'd.id', '=', 't.department_id')
            ->whereIn('se.rating_level', self::LOW_RATING_LEVELS)
            ->selectRaw('d.name as department_name, c.name as class_name, COUNT(*) as low_count')
            ->groupBy('d.name', 'c.name')
            ->orderByDesc('low_count')
            ->limit(10)
            ->get();
    }

    private function forcedDepartmentId(User $user): ?int
    {
        return $user->isDepartmentStaff() && $user->department_id ? (int) $user->department_id : null;
    }

    private function forcedTeacherId(User $user): ?int
    {
        if (! $user->isTeacher()) {
            return null;
        }

        return $user->teacher()->value('id');
    }

    private function nullableInt(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    private function validRatingLevel(mixed $value): ?string
    {
        $value = is_string($value) ? $value : null;

        return $value && array_key_exists($value, self::RATING_LABELS) ? $value : null;
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function percent(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 2);
    }
}
