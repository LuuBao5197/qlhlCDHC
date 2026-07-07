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
        @php
            $activeMergeGroupCounts = $requestModel->items
                ->filter(fn ($item) => ($item->scheduleSlot?->scheduleSlotGroup?->status ?? '') === 'active')
                ->groupBy(fn ($item) => (string) ($item->scheduleSlot?->schedule_slot_group_id ?? 0))
                ->map(fn ($group) => $group->count());
        @endphp
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
                    <div class="small text-muted mb-2">
                        Tiết ghép sẽ tự đồng bộ cùng giảng viên trong một nhóm.
                    </div>
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
                                        $mergeGroupId = $item->scheduleSlot?->schedule_slot_group_id ?? null;
                                        $mergeGroupStatus = $item->scheduleSlot?->scheduleSlotGroup?->status ?? '';
                                        $isMerged = !empty($mergeGroupId) && $mergeGroupStatus === 'active';
                                        $mergeGroupCount = (int) ($activeMergeGroupCounts[(string) $mergeGroupId] ?? 0);
                                        $mergeGroupLabel = 'Tiết ghép' . ($mergeGroupCount > 1 ? ' - ' . $mergeGroupCount . ' lớp' : '');
                                    @endphp
                                    <tr data-merge-group-id="{{ $mergeGroupId ?? '' }}"
                                        data-merge-group-status="{{ $mergeGroupStatus }}">
                                        <td>{{ $item->scheduleSlot?->date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ $item->scheduleSlot?->period_number ?? '-' }}</td>
                                        <td>
                                            <div class="font-weight-bold">{{ $item->scheduleSlot?->trainingClass?->code ?? '-' }}</div>
                                            @if ($isMerged)
                                                <span class="badge badge-primary merge-group-pill mt-1">
                                                    {{ $mergeGroupLabel }}
                                                </span>
                                            @endif
                                        </td>
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
                                                data-support-teacher-select="1"
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

    <style>
        .merge-group-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            line-height: 1.1;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function syncMergedTeacherSelects(select) {
                if (!select) {
                    return;
                }

                var row = select.closest('tr');
                if (!row) {
                    return;
                }

                if (row.getAttribute('data-merge-group-status') !== 'active') {
                    return;
                }

                var groupId = row.getAttribute('data-merge-group-id');
                if (!groupId) {
                    return;
                }

                var form = select.closest('form');
                if (!form) {
                    return;
                }

                var value = select.value;
                form.querySelectorAll('tr[data-merge-group-id="' + groupId + '"][data-merge-group-status="active"] select[data-support-teacher-select="1"]')
                    .forEach(function(otherSelect) {
                        if (otherSelect !== select) {
                            otherSelect.value = value;
                        }
                    });
            }

            function syncInitialMergedTeacherSelects() {
                document.querySelectorAll('form[action*="teaching-support-requests/confirm"]').forEach(function(form) {
                    var grouped = {};
                    form.querySelectorAll('tr[data-merge-group-id][data-merge-group-status="active"] select[data-support-teacher-select="1"]')
                        .forEach(function(select) {
                            var row = select.closest('tr');
                            if (!row) {
                                return;
                            }

                            var groupId = row.getAttribute('data-merge-group-id');
                            if (!groupId) {
                                return;
                            }

                            if (!grouped[groupId]) {
                                grouped[groupId] = [];
                            }

                            grouped[groupId].push(select);
                        });

                    Object.keys(grouped).forEach(function(groupId) {
                        var selects = grouped[groupId];
                        if (!selects.length) {
                            return;
                        }

                        var selectedValue = selects.find(function(select) {
                            return select.value !== '';
                        })?.value || selects[0].value || '';

                        selects.forEach(function(select) {
                            select.value = selectedValue;
                        });
                    });
                });
            }

            document.addEventListener('change', function(event) {
                var select = event.target.closest('select[data-support-teacher-select="1"]');
                if (select) {
                    syncMergedTeacherSelects(select);
                }
            });

            syncInitialMergedTeacherSelects();
        });
    </script>
@endsection
