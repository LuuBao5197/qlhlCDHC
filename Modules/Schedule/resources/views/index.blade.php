@extends('layouts.dashboard')

@section('title', 'Điều hướng phân công giảng dạy')

@section('content')
    @php
        $user = auth()->user();
        $approvalAuthority = app(\App\Services\ApprovalAuthorityService::class);
        $canReviewWorkflow = $approvalAuthority->canApproveAsTrainingOffice($user);
        $canManageSemesterPlan = $user && ($user->isTrainingOffice() || $user->isAdmin());
        $canDepartmentAssign = $user && ($user->isDepartmentStaff() || $user->isAdmin());
        $canLeadershipReview = $approvalAuthority->canApproveAsLeadership($user);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1">Điều hướng phân công giảng dạy</h4>
                    <p class="text-muted mb-0">
                        Khoa đi vào phân công tổng hợp theo tháng; PDT và quản trị viên đi vào hộp chờ phê duyệt batch.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if (!$canReviewWorkflow && !$canManageSemesterPlan && !$canDepartmentAssign)
        <div class="alert alert-warning">
            Tai khoan cua ban chi co quyen xem. Chuc nang nghiep vu chi danh cho vai tro `department_staff`,
            `training_office` hoac `admin`.
        </div>
    @endif

    @php
        $aggregateAssignmentCards = collect($monthlySchedules ?? [])
            ->map(function ($monthlySchedule) {
                $subjectSlots = collect($monthlySchedule->scheduleSlots ?? [])
                    ->filter(fn ($slot) => ($slot->slot_type ?? 'subject') !== 'event' && $slot->subjectModel?->department_id !== null);

                $departmentId = $subjectSlots->first()?->subjectModel?->department_id;
                if ($departmentId === null) {
                    return null;
                }

                $assignedCount = $subjectSlots->filter(fn ($slot) => !empty($slot->teacher_id) || !empty($slot->assignment_type))->count();

                return [
                    'monthly_schedule_id' => $monthlySchedule->id,
                    'year' => (int) $monthlySchedule->year,
                    'month' => (int) $monthlySchedule->month,
                    'department_id' => (int) $departmentId,
                    'department_name' => $subjectSlots->first()?->subjectModel?->department?->name ?? '-',
                    'plan_count' => 1,
                    'slot_count' => $subjectSlots->count(),
                    'assigned_count' => $assignedCount,
                    'unassigned_count' => max(0, $subjectSlots->count() - $assignedCount),
                ];
            })
            ->filter()
            ->groupBy(fn ($item) => $item['year'] . '|' . $item['month'] . '|' . $item['department_id'])
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'monthly_schedule_id' => $first['monthly_schedule_id'],
                    'year' => $first['year'],
                    'month' => $first['month'],
                    'department_name' => $first['department_name'],
                    'plan_count' => $items->count(),
                    'slot_count' => $items->sum('slot_count'),
                    'assigned_count' => $items->sum('assigned_count'),
                    'unassigned_count' => $items->sum('unassigned_count'),
                ];
            })
            ->sortByDesc(fn ($item) => sprintf('%04d-%02d-%s', $item['year'], $item['month'], $item['department_name']))
            ->values();
    @endphp

    <div class="row mb-3">
        <div class="col-lg-6 mb-3">
            <div class="card h-100 border-left-info shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-2">Phân công giảng dạy tổng hợp</h5>
                    <p class="text-muted mb-3">
                        Dành cho khoa để vào màn hình phân công theo từng lịch tháng.
                    </p>
                    @if ($canDepartmentAssign)
                        <div class="d-flex flex-wrap">
                            @foreach ($aggregateAssignmentCards->take(3) as $aggregateCard)
                                <a href="{{ route('monthly-schedule.assignment', $aggregateCard['monthly_schedule_id']) }}"
                                    class="btn btn-outline-info btn-sm mr-2 mb-2">
                                    {{ str_pad((string) $aggregateCard['month'], 2, '0', STR_PAD_LEFT) }}/{{ $aggregateCard['year'] }}
                                    - {{ $aggregateCard['department_name'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <span class="text-muted">Không có quyền truy cập.</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card h-100 border-left-success shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-2">Hộp chờ phê duyệt batch phân công</h5>
                    <p class="text-muted mb-3">
                        Lãnh đạo Khoa duyệt batch của Khoa mình; Phòng Đào tạo duyệt batch đã qua Lãnh đạo Khoa.
                    </p>
                    @if ($canDepartmentAssign || $canReviewWorkflow)
                        <a href="{{ route('department-monthly-assignment-batches.index') }}" class="btn btn-success btn-sm">
                            Đi tới hộp chờ phê duyệt
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card h-100 border-left-warning shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-2">Hồ sơ phân công tháng tổng hợp</h5>
                    <p class="text-muted mb-3">
                        PĐT tổng hợp các batch đã đủ 2 vòng duyệt của tất cả Khoa bắt buộc, gửi Lãnh đạo PĐT rồi Ban Giám hiệu phê duyệt.
                    </p>
                    @if ($canManageSemesterPlan || $canReviewWorkflow || $canLeadershipReview)
                        <a href="{{ route('monthly-assignment-dossiers.index') }}" class="btn btn-warning btn-sm">
                            Đi tới hồ sơ tổng hợp
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('schedule::partials._semester-plan')

    @include('schedule::partials._holiday-calendar-management')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="card-title mb-1">Phiếu đề nghị thay đổi kế hoạch giảng dạy</h5>
                            <small class="text-muted">Danh sách phiếu đề nghị thay đổi kế hoạch giảng dạy.</small>
                        </div>
                        @if ($canDepartmentAssign || $canReviewWorkflow)
                            <div class="mt-2 mt-sm-0">
                                <a href="{{ route('change-request.create') }}" class="btn btn-primary btn-sm">
                                    Tạo phiếu đề nghị thay đổi
                                </a>
                            </div>
                        @endif
                    </div>

                    @include('schedule::partials._change-request-list')
                </div>
            </div>
        </div>
    </div>
@endsection
