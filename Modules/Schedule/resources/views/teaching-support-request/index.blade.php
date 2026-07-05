@extends('layouts.dashboard')

@section('title', 'Điều phối hỗ trợ liên khoa')

@section('content')
    @php
        $statusClasses = [
            'pending_pdt' => 'badge-info',
            'assigned_to_department' => 'badge-primary',
            'department_assigning' => 'badge-warning',
            'completed' => 'badge-success',
            'returned' => 'badge-secondary',
            'cancelled' => 'badge-dark',
        ];
        $statusLabels = [
            'pending_pdt' => 'Chờ PDT duyệt',
            'assigned_to_department' => 'Đã duyệt',
            'department_assigning' => 'Khoa đang phân công',
            'completed' => 'Hoàn tất',
            'returned' => 'Đã từ chối',
            'cancelled' => 'Đã hủy',
            'all' => 'Tất cả',
        ];
        $selectedDepartmentId = (int) ($filters['department_id'] ?? 0);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1 text-warning font-weight-bold">
                                <i class="fas fa-hands-helping mr-2"></i>Điều phối hỗ trợ liên khoa
                            </h4>
                            <p class="mb-0 text-muted">Danh sách yêu cầu từ các khoa để PDT duyệt hoặc từ chối.</p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i>Quay lại màn hình trước
                            </a>
                        </div>
                    </div>

                    <div class="btn-group btn-group-sm flex-wrap mt-3" role="group">
                        @foreach ($statusTabs as $statusKey => $statusLabel)
                            <a href="{{ route('teaching-support-requests.index', array_merge(request()->except(['status', 'page']), ['status' => $statusKey])) }}"
                                class="btn {{ ($filters['status'] ?? 'pending_pdt') === $statusKey ? 'btn-warning' : 'btn-outline-warning' }}">
                                {{ $statusLabel }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('teaching-support-requests.index') }}" class="row mt-3 align-items-end">
                        <input type="hidden" name="status" value="{{ $filters['status'] ?? 'pending_pdt' }}">
                        <div class="col-lg-5 col-md-6 mb-2 mb-lg-0">
                            <label class="small text-muted font-weight-bold mb-1">Lọc theo khoa</label>
                            <select name="department_id" class="form-control form-control-sm">
                                <option value="">Tất cả khoa</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected($selectedDepartmentId === (int) $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto mb-2 mb-lg-0 d-flex flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm mr-2">
                                Lọc
                            </button>
                            <a href="{{ route('teaching-support-requests.index', ['status' => $filters['status'] ?? 'pending_pdt']) }}"
                                class="btn btn-outline-secondary btn-sm">
                                Bỏ lọc khoa
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Có lỗi:</strong>
            <ul class="mb-0 pl-3 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Khoa đề nghị</th>
                            <th>Khoa hỗ trợ</th>
                            <th>Bài học</th>
                            <th>Trạng thái</th>
                            <th>Số slot</th>
                            <th>Người gửi</th>
                            <th>Thời gian</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $requestModel)
                            @php
                                $requestStatus = $requestModel->status ?? 'pending_pdt';
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $requestModel->requestingDepartment?->name ?? '-' }}</div>
                                    <div class="small text-muted">#{{ $requestModel->requesting_department_id }}</div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $requestModel->assignedSupportingDepartment?->name ?? $requestModel->proposedSupportingDepartment?->name ?? '-' }}</div>
                                    <div class="small text-muted">{{ $requestModel->proposedSupportingDepartment?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    @php
                                        $firstItem = $requestModel->items->first();
                                        $subjectLesson = $firstItem?->scheduleSlot?->subjectLesson;
                                        $lessonLabel = $subjectLesson
                                            ? 'B' . ($subjectLesson->lesson_no ?? '?') . ': ' . ($subjectLesson->title ?? '-')
                                            : '-';
                                    @endphp
                                    {{ $lessonLabel }}
                                </td>
                                <td>
                                    <span class="badge {{ $statusClasses[$requestStatus] ?? 'badge-secondary' }}">
                                        {{ $statusLabels[$requestStatus] ?? ucfirst(str_replace('_', ' ', $requestStatus)) }}
                                    </span>
                                </td>
                                <td>{{ $requestModel->items_count ?? $requestModel->items->count() }}</td>
                                <td>
                                    <div class="font-weight-bold">{{ $requestModel->submittedBy?->name ?? '-' }}</div>
                                    <div class="small text-muted">{{ $requestModel->submitted_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </td>
                                <td class="text-nowrap">
                                    {{ $requestModel->pdt_processed_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                                <td>
                                    @if ($requestStatus === 'pending_pdt')
                                        <a href="{{ route('teaching-support-requests.show', $requestModel->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-check mr-1"></i>Xem và phê duyệt
                                        </a>
                                    @else
                                        <a href="{{ route('teaching-support-requests.show', $requestModel->id) }}"
                                            class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-eye mr-1"></i>Xem chi tiết
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Không có yêu cầu hỗ trợ nào phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
@endsection
