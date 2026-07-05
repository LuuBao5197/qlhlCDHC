@extends('layouts.dashboard')

@section('title', 'Inbox hỗ trợ liên khoa')

@section('content')
    @php
        $statusLabels = [
            'pending_pdt' => 'Chờ PDT duyệt',
            'assigned_to_department' => 'Đã giao khoa',
            'department_assigning' => 'Đang phân công',
            'completed' => 'Hoàn tất',
            'returned' => 'Đã từ chối',
            'cancelled' => 'Đã hủy',
        ];
    @endphp

    <div class="card border-left-primary shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <div>
                    <h4 class="card-title mb-1 text-primary font-weight-bold">
                        <i class="fas fa-inbox mr-2"></i>Inbox hỗ trợ liên khoa
                    </h4>
                    <p class="mb-0 text-muted">Chỉ hiển thị các yêu cầu PDT đã giao cho khoa hiện tại.</p>
                </div>
                <div class="mt-2 mt-lg-0">
                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i>Quay lại màn hình trước
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @forelse ($requests as $requestModel)
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <div>
                    <strong>{{ $requestModel->requestingDepartment?->name ?? '-' }}</strong>
                    <span class="mx-2">→</span>
                    <strong>{{ $requestModel->assignedSupportingDepartment?->name ?? '-' }}</strong>
                    <span class="badge badge-warning ml-2">
                        {{ $statusLabels[$requestModel->status ?? ''] ?? 'Đang xử lý' }}
                    </span>
                </div>
                <div class="small text-muted mt-2 mt-lg-0">
                    {{ $requestModel->submitted_at?->format('d/m/Y H:i') ?? '-' }}
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('teaching-support-requests.confirm', $requestModel->id) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Tiết</th>
                                    <th>Lớp</th>
                                    <th>Môn học</th>
                                    <th>Bài học</th>
                                    <th>Phòng</th>
                                    <th>Giảng viên hỗ trợ</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requestModel->items as $item)
                                    @php
                                        $teacherAvailabilityByItem = $teacherAvailabilityMap[$item->id] ?? [];
                                    @endphp
                                    <tr>
                                        <td>{{ $item->scheduleSlot?->date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $item->scheduleSlot?->period_number ?? '-' }}</td>
                                        <td>{{ $item->scheduleSlot?->trainingClass?->code ?? '-' }}</td>
                                        <td>{{ $item->scheduleSlot?->subjectModel?->code ?? '-' }}</td>
                                        <td>
                                            @php
                                                $subjectLesson = $item->scheduleSlot?->subjectLesson;
                                                $lessonLabel = $subjectLesson
                                                    ? 'B' . ($subjectLesson->lesson_no ?? '?') . ': ' . ($subjectLesson->title ?? '-')
                                                    : '-';
                                            @endphp
                                            {{ $lessonLabel }}
                                        </td>
                                        <td>{{ $item->scheduleSlot?->room?->code ?? '-' }}</td>
                                        <td style="min-width: 220px;">
                                            <input type="hidden" name="assignments[{{ $loop->index }}][request_item_id]"
                                                value="{{ $item->id }}">
                                            <select name="assignments[{{ $loop->index }}][teacher_id]"
                                                class="form-control form-control-sm" required>
                                                <option value="">-- Chọn giảng viên --</option>
                                                @foreach ($requestModel->assignedSupportingDepartment?->teachers ?? collect() as $teacher)
                                                    <option value="{{ $teacher->id }}"
                                                        @disabled(isset($teacherAvailabilityByItem[$teacher->id]) && $teacherAvailabilityByItem[$teacher->id] === false)
                                                        @selected((int) ($item->assigned_teacher_id ?? 0) === (int) $teacher->id)>
                                                        {{ $teacher->name }}
                                                        @if (isset($teacherAvailabilityByItem[$teacher->id]) && $teacherAvailabilityByItem[$teacher->id] === false)
                                                            (Đã kín lịch)
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="small text-muted mt-1">
                                                Hệ thống sẽ tự chặn nếu giảng viên bị trùng lịch trên toàn hệ thống, kể cả tiết nội bộ và hỗ trợ liên khoa.
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="assignments[{{ $loop->index }}][note]"
                                                class="form-control form-control-sm" maxlength="500"
                                                value="{{ $item->note ?? '' }}" placeholder="Ghi chú">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex flex-wrap align-items-center">
                        <button type="submit" class="btn btn-success mr-2 mb-2">
                            <i class="fas fa-check mr-1"></i>Xác nhận phân công
                        </button>
                        <a href="{{ route('teaching-support-requests.show', $requestModel->id) }}"
                            class="btn btn-outline-secondary mb-2">
                            Xem chi tiết
                        </a>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-info">
            Không có nhiệm vụ hỗ trợ nào đang chờ khoa hiện tại xử lý.
        </div>
    @endforelse
@endsection
