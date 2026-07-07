@extends('layouts.dashboard')

@section('title', 'Duyệt phân công tổng hợp')

@section('content')
    @php
        $slots = collect($batchData['slots'] ?? []);
        $sourcePlans = $slots->groupBy('monthly_schedule_plan_id')->map(function ($group) {
            $first = $group->first();

            return [
                'plan_id' => $first['monthly_schedule_plan_id'] ?? null,
                'plan_name' => $first['monthly_schedule_plan_name'] ?? null,
            ];
        })->values();
        $sourceMonthlySchedules = $slots->groupBy('monthly_schedule_id')->map(function ($group) {
            $first = $group->first();

            return [
                'monthly_schedule_id' => $first['monthly_schedule_id'] ?? null,
                'plan_name' => $first['monthly_schedule_plan_name'] ?? null,
            ];
        })->values();
        $assignedSlotCount = $slots->filter(fn ($slot) => ! empty($slot['teacher_id']) || ! empty($slot['assignment_type']))->count();
        $unassignedSlotCount = max(($batchData['slot_count'] ?? 0) - $assignedSlotCount, 0);
        $activeMergeGroupCount = $slots
            ->filter(fn ($slot) => ! empty($slot['merge_group_id']) && ($slot['merge_group_status'] ?? '') === 'active')
            ->pluck('merge_group_id')
            ->unique()
            ->count();
        $status = $batchData['status'] ?? $batch->status ?? 'draft';
        $statusLabels = [
            'draft' => 'Nháp',
            'submitted' => 'Đã gửi PDT duyệt',
            'approved' => 'Đã phê duyệt',
            'returned' => 'Đã trả về',
        ];
        $statusClasses = [
            'draft' => 'badge-secondary',
            'submitted' => 'badge-info',
            'approved' => 'badge-success',
            'returned' => 'badge-warning',
        ];
        $statusLabel = $statusLabels[$status] ?? strtoupper($status);
        $statusClass = $statusClasses[$status] ?? 'badge-secondary';
        $canReviewBatch = ($canReview ?? false) && $status === 'submitted';
        $firstSlot = $slots->first();
        $anchorMonthlyScheduleId = $anchorMonthlyScheduleId ?? ($firstSlot['monthly_schedule_id'] ?? null);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1 text-primary font-weight-bold">
                                <i class="fas fa-clipboard-check mr-2"></i>Duyệt phân công tổng hợp
                            </h4>
                            <p class="mb-0 text-muted">
                                Khoa: <strong class="text-dark">{{ $batchData['department_name'] ?? '-' }}</strong>
                                <span class="mx-2">|</span>
                                Tháng/Năm: <strong class="text-dark">{{ $batchData['month'] ?? '-' }}/{{ $batchData['year'] ?? '-' }}</strong>
                                <span class="mx-2">|</span>
                                Trạng thái:
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            @if ($anchorMonthlyScheduleId)
                                <a href="{{ route('monthly-schedule.assignment', $anchorMonthlyScheduleId) }}"
                                    class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                                    <i class="fas fa-arrow-left mr-1"></i>Quay lại màn phân công
                                </a>
                            @endif
                            @if ($canReviewBatch)
                                <span class="badge badge-info px-3 py-2">Sẵn sàng duyệt</span>
                            @endif
                        </div>
                    </div>

                    @if ($status !== 'submitted')
                        <div class="alert alert-warning mt-3 mb-0">
                            Batch này đang ở trạng thái <strong>{{ $statusLabel }}</strong>, nên chưa có nút phê duyệt.
                            Chỉ batch ở trạng thái <strong>Chờ duyệt</strong> mới có thể duyệt hoặc trả về.
                        </div>
                    @endif

                    <div class="row mt-3">
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Người gửi</div>
                                <div class="font-weight-bold">{{ $batchData['submitted_by_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $batchData['submitted_at'] ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Người xử lý PDT</div>
                                <div class="font-weight-bold">{{ $batchData['reviewed_by_name'] ?? '-' }}</div>
                                <div class="small text-muted">{{ $batchData['reviewed_at'] ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Tổng slot</div>
                                <div class="h5 mb-0">{{ $batchData['slot_count'] ?? 0 }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Đã phân công</div>
                                <div class="h5 mb-0 text-success">{{ $assignedSlotCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Chưa phân công</div>
                                <div class="h5 mb-0 text-danger">{{ $unassignedSlotCount }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Nguồn plan</div>
                                <div class="h5 mb-0">{{ count($sourcePlans) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Nguồn lịch tháng</div>
                                <div class="h5 mb-0">{{ count($sourceMonthlySchedules) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Nhóm ghép active</div>
                                <div class="h5 mb-0">{{ $activeMergeGroupCount }}</div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="border rounded p-2 bg-light">
                                <div class="small text-muted">Version</div>
                                <div class="h5 mb-0">{{ $batchData['version'] ?? 1 }}</div>
                            </div>
                        </div>
                    </div>

                    @if (! empty($batchData['review_note']))
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Ghi chú trả về:</strong> {{ $batchData['review_note'] }}
                        </div>
                    @endif

                    <div class="mt-3">
                        @if ($canReviewBatch)
                            <form method="POST" action="{{ route('department-monthly-assignment-batches.approve', $batchData['id']) }}" class="d-inline-block mr-2 mb-2" onsubmit="return confirm('Duyệt toàn bộ batch này?');">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check mr-1"></i>Duyệt toàn bộ
                                </button>
                            </form>

                            <form method="POST" action="{{ route('department-monthly-assignment-batches.return', $batchData['id']) }}" class="d-inline-block mb-2">
                                @csrf
                                <div class="form-row align-items-end">
                                    <div class="col-md-9 mb-2 mb-md-0">
                                        <textarea name="review_note" class="form-control form-control-sm" rows="2" required
                                            placeholder="Nhập ghi chú trả về chỉnh sửa"></textarea>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-outline-danger btn-sm btn-block"
                                            onclick="return confirm('Trả batch này về khoa để chỉnh sửa?');">
                                            <i class="fas fa-undo mr-1"></i>Trả về chỉnh sửa
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <span class="text-muted">Chỉ PDT hoặc admin được duyệt/trả batch khi batch ở trạng thái submitted.</span>
                        @endif
                    </div>
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
                    <i class="fas fa-list mr-1 text-primary"></i>Danh sách tiết trong batch
                </h5>
                <span class="badge badge-primary px-2 py-1">{{ $slots->count() }} tiết</span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Ngày</th>
                            <th>Tiết</th>
                            <th>Lớp</th>
                            <th>Môn</th>
                            <th>Bài học</th>
                            <th>Giảng viên</th>
                            <th>Phòng</th>
                            <th>Nhóm ghép</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($slots as $slot)
                            <tr>
                                <td>{{ $slot['date'] ?? '-' }}</td>
                                <td>{{ $slot['period_number'] ?? '-' }}</td>
                                <td>{{ $slot['class_code'] ?? $slot['class_name'] ?? '-' }}</td>
                                <td>{{ $slot['subject_code'] ?? $slot['subject_name'] ?? '-' }}</td>
                                <td>{{ $slot['lesson_label'] ?? '-' }}</td>
                                <td>{{ $slot['teacher_name'] ?? '-' }}</td>
                                <td>{{ $slot['room_code'] ?? '-' }}</td>
                                <td>
                                    @if (! empty($slot['merge_group_id']))
                                        <span class="badge badge-success">#{{ $slot['merge_group_id'] }}</span>
                                        <span class="badge badge-light">{{ $slot['merge_group_status'] ?? '-' }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if (($slot['slot_type'] ?? 'subject') === 'event')
                                        <span class="badge badge-secondary">Event</span>
                                    @elseif (($slot['slot_status'] ?? '') === 'cancelled')
                                        <span class="badge badge-danger">Cancelled</span>
                                    @elseif (! empty($slot['teacher_id']) || ! empty($slot['assignment_type']))
                                        <span class="badge badge-success">Đã phân công</span>
                                    @else
                                        <span class="badge badge-warning">Chưa phân công</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Không có tiết nào trong batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sourceMonthlySchedules->isNotEmpty())
                <div class="mt-4">
                    <h6 class="font-weight-bold mb-2">Nguồn monthly_schedule / plan</h6>
                    <div class="d-flex flex-wrap">
                        @foreach ($sourceMonthlySchedules as $source)
                            <span class="badge badge-light border mr-2 mb-2 px-2 py-1">
                                Monthly #{{ $source['monthly_schedule_id'] ?? '-' }}
                                @if (! empty($source['plan_name']))
                                    - {{ $source['plan_name'] }}
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
