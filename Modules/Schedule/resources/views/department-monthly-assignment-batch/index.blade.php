@extends('layouts.dashboard')

@section('title', 'Hộp chờ phê duyệt phân công')

@section('content')
    @php
        $currentStatus = $filters['status'] ?? 'submitted';
        $baseQuery = request()->except(['status', 'page']);
        $currentMonth = $filters['month'] ?? (int) now()->month;
        $currentYear = $filters['year'] ?? (int) now()->year;
        $monthOptions = range(1, 12);
        $statusClasses = [
            'submitted' => 'badge-info',
            'approved' => 'badge-success',
            'returned' => 'badge-warning',
            'draft' => 'badge-secondary',
        ];
        $statusLabels = [
            'submitted' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'returned' => 'Trả về chỉnh sửa',
            'draft' => 'Nháp',
            'all' => 'Tất cả',
        ];
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1 text-primary font-weight-bold">
                                <i class="fas fa-inbox mr-2"></i>Hộp chờ phê duyệt phân công giảng dạy
                            </h4>
                            <p class="mb-0 text-muted">
                                Dành cho Phòng Đào tạo và quản trị viên để rà soát các batch phân công tổng hợp theo khoa.
                            </p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="badge badge-info px-3 py-2">Mặc định: Chờ duyệt</span>
                        </div>
                    </div>

                    <div class="btn-group btn-group-sm flex-wrap mt-3 mb-3" role="group" aria-label="Bộ lọc trạng thái batch">
                        @foreach ($statusTabs as $statusKey => $statusLabel)
                            <a href="{{ route('department-monthly-assignment-batches.index', array_merge($baseQuery, ['status' => $statusKey])) }}"
                                class="btn {{ $currentStatus === $statusKey ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ $statusLabel }}
                            </a>
                        @endforeach
                    </div>

                    <form method="GET" action="{{ route('department-monthly-assignment-batches.index') }}">
                        <input type="hidden" name="status" value="{{ $currentStatus }}">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-2">
                                <label class="mb-1 font-weight-bold">Khoa</label>
                                <select name="department_id" class="form-control">
                                    <option value="">Tất cả khoa</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((int) ($filters['department_id'] ?? 0) === (int) $department->id)>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1 font-weight-bold">Tháng</label>
                                <select name="month" class="form-control">
                                    @foreach ($monthOptions as $month)
                                        <option value="{{ $month }}" @selected((int) $currentMonth === (int) $month)>
                                            {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1 font-weight-bold">Năm</label>
                                <input type="number" name="year" class="form-control" value="{{ $currentYear }}" min="2020" max="2100">
                            </div>
                            <div class="col-md-4 mb-2">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-filter mr-1"></i>Lọc danh sách
                                </button>
                                <a href="{{ route('department-monthly-assignment-batches.index', ['status' => $currentStatus]) }}"
                                    class="btn btn-outline-secondary">
                                    Xóa lọc
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Có lỗi:</strong>
            <ul class="mb-0 pl-3 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-dark font-weight-bold">
                    <i class="fas fa-list mr-1 text-primary"></i>Danh sách batch chờ duyệt
                </h5>
                <span class="badge badge-primary px-2 py-1">{{ $batches->total() }} kết quả</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width: 220px;">Khoa</th>
                            <th style="min-width: 110px;">Tháng/Năm</th>
                            <th style="min-width: 130px;">Trạng thái</th>
                            <th style="min-width: 160px;">Người gửi</th>
                            <th style="min-width: 150px;">Thời gian gửi</th>
                            <th style="min-width: 160px;">Người xử lý</th>
                            <th style="min-width: 140px;">Thời gian xử lý</th>
                            <th style="min-width: 90px;">Số slot</th>
                            <th style="min-width: 170px;">Nguồn</th>
                            <th style="min-width: 160px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            @php
                                $batchStatus = $batch['status'] ?? 'draft';
                                $batchStatusClass = $statusClasses[$batchStatus] ?? 'badge-secondary';
                                $batchCanReview = $batchStatus === 'submitted';
                                $batchActionLabel = $batchCanReview ? 'Xem và phê duyệt' : 'Xem chi tiết';
                                $batchActionClass = $batchCanReview ? 'btn-outline-primary' : 'btn-outline-secondary';
                            @endphp
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $batch['department_name'] ?? '-' }}</div>
                                    <div class="small text-muted">#{{ $batch['department_id'] ?? '-' }}</div>
                                </td>
                                <td class="text-nowrap">{{ str_pad((string) ($batch['month'] ?? '-'), 2, '0', STR_PAD_LEFT) }}/{{ $batch['year'] ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $batchStatusClass }}">{{ $statusLabels[$batchStatus] ?? strtoupper($batchStatus) }}</span>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $batch['submitted_by_name'] ?? '-' }}</div>
                                    <div class="small text-muted">
                                        {{ $batch['submitted_by'] ? 'ID: ' . $batch['submitted_by'] : '' }}
                                    </div>
                                </td>
                                <td class="text-nowrap">{{ $batch['submitted_at'] ?? '-' }}</td>
                                <td>
                                    <div class="font-weight-bold">{{ $batch['reviewed_by_name'] ?? '-' }}</div>
                                    <div class="small text-muted">
                                        {{ $batch['reviewed_by'] ? 'ID: ' . $batch['reviewed_by'] : '' }}
                                    </div>
                                </td>
                                <td class="text-nowrap">{{ $batch['processing_time'] ?? '-' }}</td>
                                <td>
                                    <div class="font-weight-bold">{{ $batch['slot_count'] ?? 0 }}</div>
                                    <div class="small text-muted">
                                        {{ $batch['assigned_slot_count'] ?? 0 }} đã phân công
                                    </div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">
                                        {{ $batch['source_plan_count'] ?? 0 }} plan
                                    </div>
                                    <div class="small text-muted">
                                        {{ $batch['source_monthly_schedule_count'] ?? 0 }} lịch tháng
                                        <span class="mx-1">|</span>
                                        {{ $batch['active_merge_group_count'] ?? 0 }} nhóm ghép
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('department-monthly-assignment-batches.show', $batch['id']) }}"
                                        class="btn {{ $batchActionClass }} btn-sm">
                                        <i class="fas fa-eye mr-1"></i>{{ $batchActionLabel }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Không tìm thấy batch nào phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $batches->links() }}
            </div>
        </div>
    </div>
@endsection
