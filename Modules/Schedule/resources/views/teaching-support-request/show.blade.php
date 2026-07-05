@extends('layouts.dashboard')

@section('title', 'Chi tiết yêu cầu hỗ trợ liên khoa')

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
        ];
        $items = $requestModel->items;
        $actionLabels = [
            'support_request_submitted' => 'Đã gửi yêu cầu',
            'support_request_returned_by_pdt' => 'PDT đã từ chối yêu cầu',
            'support_request_approved_by_pdt' => 'PDT đã duyệt yêu cầu',
            'support_request_item_confirmed' => 'Khoa đã xác nhận',
            'support_request_completed' => 'Hoàn tất yêu cầu',
            'support_request_item_inline_assigned' => 'Phân công trực tiếp cho tiết',
            'support_request_inline_assignment_completed' => 'Hoàn tất phân công trực tiếp',
            'support_request_item_withdrawn' => 'Đã rút tiết khỏi yêu cầu',
            'support_request_withdrawn' => 'Đã rút yêu cầu',
            'support_cancellation_request_submitted' => 'Đã gửi phiếu đề nghị hủy',
            'support_change_request_returned' => 'PDT từ chối phiếu hủy',
            'support_cancellation_request_approved' => 'PDT đã duyệt phiếu hủy',
            'support_request_item_cancelled' => 'Đã hủy tiết hỗ trợ',
            'support_cancellation_request_applied' => 'Đã áp dụng hủy',
        ];
        $itemStatusLabels = [
            'pending' => 'Chờ xử lý',
            'assigned' => 'Đã giao giảng viên',
            'confirmed' => 'Đã xác nhận',
            'rejected' => 'Từ chối',
            'cancelled' => 'Đã hủy',
        ];

        $formatDateTime = static function ($value): string {
            if ($value instanceof \Illuminate\Support\Carbon) {
                return $value->format('d/m/Y H:i');
            }

            if (is_string($value) && trim($value) !== '') {
                try {
                    return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i');
                } catch (\Throwable $e) {
                    return $value;
                }
            }

            return '-';
        };

        $formatDate = static function ($value): string {
            if ($value instanceof \Illuminate\Support\Carbon) {
                return $value->format('d/m/Y');
            }

            if (is_string($value) && trim($value) !== '') {
                try {
                    return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
                } catch (\Throwable $e) {
                    return $value;
                }
            }

            return '-';
        };

        $describeSlot = static function (?array $slot) use ($formatDate): string {
            if (! is_array($slot) || $slot === []) {
                return '-';
            }

            $parts = array_filter([
                isset($slot['date']) ? $formatDate($slot['date']) : null,
                isset($slot['period_number']) ? 'Tiết ' . $slot['period_number'] : null,
                $slot['class_code'] ?? $slot['class_name'] ?? null,
                $slot['subject_code'] ?? $slot['subject_name'] ?? null,
                $slot['room_code'] ?? $slot['room_name'] ?? null,
            ]);

            return $parts !== [] ? implode(' · ', $parts) : '-';
        };

        $describeItems = static function (?array $items) use ($describeSlot): array {
            if (! is_array($items) || $items === []) {
                return [];
            }

            return array_map(static function ($item) use ($describeSlot): string {
                if (! is_array($item)) {
                    return (string) $item;
                }

                $parts = array_filter([
                    isset($item['id']) ? '#' . $item['id'] : null,
                    $describeSlot($item['slot'] ?? null),
                    $item['status'] ?? null,
                    $item['assigned_teacher_name'] ?? null,
                ]);

                return $parts !== [] ? implode(' · ', $parts) : json_encode($item, JSON_UNESCAPED_UNICODE);
            }, $items);
        };

        $formatFieldValue = static function (string $key, $value) use ($statusLabels, $itemStatusLabels, $formatDate, $formatDateTime): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            return match ($key) {
                'status' => $statusLabels[(string) $value] ?? $itemStatusLabels[(string) $value] ?? (string) $value,
                'slot_status' => $itemStatusLabels[(string) $value] ?? (string) $value,
                'date' => $formatDate($value),
                'submitted_at', 'pdt_processed_at', 'assigned_at', 'occurred_at' => $formatDateTime($value),
                'period_number' => 'Tiết ' . $value,
                default => (string) $value,
            };
        };

        $fieldLabel = static function (string $key): string {
            return match ($key) {
                'id' => 'Mã số',
                'request_id' => 'Yêu cầu',
                'change_request_id' => 'Phiếu đề nghị hủy',
                'schedule_slot_id' => 'Tiết hỗ trợ',
                'status' => 'Trạng thái',
                'requesting_department_id' => 'Mã khoa đề nghị',
                'requesting_department_name' => 'Khoa đề nghị',
                'proposed_supporting_department_id' => 'Mã khoa hỗ trợ đề xuất',
                'proposed_supporting_department_name' => 'Khoa hỗ trợ đề xuất',
                'assigned_supporting_department_id' => 'Mã khoa hỗ trợ được giao',
                'assigned_supporting_department_name' => 'Khoa hỗ trợ được giao',
                'assigned_teacher_id' => 'Mã giảng viên được giao',
                'assigned_teacher_name' => 'Giảng viên được giao',
                'assigned_by' => 'Người giao',
                'assigned_at' => 'Thời gian giao',
                'submitted_at' => 'Thời gian gửi',
                'pdt_processed_at' => 'Thời gian PDT xử lý',
                'reason' => 'Lý do',
                'request_note' => 'Ghi chú',
                'pdt_note' => 'Ghi chú PDT',
                'note' => 'Ghi chú',
                'action' => 'Hành động',
                'revision_no' => 'Lần cập nhật',
                'previous_teacher_id' => 'Giảng viên cũ',
                'class_code' => 'Lớp',
                'class_name' => 'Tên lớp',
                'teacher_code' => 'Mã giảng viên',
                'teacher_name' => 'Giảng viên',
                'subject_code' => 'Môn',
                'subject_name' => 'Tên môn',
                'subject_lesson_title' => 'Bài học',
                'room_code' => 'Phòng',
                'room_name' => 'Tên phòng',
                'day_of_week' => 'Thứ',
                'period_number' => 'Tiết',
                'period' => 'Buổi',
                'assignment_source' => 'Nguồn phân công',
                'slot_type' => 'Loại tiết',
                'submitted_by' => 'Người gửi',
                default => ucfirst(str_replace('_', ' ', $key)),
            };
        };

        $buildRows = static function (?array $values) use ($fieldLabel, $formatFieldValue, $describeSlot): array {
            if (! is_array($values) || $values === []) {
                return [];
            }

            $rows = [];
            foreach ($values as $key => $value) {
                if ($key === 'items' && is_array($value)) {
                    $rows[] = [
                        'label' => $fieldLabel($key),
                        'value' => implode(' | ', array_map(static fn ($item): string => is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : (string) $item, $value)),
                    ];
                    continue;
                }

                if ($key === 'slot' && is_array($value)) {
                    $rows[] = [
                        'label' => $fieldLabel($key),
                        'value' => $describeSlot($value),
                    ];
                    continue;
                }

                if (is_array($value)) {
                    $rows[] = [
                        'label' => $fieldLabel($key),
                        'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                    ];
                    continue;
                }

                $rows[] = [
                    'label' => $fieldLabel($key),
                    'value' => $formatFieldValue($key, $value),
                ];
            }

            return $rows;
        };

        $buildSummarySections = static function (?array $values) use (&$buildRows, $describeItems): array {
            if (! is_array($values) || $values === []) {
                return [];
            }

            $sections = [];
            $working = $values;

            foreach (['request' => 'Thông tin yêu cầu', 'change_request' => 'Thông tin phiếu đề nghị hủy', 'item' => 'Thông tin tiết hỗ trợ', 'slot' => 'Thông tin tiết học'] as $sectionKey => $title) {
                if (! array_key_exists($sectionKey, $working) || ! is_array($working[$sectionKey])) {
                    continue;
                }

                $sections[] = [
                    'title' => $title,
                    'type' => 'rows',
                    'rows' => $buildRows($working[$sectionKey]),
                ];

                unset($working[$sectionKey]);
            }

            if (isset($working['items']) && is_array($working['items'])) {
                $sections[] = [
                    'title' => 'Danh sách tiết',
                    'type' => 'list',
                    'items' => $describeItems($working['items']),
                ];
                unset($working['items']);
            }

            $rows = $buildRows($working);
            if ($rows !== []) {
                $sections[] = [
                    'title' => 'Thông tin',
                    'type' => 'rows',
                    'rows' => $rows,
                ];
            }

            return $sections;
        };
    @endphp

    <div class="card border-left-warning shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <div>
                    <h4 class="card-title mb-1 text-warning font-weight-bold">
                        <i class="fas fa-clipboard-list mr-2"></i>Yêu cầu hỗ trợ #{{ $requestModel->id }}
                    </h4>
                    <div class="text-muted">
                        Khoa đề nghị: <strong>{{ $requestModel->requestingDepartment?->name ?? '-' }}</strong>
                        <span class="mx-2">|</span>
                        Khoa hỗ trợ: <strong>{{ $requestModel->assignedSupportingDepartment?->name ?? $requestModel->proposedSupportingDepartment?->name ?? '-' }}</strong>
                        <span class="mx-2">|</span>
                        Trạng thái:
                        <span class="badge {{ $statusClasses[$requestModel->status] ?? 'badge-secondary' }}">
                            {{ $statusLabels[$requestModel->status] ?? strtoupper($requestModel->status) }}
                        </span>
                    </div>
                </div>
                <div class="mt-2 mt-lg-0">
                    <a href="{{ route('teaching-support-requests.index') }}" class="btn btn-outline-secondary btn-sm mr-2">
                        <i class="fas fa-arrow-left mr-1"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Người gửi</div>
                    <div class="font-weight-bold">{{ $requestModel->submittedBy?->name ?? '-' }}</div>
                    <div class="small text-muted">{{ $requestModel->submitted_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">PDT xử lý</div>
                    <div class="font-weight-bold">{{ $requestModel->pdtProcessedBy?->name ?? '-' }}</div>
                    <div class="small text-muted">{{ $requestModel->pdt_processed_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Lý do / ghi chú</div>
                    <div>{{ $requestModel->request_note ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($requestModel->pdt_note)
        <div class="alert alert-info">
            <strong>Ghi chú PDT:</strong> {{ $requestModel->pdt_note }}
        </div>
    @endif

    @if ($canProcess && $requestModel->status === 'pending_pdt')
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                    <div>
                        <h5 class="card-title mb-1 text-primary font-weight-bold">
                            <i class="fas fa-check-circle mr-2"></i>Phê duyệt yêu cầu hỗ trợ
                        </h5>
                        <div class="small text-muted">PDT chỉ duyệt theo khoa đã đề xuất hoặc từ chối yêu cầu.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('teaching-support-requests.review', $requestModel->id) }}">
                    @csrf
                    <div class="form-row">
                        <div class="col-lg-12 mb-3">
                            <label class="font-weight-bold">Ghi chú PDT</label>
                            <textarea name="pdt_note" class="form-control" rows="4" maxlength="2000"
                                placeholder="Nhập ghi chú nếu cần"></textarea>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap">
                        <button type="submit" name="action" value="approve" class="btn btn-primary mr-2 mb-2">
                            <i class="fas fa-check mr-1"></i>Phê duyệt
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger mb-2">
                            <i class="fas fa-times mr-1"></i>Từ chối
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-2">
                <div>
                    <h5 class="card-title mb-1 text-primary font-weight-bold">
            <i class="fas fa-ban mr-2"></i>Thu hồi / hủy yêu cầu hỗ trợ
                    </h5>
                    <div class="small text-muted">Chỉ thu hồi toàn bộ yêu cầu, không sửa từng tiết.</div>
                </div>
                <div class="mt-2 mt-lg-0 d-flex flex-wrap">
                    @if ($canWithdrawDirectly)
                        <a href="{{ route('teaching-support-change-requests.create', $requestModel->id) }}" class="btn btn-danger btn-sm mr-2">
                            <i class="fas fa-undo mr-1"></i>Thu hồi yêu cầu
                        </a>
                    @endif
                    @if ($canCreateChangeRequest)
                        <a href="{{ route('teaching-support-change-requests.create', $requestModel->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-ban mr-1"></i>Gửi phiếu đề nghị hủy
                        </a>
                    @endif
                </div>
            </div>

            @forelse (($changeRequestPayload['change_requests'] ?? collect()) as $changeRequest)
                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <strong>#{{ $changeRequest->id }}</strong>
                            <span class="badge badge-info ml-2">{{ $changeRequest->status }}</span>
                            <span class="text-muted small ml-2">Phiên bản {{ $changeRequest->revision_no }}</span>
                        </div>
                        <div class="d-flex flex-wrap mt-2 mt-sm-0">
                            @if ($canProcess && $changeRequest->status === 'pending_pdt')
                                <a href="{{ route('teaching-support-change-requests.show', $changeRequest->id) }}" class="btn btn-sm btn-primary mr-2 mb-2 mb-sm-0">Xem và xử lý</a>
                            @else
                                <a href="{{ route('teaching-support-change-requests.show', $changeRequest->id) }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2 mb-sm-0">Xem chi tiết</a>
                            @endif
                        </div>
                    </div>
                    <div class="small text-muted mt-2">{{ $changeRequest->reason ?: 'Không có lý do' }}</div>
                    <div class="small mt-2">
                        Người gửi: <strong>{{ $changeRequest->submittedBy?->name ?? '-' }}</strong>
                        <span class="mx-2">|</span>
                        PDT: <strong>{{ $changeRequest->pdtProcessedBy?->name ?? '-' }}</strong>
                    </div>
                    @if ($canProcess && $changeRequest->status === 'pending_pdt')
                        <form method="POST" action="{{ route('teaching-support-change-requests.review', $changeRequest->id) }}" class="mt-3">
                            @csrf
                            <div class="form-row">
                                <div class="col-lg-8 mb-2">
                                    <label class="small font-weight-bold mb-1">Ghi chú PDT</label>
                                    <textarea name="pdt_note" class="form-control form-control-sm" rows="2" maxlength="2000" required
                                        placeholder="Nhập ghi chú khi duyệt hoặc từ chối"></textarea>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap">
                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-primary mr-2 mb-2">
                                    <i class="fas fa-check mr-1"></i>Duyệt phiếu hủy
                                </button>
                                <button type="submit" name="action" value="return" class="btn btn-sm btn-outline-danger mb-2">
                                    <i class="fas fa-times mr-1"></i>Từ chối
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @empty
                <div class="text-muted">Chưa có phiếu đề nghị hủy nào.</div>
            @endforelse
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title mb-3 text-dark font-weight-bold">
                <i class="fas fa-stream mr-2"></i>Lịch sử xử lý
            </h5>
            <div class="timeline-list">
            @forelse (($changeRequestPayload['history_logs'] ?? collect()) as $log)
                @php
                    $oldSections = $buildSummarySections($log->old_values ?? null);
                    $newSections = $buildSummarySections($log->new_values ?? null);
                @endphp
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div class="mb-2">
                                <div class="small text-muted">{{ $log->occurred_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                <div class="font-weight-bold">{{ $actionLabels[$log->action] ?? $log->action }}</div>
                                <div class="small text-muted">
                                    {{ $log->actor_name_snapshot ?? $log->actor?->name ?? '-' }}
                                    @if ($log->actor_role_snapshot)
                                        - {{ $log->actor_role_snapshot }}
                                    @endif
                                    @if ($log->actorDepartment?->name)
                                        - {{ $log->actorDepartment?->name }}
                                    @endif
                                </div>
                            </div>
                            @if ($log->note)
                                <div class="mt-2 p-2 rounded border bg-white text-dark small">
                                    <div class="text-uppercase text-muted font-weight-bold mb-1">Ghi chu</div>
                                    <div>{{ $log->note }}</div>
                                </div>
                            @endif
                        </div>

                        @if ($oldSections !== [] || $newSections !== [])
                            <div class="row mt-3">
                                @if ($oldSections !== [])
                                    <div class="col-lg-6 mb-3">
                                        <div class="small text-uppercase text-muted font-weight-bold mb-2">Trước</div>
                                        @foreach ($oldSections as $section)
                                            <div class="border rounded bg-light p-3 mb-2">
                                                <div class="font-weight-bold mb-2">{{ $section['title'] }}</div>
                                                @if (($section['type'] ?? 'rows') === 'list')
                                                    <ul class="mb-0 pl-3">
                                                        @foreach ($section['items'] as $itemLine)
                                                            <li class="mb-1">{{ $itemLine }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <dl class="row mb-0">
                                                        @foreach ($section['rows'] as $row)
                                                            <dt class="col-sm-5 text-muted small mb-1">{{ $row['label'] }}</dt>
                                                            <dd class="col-sm-7 mb-1">{{ $row['value'] }}</dd>
                                                        @endforeach
                                                    </dl>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($newSections !== [])
                                    <div class="col-lg-6 mb-3">
                                        <div class="small text-uppercase text-muted font-weight-bold mb-2">Sau</div>
                                        @foreach ($newSections as $section)
                                            <div class="border rounded bg-white p-3 mb-2">
                                                <div class="font-weight-bold mb-2">{{ $section['title'] }}</div>
                                                @if (($section['type'] ?? 'rows') === 'list')
                                                    <ul class="mb-0 pl-3">
                                                        @foreach ($section['items'] as $itemLine)
                                                            <li class="mb-1">{{ $itemLine }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <dl class="row mb-0">
                                                        @foreach ($section['rows'] as $row)
                                                            <dt class="col-sm-5 text-muted small mb-1">{{ $row['label'] }}</dt>
                                                            <dd class="col-sm-7 mb-1">{{ $row['value'] }}</dd>
                                                        @endforeach
                                                    </dl>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @if ($loop->last === false)
                    <div class="border-left ml-3 mb-3" style="border-left-width: 3px !important;"></div>
                @endif
            @empty
                <div class="text-muted">Chua co log xu ly.</div>
            @endforelse
        </div>
    </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Ngày</th>
                            <th>Tiết</th>
                            <th>Lớp</th>
                            <th>Môn</th>
                            <th>Bài học</th>
                            <th>GV ho tro</th>
                            <th>Phòng</th>
                            <th>Ghi chu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
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
                                <td>{{ $item->assignedTeacher?->name ?? $item->scheduleSlot?->teacher?->name ?? '-' }}</td>
                                <td>{{ $item->scheduleSlot?->room?->code ?? '-' }}</td>
                                <td>{{ $item->note ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
