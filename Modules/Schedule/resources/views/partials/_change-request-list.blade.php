@php
    $teacherMap = ($teacherLookup ?? $teachers ?? collect())->keyBy('id');
    $roomMap = ($rooms ?? collect())->keyBy('id');
    $lessonMap = ($subjectLessons ?? collect())->keyBy('id');

    $fieldOrder = [
        'date',
        'day_of_week',
        'period_number',
        'teacher_id',
        'subject_id',
        'subject_lesson_id',
        'room_id',
        'content',
        'note',
        'slot_status',
        'actual_content',
    ];

    $fieldLabels = [
        'date' => 'Ngay hoc',
        'day_of_week' => 'Thu',
        'period_number' => 'Tiet',
        'teacher_id' => 'Giảng viên',
        'subject_id' => 'Môn học',
        'subject_lesson_id' => 'Bài học',
        'room_id' => 'Phòng học',
        'content' => 'Nội dung',
        'note' => 'Ghi chú',
        'slot_status' => 'Trạng thái tiết',
        'actual_content' => 'Nội dung thực tế',
    ];

    $slotStatusLabels = [
        'planned' => 'Theo kế hoạch',
        'updated' => 'Đã cập nhật',
        'cancelled' => 'Đã hủy',
        'completed' => 'Đã hoàn thành',
    ];

    $changeTypeLabels = [
        'general' => 'Dieu chinh chung',
        'holiday_reschedule' => 'Doi lich nghi le/tet',
    ];

    $dayOfWeekLabels = [
        2 => 'Thu 2',
        3 => 'Thu 3',
        4 => 'Thu 4',
        5 => 'Thu 5',
        6 => 'Thu 6',
        7 => 'Thu 7',
        8 => 'CN',
    ];

    $formatDayOfWeek = static function ($value) use ($dayOfWeekLabels): string {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        $day = (int) $value;

        // Current module convention: 2..8 (Thu 2..CN).
        if (isset($dayOfWeekLabels[$day])) {
            return $dayOfWeekLabels[$day];
        }

        // Legacy fallback: 0..6 (CN..Thu 7) -> 8,2..7.
        if ($day >= 0 && $day <= 6) {
            $normalized = $day === 0 ? 8 : $day + 1;

            return $dayOfWeekLabels[$normalized] ?? (string) $value;
        }

        return (string) $value;
    };

    $normalizeValue = static function ($value) {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    };

    $resolveFieldValue = static function (string $field, $value, $slot) use ($teacherMap, $roomMap, $lessonMap, $slotStatusLabels, $formatDayOfWeek): string {
        if ($value === null || $value === '') {
            return '-';
        }

        if ($field === 'date') {
            try {
                return \Carbon\Carbon::parse((string) $value)->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if ($field === 'day_of_week') {
            return $formatDayOfWeek($value);
        }

        if ($field === 'period_number') {
            return 'Tiet ' . (string) $value;
        }

        if ($field === 'teacher_id') {
            $teacherId = is_numeric($value) ? (int) $value : null;

            if ($teacherId && $slot?->teacher && (int) $slot->teacher->id === $teacherId) {
                $teacherCode = $slot->teacher->teacher_code ?? null;

                return trim($slot->teacher->name . ($teacherCode ? ' (' . $teacherCode . ')' : ''));
            }

            $teacher = $teacherId ? $teacherMap->get($teacherId) : null;
            if ($teacher) {
                $teacherCode = $teacher->employee_code ?? $teacher->teacher_code ?? null;

                return trim($teacher->name . ($teacherCode ? ' (' . $teacherCode . ')' : ''));
            }

            return $teacherId ? ('Không tìm thấy giảng viên (ID ' . $teacherId . ')') : (string) $value;
        }

        if ($field === 'subject_id') {
            $subjectId = is_numeric($value) ? (int) $value : null;

            if ($subjectId && $slot?->subjectModel && (int) $slot->subjectModel->id === $subjectId) {
                return trim(($slot->subjectModel->code ?? '') . ' - ' . ($slot->subjectModel->name ?? ''));
            }

            return $subjectId ? ('ID ' . $subjectId) : (string) $value;
        }

        if ($field === 'subject_lesson_id') {
            $lessonId = is_numeric($value) ? (int) $value : null;

            if ($lessonId && $slot?->subjectLesson && (int) $slot->subjectLesson->id === $lessonId) {
                $subjectCode = $slot->subjectLesson->subject?->code;
                $prefix = $subjectCode ? ($subjectCode . ' - ') : '';

                return $prefix . 'B' . ($slot->subjectLesson->lesson_no ?? '?') . ': ' . ($slot->subjectLesson->title ?? '-');
            }

            $lesson = $lessonId ? $lessonMap->get($lessonId) : null;
            if ($lesson) {
                $subjectCode = $lesson->subject?->code ?? null;
                $prefix = $subjectCode ? ($subjectCode . ' - ') : '';

                return $prefix . 'B' . ($lesson->lesson_no ?? '?') . ': ' . ($lesson->title ?? '-');
            }

            return $lessonId ? ('ID ' . $lessonId) : (string) $value;
        }

        if ($field === 'room_id') {
            $roomId = is_numeric($value) ? (int) $value : null;

            if ($roomId && $slot?->room && (int) $slot->room->id === $roomId) {
                return trim(($slot->room->code ?? '') . ' ' . ($slot->room->name ?? ''));
            }

            $room = $roomId ? $roomMap->get($roomId) : null;
            if ($room) {
                return trim(($room->code ?? '') . ' ' . ($room->name ?? ''));
            }

            return $roomId ? ('ID ' . $roomId) : (string) $value;
        }

        if ($field === 'slot_status') {
            $status = (string) $value;

            return $slotStatusLabels[$status] ?? $status;
        }

        return (string) $value;
    };

    $buildDiffRows = static function (array $oldPayload, array $newPayload, $slot) use ($fieldOrder, $fieldLabels, $normalizeValue, $resolveFieldValue): array {
        $rows = [];

        foreach ($fieldOrder as $field) {
            $hasOld = array_key_exists($field, $oldPayload);
            $hasNew = array_key_exists($field, $newPayload);

            if (! $hasOld && ! $hasNew) {
                continue;
            }

            if (! $hasNew) {
                continue;
            }

            $oldValue = $hasOld ? $oldPayload[$field] : null;
            $newValue = $newPayload[$field];

            if ($normalizeValue($oldValue) === $normalizeValue($newValue)) {
                continue;
            }

            $rows[] = [
                'label' => $fieldLabels[$field] ?? $field,
                'old' => $resolveFieldValue($field, $oldValue, $slot),
                'new' => $resolveFieldValue($field, $newValue, $slot),
            ];
        }

        return $rows;
    };

    $applyStatusClass = [
        'applied' => 'badge-success',
        'failed' => 'badge-danger',
    ];
@endphp

<style>
    .cr-diff-old {
        background-color: #fff5f5;
        color: #842029;
    }

    .cr-diff-new {
        background-color: #eefaf1;
        color: #0f5132;
        font-weight: 600;
    }

    .cr-group-header {
        background: #f1f3f5;
        border: 1px solid #dee2e6;
        border-radius: 0.35rem;
    }
</style>

<div class="table-responsive mt-3">
    <table class="table table-bordered table-hover mb-0 align-middle">
        <thead>
            <tr>
                <th style="width: 70px;">ID</th>
                <th>Lịch tháng</th>
                <th>Người đề nghị</th>
                <th>Trạng thái</th>
                <th>Lý do</th>
                <th>Nội dung thay đổi</th>
                <th style="min-width: 360px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($changeRequests as $changeRequest)
                @php
                    $statusClass = $statusStyles[$changeRequest->status] ?? 'badge-light';
                    $requestedBy = $changeRequest->requestedBy;
                    $requesterUsername = $requestedBy?->employee_code ?: $requestedBy?->email;
                    $requesterFullName = $requestedBy?->name;
                    $requesterDepartment = $requestedBy?->department?->name;
                @endphp
                <tr>
                    <td>{{ $changeRequest->id }}</td>
                    <td>
                        @if ($changeRequest->monthlySchedule)
                            Lớp {{ $changeRequest->monthlySchedule->class_name }}
                            ({{ $changeRequest->monthlySchedule->month }}/{{ $changeRequest->monthlySchedule->year }})
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($requestedBy)
                            <div class="font-weight-bold">{{ $requesterUsername ?? '-' }}</div>
                            <div class="small text-muted">{{ $requesterFullName ?? '-' }}</div>
                            @if ($requesterDepartment)
                                <div class="small text-muted">{{ $requesterDepartment }}</div>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td><span class="badge {{ $statusClass }}">{{ $changeRequest->status }}</span></td>
                    <td>
                        {{ \Illuminate\Support\Str::limit($changeRequest->reason, 80) }}
                        <div class="small text-muted mt-1">
                            Loai: {{ $changeTypeLabels[$changeRequest->change_type ?? 'general'] ?? ($changeRequest->change_type ?? 'general') }}
                        </div>
                    </td>
                    <td>
                        @php
                            $displayItems = $changeRequest->changeRequestItems->map(static function ($item) {
                                return [
                                    'slot' => $item->scheduleSlot,
                                    'old_payload' => is_array($item->old_payload) ? $item->old_payload : [],
                                    'new_payload' => is_array($item->new_payload) ? $item->new_payload : [],
                                    'apply_status' => $item->apply_status,
                                    'apply_error' => $item->apply_error,
                                ];
                            })->values();

                            if ($displayItems->isEmpty()) {
                                $displayItems = collect([
                                    [
                                        'slot' => $changeRequest->scheduleSlot,
                                        'old_payload' => is_array($changeRequest->old_payload) ? $changeRequest->old_payload : [],
                                        'new_payload' => is_array($changeRequest->new_payload) ? $changeRequest->new_payload : [],
                                        'apply_status' => null,
                                        'apply_error' => null,
                                    ],
                                ]);
                            }

                            $groupedItems = $displayItems->groupBy(static function ($item) {
                                $slot = $item['slot'] ?? null;
                                $slotDate = optional($slot?->date)->format('d/m/Y') ?? 'Không rõ ngày';
                                $slotClass = $slot?->trainingClass?->code ?? $slot?->trainingClass?->name ?? 'Không rõ lớp';

                                return $slotDate . '||' . $slotClass;
                            });
                        @endphp

                        <details>
                            <summary class="text-primary">Xem đối chiếu trước/sau</summary>
                            <div class="mt-2">
                                @foreach ($groupedItems as $groupKey => $groupItems)
                                    @php
                                        [$groupDate, $groupClass] = array_pad(explode('||', (string) $groupKey, 2), 2, '-');
                                    @endphp

                                    <div class="cr-group-header d-flex justify-content-between align-items-center p-2 mb-2">
                                        <div class="font-weight-bold">{{ $groupDate }} - Lớp {{ $groupClass }}</div>
                                        <span class="badge badge-secondary">{{ $groupItems->count() }} mục</span>
                                    </div>

                                    @foreach ($groupItems as $index => $displayItem)
                                        @php
                                            $slot = $displayItem['slot'];
                                            $oldPayload = is_array($displayItem['old_payload']) ? $displayItem['old_payload'] : [];
                                            $newPayload = is_array($displayItem['new_payload']) ? $displayItem['new_payload'] : [];
                                            $diffRows = $buildDiffRows($oldPayload, $newPayload, $slot);
                                            $slotPeriod = $slot?->period_number ?? '-';
                                            $itemStatus = $displayItem['apply_status'] ?? null;
                                        @endphp

                                        <div class="border rounded p-2 mb-2 bg-light">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted">
                                                    Mục {{ $index + 1 }}: Tiết {{ $slotPeriod }}
                                                </small>

                                                @if ($itemStatus)
                                                    <span class="badge {{ $applyStatusClass[$itemStatus] ?? 'badge-secondary' }}">
                                                        {{ $itemStatus }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if ($diffRows === [])
                                                <div class="text-muted small">Không có trường thay đổi.</div>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-0 bg-white">
                                                        <thead>
                                                            <tr>
                                                                <th>Trường</th>
                                                                <th>Theo kế hoạch</th>
                                                                <th>Đề nghị thay đổi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($diffRows as $row)
                                                                <tr>
                                                                    <td class="font-weight-bold">{{ $row['label'] }}</td>
                                                                    <td class="cr-diff-old">{{ $row['old'] }}</td>
                                                                    <td class="cr-diff-new">{{ $row['new'] }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif

                                            @if (! empty($displayItem['apply_error']))
                                                <div class="small text-danger mt-2">Lỗi áp dụng: {{ $displayItem['apply_error'] }}</div>
                                            @endif

                                            @if ($itemStatus === 'applied' && $slot)
                                                <div class="small text-success mt-2">
                                                    Thuc te hien tai:
                                                    Ngay {{ optional($slot->date)->format('d/m/Y') ?? '-' }},
                                                    {{ $formatDayOfWeek($slot->day_of_week ?? '-') }},
                                                    Tiet {{ $slot->period_number ?? '-' }}.
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </details>
                    </td>
                    <td>
                        @php
                            $isHolidayReschedule = ($changeRequest->change_type ?? 'general') === 'holiday_reschedule';
                            $isSelfHolidayRequest = $isHolidayReschedule
                                && $changeRequest->requested_by
                                && (int) $changeRequest->requested_by === (int) (auth()->id() ?? 0);
                            $canReviewThisRequest = (! $isHolidayReschedule || (auth()->user()?->isAdmin() ?? false))
                                && ! $isSelfHolidayRequest;
                        @endphp

                        @if ($canReviewWorkflow && $changeRequest->status === 'pending' && $canReviewThisRequest)
                            <div class="d-flex flex-column">
                                <form method="POST"
                                    action="{{ route('change-request.review', $changeRequest->id) }}"
                                    class="mb-2">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="apply_changes" value="0">
                                    <div class="form-group mb-2">
                                        <label class="mb-1 small font-weight-bold">Chế độ áp dụng thay đổi</label>
                                        <select name="apply_mode" class="form-control form-control-sm">
                                            <option value="all_or_none" @selected(($changeRequest->apply_mode ?? 'all_or_none') === 'all_or_none')>all_or_none - Lỗi 1 mục thì rollback tất cả</option>
                                            <option value="best_effort" @selected(($changeRequest->apply_mode ?? 'all_or_none') === 'best_effort')>best_effort - Áp dụng tối đa, mục lỗi sẽ bỏ qua</option>
                                        </select>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox"
                                            name="apply_changes" value="1" checked
                                            id="apply-{{ $changeRequest->id }}">
                                        <label class="form-check-label" for="apply-{{ $changeRequest->id }}">
                                            Áp dụng thay đổi vào lịch khi phê duyệt
                                        </label>
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="comment"
                                            placeholder="Nhận xét phê duyệt (optional)">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-success">UC6 Phê duyệt</button>
                                        </div>
                                    </div>
                                </form>

                                <form method="POST"
                                    action="{{ route('change-request.review', $changeRequest->id) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="reason"
                                            required placeholder="Lý do từ chối (required)">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-danger">UC6 Từ chối</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @elseif ($canReviewWorkflow && $changeRequest->status === 'pending' && ! $canReviewThisRequest)
                            @if ($isSelfHolidayRequest)
                                <span class="text-muted">Khong duoc tu phe duyet phieu doi lich nghi le/tet do chinh ban tao</span>
                            @else
                                <span class="text-muted">Chi Admin duoc phe duyet phieu doi lich nghi le/tet</span>
                            @endif
                        @elseif (!$canReviewWorkflow)
                            <span class="text-muted">Không có quyền thao tác</span>
                        @else
                            <span class="text-muted">Không có thao tác phù hợp</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Chưa có phiếu đề nghị thay đổi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
