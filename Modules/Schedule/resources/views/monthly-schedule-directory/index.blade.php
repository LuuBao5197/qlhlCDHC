@extends('layouts.dashboard')

@section('title', 'Phân công giảng dạy theo tháng')

@section('content')
    @php
        $currentMonth = $filters['month'] ?? null;
        $currentYear = $filters['year'] ?? null;
        $monthOptions = range(1, 12);
        $batchStatusLabels = [
            'draft' => 'Chưa gửi / nháp',
            'submitted' => 'Đã gửi - chờ duyệt',
            'approved' => 'Đã duyệt',
            'returned' => 'Bị trả về',
        ];
        $batchStatusClasses = [
            'draft' => 'badge-secondary',
            'submitted' => 'badge-info',
            'approved' => 'badge-success',
            'returned' => 'badge-warning',
        ];
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1 text-primary font-weight-bold">
                                <i class="fas fa-chalkboard-teacher mr-2"></i>Phân công giảng dạy theo tháng
                            </h4>
                            <p class="mb-0 text-muted">
                                Chọn Khoa + Tháng/Năm để vào màn phân công giảng dạy chi tiết cho từng tiết học —
                                bao gồm cả dữ liệu lịch sử trước khi hệ thống vận hành.
                            </p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('monthly-schedule.directory') }}" class="mt-3">
                        <div class="row align-items-end">
                            <div class="col-md-4 mb-2">
                                <label class="mb-1 font-weight-bold">Khoa</label>
                                <select name="department_id" class="form-control" @disabled($isDepartmentStaff)>
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
                                    <option value="">Tất cả</option>
                                    @foreach ($monthOptions as $month)
                                        <option value="{{ $month }}" @selected((int) $currentMonth === (int) $month)>
                                            {{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="mb-1 font-weight-bold">Năm</label>
                                <input type="number" name="year" class="form-control" value="{{ $currentYear }}" min="2000" max="2100" placeholder="Tất cả">
                            </div>
                            <div class="col-md-4 mb-2">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-filter mr-1"></i>Lọc danh sách
                                </button>
                                <a href="{{ route('monthly-schedule.directory') }}" class="btn btn-outline-secondary">
                                    Xóa lọc
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-dark font-weight-bold">
                    <i class="fas fa-list mr-1 text-primary"></i>Danh sách Khoa / Tháng có lịch giảng dạy
                </h5>
                <span class="badge badge-primary px-2 py-1">{{ $rows->total() }} kết quả</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width: 220px;">Khoa</th>
                            <th style="min-width: 110px;">Tháng/Năm</th>
                            <th style="min-width: 90px;">Số tiết</th>
                            <th style="min-width: 160px;">Trạng thái batch</th>
                            <th style="min-width: 160px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $row['department_name'] }}</div>
                                </td>
                                <td class="text-nowrap">
                                    {{ str_pad((string) $row['month'], 2, '0', STR_PAD_LEFT) }}/{{ $row['year'] }}
                                </td>
                                <td>{{ $row['slot_count'] }}</td>
                                <td>
                                    @if ($row['batch_status'])
                                        <span class="badge {{ $batchStatusClasses[$row['batch_status']] ?? 'badge-secondary' }}">
                                            {{ $batchStatusLabels[$row['batch_status']] ?? $row['batch_status'] }}
                                        </span>
                                    @else
                                        <span class="badge badge-light border">Chưa tạo batch</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('monthly-schedule.assignment', ['id' => $row['anchor_monthly_schedule_id'], 'department_id' => $row['department_id']]) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-chalkboard-teacher mr-1"></i>Vào phân công
                                    </a>
                                    @if ($row['batch_id'])
                                        <a href="{{ route('department-monthly-assignment-batches.show', $row['batch_id']) }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-eye mr-1"></i>Xem batch
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Không tìm thấy Khoa/Tháng nào phù hợp với bộ lọc hiện tại.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $rows->links() }}
            </div>
        </div>
    </div>
@endsection
