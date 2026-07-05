@extends('layouts.dashboard')

@section('title', 'Chi tiết phiếu đề nghị hủy yêu cầu hỗ trợ liên khoa')

@section('content')
    @php
        $statusLabels = [
            'pending_pdt' => 'Chờ PDT duyệt',
            'returned' => 'Đã từ chối',
            'completed' => 'Đã duyệt hủy',
            'cancelled' => 'Đã hủy',
            'rejected' => 'Đã từ chối',
        ];
        $actionLabels = [
            'support_cancellation_request_submitted' => 'Đã gửi phiếu đề nghị hủy',
            'support_change_request_returned' => 'PDT từ chối phiếu hủy',
            'support_cancellation_request_approved' => 'PDT đã duyệt phiếu hủy',
            'support_request_item_cancelled' => 'Đã hủy tiết hỗ trợ',
            'support_cancellation_request_applied' => 'Đã áp dụng hủy',
            'support_request_withdrawn' => 'Đã rút yêu cầu',
        ];
        $itemActionLabels = [
            'remove' => 'Xóa khỏi yêu cầu',
            'keep' => 'Giữ nguyên',
        ];
        $itemStatusLabels = [
            'pending' => 'Chờ xử lý',
            'completed' => 'Đã xử lý',
            'returned' => 'Đã từ chối',
            'cancelled' => 'Đã hủy',
            'rejected' => 'Đã từ chối',
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
        $describeItems = static function (?array $items) use ($describeSlot, $itemStatusLabels, $itemActionLabels): array {
            if (! is_array($items) || $items === []) {
                return [];
            }

            return array_map(static function ($item) use ($describeSlot, $itemStatusLabels, $itemActionLabels): string {
                if (! is_array($item)) {
                    return (string) $item;
                }

                $parts = array_filter([
                    isset($item['id']) ? '#' . $item['id'] : null,
                    $itemActionLabels[(string) ($item['action'] ?? '')] ?? $item['action'] ?? null,
                    $describeSlot($item['slot'] ?? null),
                    $itemStatusLabels[(string) ($item['status'] ?? '')] ?? $item['status'] ?? null,
                    $item['previous_teacher_name'] ?? null,
                ]);

                return $parts !== [] ? implode(' · ', $parts) : json_encode($item, JSON_UNESCAPED_UNICODE);
            }, $items);
        };
        $fieldLabel = static function (string $key): string {
            return match ($key) {
                'id' => 'Mã số',
                'teaching_support_request_id' => 'Yêu cầu gốc',
                'change_request_id' => 'Phiếu đề nghị hủy',
                'requesting_department_id' => 'Khoa đề nghị',
                'requesting_department_name' => 'Khoa đề nghị',
                'proposed_supporting_department_id' => 'Khoa hỗ trợ đề xuất',
                'proposed_supporting_department_name' => 'Khoa hỗ trợ đề xuất',
                'assigned_supporting_department_id' => 'Khoa hỗ trợ được giao',
                'assigned_supporting_department_name' => 'Khoa hỗ trợ được giao',
                'status' => 'Trạng thái',
                'reason' => 'Lý do',
                'revision_no' => 'Lần cập nhật',
                'submitted_at' => 'Thời gian gửi',
                'pdt_processed_at' => 'Thời gian PDT xử lý',
                'assigned_teacher_id' => 'Giảng viên được giao',
                'assigned_teacher_name' => 'Giảng viên được giao',
                'previous_teacher_id' => 'Giảng viên cũ',
                'previous_teacher_name' => 'Giảng viên cũ',
                'action' => 'Hành động',
                'note' => 'Ghi chú',
                'pdt_note' => 'Ghi chú PDT',
                'item_status' => 'Trạng thái tiết',
                'schedule_slot_id' => 'Tiết hỗ trợ',
                'class_code' => 'Lớp',
                'class_name' => 'Tên lớp',
                'subject_code' => 'Môn',
                'subject_name' => 'Tên môn',
                'room_code' => 'Phòng',
                'room_name' => 'Tên phòng',
                'teacher_code' => 'Mã giảng viên',
                'teacher_name' => 'Giảng viên',
                'day_of_week' => 'Thứ',
                'period_number' => 'Tiết',
                'period' => 'Buổi',
                'date' => 'Ngày',
                'slot_type' => 'Loại tiết',
                'assignment_source' => 'Nguồn phân công',
                default => ucfirst(str_replace('_', ' ', $key)),
            };
        };
        $formatValue = static function (string $key, $value) use ($statusLabels, $itemStatusLabels, $formatDate, $formatDateTime): string {
            if ($value === null || $value === '') {
                return '-';
            }

            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            return match ($key) {
                'status' => $statusLabels[(string) $value] ?? (string) $value,
                'item_status' => $itemStatusLabels[(string) $value] ?? (string) $value,
                'date' => $formatDate($value),
                'submitted_at', 'pdt_processed_at', 'occurred_at', 'assigned_at' => $formatDateTime($value),
                'period_number' => 'Tiết ' . $value,
                default => (string) $value,
            };
        };
        $buildRows = static function (?array $values) use ($fieldLabel, $formatValue, $describeSlot): array {
            if (! is_array($values) || $values === []) {
                return [];
            }

            $rows = [];
            foreach ($values as $key => $value) {
                if ($key === 'slot' && is_array($value)) {
                    $rows[] = [
                        'label' => $fieldLabel($key),
                        'value' => $describeSlot($value),
                    ];
                    continue;
                }

                if ($key === 'items' && is_array($value)) {
                    $rows[] = [
                        'label' => $fieldLabel($key),
                        'value' => implode(' | ', array_map(static fn ($item): string => is_array($item) ? json_encode($item, JSON_UNESCAPED_UNICODE) : (string) $item, $value)),
                    ];
                    continue;
                }

                $rows[] = [
                    'label' => $fieldLabel($key),
                    'value' => $formatValue((string) $key, $value),
                ];
            }

            return $rows;
        };
        $buildSummarySections = static function (?array $values) use ($buildRows, $describeItems): array {
            if (! is_array($values) || $values === []) {
                return [];
            }

            $sections = [];
            $working = $values;

            foreach ([
                'change_request' => 'Thông tin phiếu đề nghị hủy',
                'request' => 'Thông tin yêu cầu gốc',
                'item' => 'Thông tin tiết hỗ trợ',
                'slot' => 'Thông tin tiết học',
            ] as $sectionKey => $title) {
                if (array_key_exists($sectionKey, $working) && is_array($working[$sectionKey])) {
                    $sections[] = [
                        'title' => $title,
                        'type' => 'rows',
                        'rows' => $buildRows($working[$sectionKey]),
                    ];
                    unset($working[$sectionKey]);
                }
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

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h4 class="card-title text-primary font-weight-bold mb-1">
                        <i class="fas fa-clipboard-list mr-2"></i>Phiếu đề nghị hủy #{{ $changeRequest->id }}
                    </h4>
                    <div class="text-muted">
                        Yêu cầu gốc #{{ $changeRequest->teaching_support_request_id }}
                        <span class="mx-2">|</span>
                        Trạng thái:
                        <span class="badge badge-info">{{ $statusLabels[$changeRequest->status] ?? $changeRequest->status }}</span>
                    </div>
                </div>
                <a href="{{ route('teaching-support-requests.show', $changeRequest->teaching_support_request_id) }}" class="btn btn-outline-secondary btn-sm">
                    Quay lại yêu cầu gốc
                </a>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Người gửi</div>
                    <div class="font-weight-bold">{{ $changeRequest->submittedBy?->name ?? '-' }}</div>
                    <div class="small text-muted">{{ $changeRequest->submitted_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">PDT xử lý</div>
                    <div class="font-weight-bold">{{ $changeRequest->pdtProcessedBy?->name ?? '-' }}</div>
                    <div class="small text-muted">{{ $changeRequest->pdt_processed_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted">Lý do</div>
                    <div>{{ $changeRequest->reason ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($changeRequest->pdt_note)
        <div class="alert alert-info">
            <strong>Ghi chú PDT:</strong> {{ $changeRequest->pdt_note }}
        </div>
    @endif

    @if ($canProcess && $changeRequest->status === 'pending_pdt')
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('teaching-support-change-requests.review', $changeRequest->id) }}">
                    @csrf
                    <div class="form-row">
                        <div class="col-md-7 mb-2">
                            <label class="font-weight-bold">Ghi chú PDT</label>
                            <textarea name="pdt_note" class="form-control" rows="3" maxlength="2000" required></textarea>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap">
                        <button type="submit" name="action" value="approve" class="btn btn-primary mr-2 mb-2">
                            <i class="fas fa-check mr-1"></i>Duyệt phiếu hủy
                        </button>
                        <button type="submit" name="action" value="return" class="btn btn-outline-danger mb-2">
                            <i class="fas fa-times mr-1"></i>Từ chối
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title text-dark font-weight-bold mb-3">Các tiết bị hủy</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Ngày</th>
                            <th>Tiết</th>
                            <th>Lớp</th>
                            <th>Môn</th>
                            <th>Bài học</th>
                            <th>Phòng</th>
                            <th>Giảng viên cũ</th>
                            <th>Hành động</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($changeRequest->items as $item)
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
                                <td>
                                    @php
                                        $subjectLesson = $item->scheduleSlot?->subjectLesson;
                                        $lessonLabel = $subjectLesson
                                            ? 'B' . ($subjectLesson->lesson_no ?? '?') . ': ' . ($subjectLesson->title ?? '-')
                                            : '-';
                                    @endphp
                                    {{ $lessonLabel }}
                                </td>
                                <td>{{ $item->previousTeacher?->name ?? $item->supportRequestItem?->assignedTeacher?->name ?? '-' }}</td>
                                <td>{{ $itemActionLabels[$item->action] ?? $item->action }}</td>
                                <td>{{ $itemStatusLabels[$item->status] ?? $item->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title text-dark font-weight-bold mb-3">Lịch sử xử lý</h5>
            <div class="timeline-list">
                @forelse ($changeRequest->auditLogs as $log)
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
                @empty
                    <div class="text-muted">Chua co log.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
