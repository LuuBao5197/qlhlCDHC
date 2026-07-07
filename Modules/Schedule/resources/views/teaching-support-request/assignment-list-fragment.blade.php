@php
    $requestStatusClasses = [
        'pending_pdt' => 'badge-info',
        'assigned_to_department' => 'badge-primary',
        'department_assigning' => 'badge-warning',
        'completed' => 'badge-success',
        'returned' => 'badge-secondary',
        'cancelled' => 'badge-dark',
    ];

    $itemStatusClasses = [
        'pending' => 'badge-secondary',
        'assigned' => 'badge-info',
        'confirmed' => 'badge-success',
        'rejected' => 'badge-danger',
    ];

    $currentUser = auth()->user();
    $currentDepartmentId = (int) ($currentUser?->department_id ?? 0);
    $canWithdrawRequest = static function ($requestModel) use ($currentUser, $currentDepartmentId): bool {
        if (! $currentUser || ! $currentUser->isDepartmentStaff()) {
            return false;
        }

        if ((int) ($requestModel?->requesting_department_id ?? 0) !== $currentDepartmentId) {
            return false;
        }

        return in_array((string) ($requestModel?->status ?? 'pending_pdt'), [
            'pending_pdt',
            'assigned_to_department',
            'department_assigning',
            'completed',
        ], true);
    };

    $requestGroups = $requests->getCollection()->groupBy(static function ($item) {
        $requestId = (int) ($item->request_id ?? $item->request?->id ?? 0);

        if ($requestId > 0) {
            return 'request:' . $requestId;
        }

        return 'item:' . (int) ($item->id ?? 0);
    });
@endphp

<div class="bg-light rounded p-3">
    <form method="GET" action="{{ $modalUrl }}" class="mb-3" data-support-request-modal-filter-form>
        <div class="form-row">
            <div class="col-md-4 mb-2">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                    placeholder="Tìm theo lớp, môn, phòng, ghi chú...">
            </div>
            <div class="col-md-3 mb-2">
                <select name="status" class="form-control form-control-sm">
                    @foreach ($requestStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <select name="item_status" class="form-control form-control-sm">
                    @foreach ($itemStatusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['item_status'] ?? 'all') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2 d-flex">
                <button type="submit" class="btn btn-primary btn-sm mr-2 flex-fill">Lọc</button>
                <a href="{{ $modalUrl }}" class="btn btn-outline-secondary btn-sm flex-fill">Đặt lại</a>
            </div>
        </div>
    </form>

    <div class="bg-white border rounded shadow-sm overflow-hidden" data-support-request-modal-table>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Ngày</th>
                        <th>Tiết</th>
                        <th>Lớp</th>
                        <th>Môn</th>
                        <th>Bài học</th>
                        <th>Phòng</th>
                        <th>Khoa hỗ trợ</th>
                        <th>Trạng thái phiếu</th>
                        <th>Trạng thái tiết</th>
                        <th>Người gửi</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requestGroups as $requestId => $groupItems)
                        @php
                            $firstItem = $groupItems->first();
                            $requestModel = $firstItem?->request;
                            $requestStatus = $requestModel?->status ?? 'pending_pdt';
                            $showWithdrawAction = $requestModel ? $canWithdrawRequest($requestModel) : false;
                            $groupRowspan = max(1, $groupItems->count());
                            $groupItemStatus = $firstItem?->status ?? 'pending';
                            $groupNote = $firstItem?->note ?: ($requestModel?->request_note ?: '-');
                        @endphp

                        @foreach ($groupItems as $index => $item)
                            <tr>
                                <td class="text-nowrap">{{ $item->scheduleSlot?->date?->format('d/m/Y') ?? '-' }}</td>
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
                                @if ($index === 0)
                                    <td rowspan="{{ $groupRowspan }}">
                                        <div class="font-weight-bold">{{ $requestModel?->assignedSupportingDepartment?->name ?? $requestModel?->proposedSupportingDepartment?->name ?? '-' }}</div>
                                        <div class="small text-muted">{{ $requestModel?->requestingDepartment?->name ?? '-' }}</div>
                                    </td>
                                    <td rowspan="{{ $groupRowspan }}">
                                        <span class="badge {{ $requestStatusClasses[$requestStatus] ?? 'badge-secondary' }}">
                                            {{ $requestStatusLabels[$requestStatus] ?? strtoupper($requestStatus) }}
                                        </span>
                                    </td>
                                    <td rowspan="{{ $groupRowspan }}">
                                        <span class="badge {{ $itemStatusClasses[$groupItemStatus] ?? 'badge-secondary' }}">
                                            {{ $itemStatusLabels[$groupItemStatus] ?? strtoupper($groupItemStatus) }}
                                        </span>
                                    </td>
                                    <td rowspan="{{ $groupRowspan }}">
                                        <div class="font-weight-bold">{{ $requestModel?->submittedBy?->name ?? '-' }}</div>
                                        <div class="small text-muted">{{ $requestModel?->submitted_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                    </td>
                                    <td style="min-width: 220px; max-width: 260px;" rowspan="{{ $groupRowspan }}">
                                        <div class="text-break">{{ $groupNote }}</div>
                                    </td>
                                @endif
                                @if ($index === 0)
                                    <td style="min-width: 180px; vertical-align: top;" rowspan="{{ $groupRowspan }}">
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('teaching-support-requests.show', $requestModel?->id) }}"
                                                class="btn btn-outline-warning btn-sm mb-1" target="_blank" rel="noopener">
                                                Xem chi tiết
                                            </a>
                                            @if ($requestModel && $showWithdrawAction)
                                                <a href="{{ route('teaching-support-change-requests.create', $requestModel?->id) }}"
                                                    class="btn btn-outline-danger btn-sm">
                                                    Thu hồi / đề nghị hủy
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                Chưa có tiết nào khớp bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3" data-support-request-modal-pagination>
        <div class="small text-muted mb-2 mb-md-0">
            Hiển thị {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }}
            / {{ $requests->total() }} tiết
        </div>
        <div>
            {{ $requests->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

