@php
    $teachersById = ($teachers ?? collect())->keyBy('id');
    $changeRequestMonthlySchedules = $changeRequestMonthlySchedules ?? ($monthlySchedules ?? collect());

    $slotTeacherIds = $changeRequestMonthlySchedules
        ->flatMap(function ($schedule) {
            return $schedule->scheduleSlots->pluck('teacher_id');
        })
        ->filter()
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    $trainingTeachersById = $slotTeacherIds->isEmpty()
        ? collect()
        : \Modules\Training\Models\Teacher::query()
            ->whereIn('id', $slotTeacherIds)
            ->get(['id', 'name', 'teacher_code'])
            ->keyBy('id');

    $monthlyScheduleBatches = ($monthlySchedules ?? collect())
        ->groupBy(fn ($schedule) => $schedule->year . '|' . $schedule->month)
        ->map(function ($schedules) {
            $anchor = $schedules->sortBy('id')->first();

            return [
                'anchor_id' => $anchor?->id,
                'month' => (int) ($anchor?->month ?? 0),
                'year' => (int) ($anchor?->year ?? 0),
                'label' => sprintf(
                    '%02d/%d - %d kế hoạch',
                    (int) ($anchor?->month ?? 0),
                    (int) ($anchor?->year ?? 0),
                    $schedules->count()
                ),
                'schedule_count' => $schedules->count(),
                'slot_count' => $schedules->sum(fn ($schedule) => $schedule->scheduleSlots->count()),
            ];
        })
        ->sortByDesc(fn ($batch) => sprintf('%04d-%02d', $batch['year'], $batch['month']))
        ->values();

    $selectedMonthlyScheduleId = (int) old('monthly_schedule_id', $changeRequestMonthlySchedules->first()?->id ?? 0);
    $selectedMonthlySchedule = $changeRequestMonthlySchedules->firstWhere('id', $selectedMonthlyScheduleId);
    $selectedBatchMonth = $selectedMonthlySchedule?->month;
    $selectedBatchYear = $selectedMonthlySchedule?->year;

    $changeRequestDraftData = $changeRequestMonthlySchedules->map(function ($schedule) use ($teachersById, $trainingTeachersById) {
        return [
            'id' => $schedule->id,
            'label' => '#'.$schedule->id.' - '.($schedule->class_name ?? 'Lớp').
                ' ('.$schedule->month.'/'.$schedule->year.')',
            'class_name' => $schedule->class_name,
            'plan_label' => $schedule->plan?->name
                ?? $schedule->plan?->title
                ?? ($schedule->plan ? ('Ke hoach #' . $schedule->plan->id) : 'Ke hoach'),
            'month' => $schedule->month,
            'year' => $schedule->year,
            'slots' => $schedule->scheduleSlots->map(function ($slot) use ($schedule, $teachersById, $trainingTeachersById) {
                $slotTeacherId = (int) ($slot->teacher_id ?? 0);
                $userTeacher = $slotTeacherId > 0 ? $teachersById->get($slotTeacherId) : null;
                $trainingTeacher = $slotTeacherId > 0 ? $trainingTeachersById->get($slotTeacherId) : null;

                $teacherName = $slot->teacher?->name
                    ?? $userTeacher?->name
                    ?? $trainingTeacher?->name;

                $teacherCode = $slot->teacher?->employee_code
                    ?? $slot->teacher?->teacher_code
                    ?? $userTeacher?->employee_code
                    ?? $trainingTeacher?->teacher_code;

                $teacherLabel = $teacherName
                    ? trim($teacherName.' '.($teacherCode ? '('.$teacherCode.')' : ''))
                    : ($slotTeacherId > 0 ? ('ID '.$slotTeacherId) : null);

                return [
                    'id' => $slot->id,
                    'monthly_schedule_id' => $schedule->id,
                    'schedule_label' => '#'.$schedule->id.' - '.($schedule->class_name ?? 'Lop'),
                    'plan_label' => $schedule->plan?->name
                        ?? $schedule->plan?->title
                        ?? ($schedule->plan ? ('Ke hoach #' . $schedule->plan->id) : 'Ke hoach'),
                    'date' => optional($slot->date)->format('Y-m-d'),
                    'period_number' => $slot->period_number,
                    'class_name' => $slot->trainingClass?->code ?? $slot->trainingClass?->name,
                    'teacher_id' => $slot->teacher_id,
                    'assignment_type' => $slot->assignment_type,
                    'teacher_name' => $teacherName,
                    'teacher_code' => $teacherCode,
                    'teacher_label' => $teacherLabel,
                    'subject_lesson_id' => $slot->subject_lesson_id,
                    'subject_id' => $slot->subject_id,
                    'subject' => $slot->subject ?? $slot->subjectModel?->name,
                    'content' => $slot->content,
                    'room_id' => $slot->room_id,
                    'room_name' => $slot->room?->code ?? $slot->room?->name,
                    'note' => $slot->note,
                    'schedule_slot_group_id' => is_numeric($slot->schedule_slot_group_id) ? (int) $slot->schedule_slot_group_id : null,
                    'schedule_slot_group_status' => $slot->scheduleSlotGroup?->status,
                    'is_merged_group' => $slot->scheduleSlotGroup?->status === 'active'
                        && is_numeric($slot->schedule_slot_group_id),
                ];
            })->values()->all(),
        ];
    })->values();

    $roomsJs = ($rooms ?? collect())->map(function ($room) {
        return [
            'id' => $room->id,
            'label' => trim(($room->code ?? '').' '.($room->name ?? '')),
        ];
    })->values()->all();

    $teachersJs = ($teachers ?? collect())->map(function ($teacher) {
        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'employee_code' => $teacher->employee_code,
        ];
    })->values()->all();

    $subjectLessonsJs = ($subjectLessons ?? collect())->map(function ($lesson) {
        return [
            'id' => $lesson->id,
            'subject_id' => $lesson->subject_id,
            'lesson_no' => $lesson->lesson_no,
            'title' => $lesson->title,
            'subject_code' => $lesson->subject?->code,
            'subject_name' => $lesson->subject?->name,
        ];
    })->values()->all();
@endphp

@if ($canDepartmentAssign)
    <div class="border rounded p-3 mt-3 bg-light">
        <h6 class="mb-2">Tạo phiếu đề nghị thay đổi mới</h6>
        <form method="POST" action="{{ route('change-request.store') }}">
            @csrf
            <input type="hidden" name="selected_slots_json" id="selectedSlotsJsonInput" value="[]">

            <div class="border rounded bg-white p-2 mb-3">
                <h6 class="mb-2">Bước 1: Chọn tiết cần thay đổi</h6>
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="Change request tabs">
                        <button type="button" class="btn btn-primary" data-change-tab="aggregate">Theo thang tong hop</button>
                        <button type="button" class="btn btn-outline-primary" data-change-tab="class-plan">Theo lop / ke hoach</button>
                        <button type="button" class="btn btn-outline-primary" data-change-tab="selected">Da chon & ra soat</button>
                    </div>
                    <div class="small text-muted mt-2 mt-md-0" id="currentTabHint">
                        Quet nhanh toan bo slot trong thang, sau do khoanh bang bo loc ngang.
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Tháng tổng hợp</label>
                        <select id="draftMonthlySchedule" name="monthly_schedule_id" class="form-control form-control-sm" required>
                            <option value="">-- Chọn tháng tổng hợp --</option>
                            @foreach ($monthlyScheduleBatches as $batch)
                                <option value="{{ $batch['anchor_id'] }}"
                                    @selected((int) $selectedBatchMonth === (int) $batch['month'] && (int) $selectedBatchYear === (int) $batch['year'])>
                                    {{ $batch['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Ngày học</label>
                        @include('schedule::partials._date-picker-field', [
                            'label' => '',
                            'name' => 'slot_filter_date',
                            'field' => 'slotFilterDate',
                            'displayId' => 'slotFilterDate',
                            'nativeId' => 'slotFilterDateNative',
                            'value' => '',
                            'inputClass' => 'form-control-sm',
                            'wrapperClass' => 'mb-0',
                            'buttonLabel' => 'Lịch',
                        ])
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Tiết học</label>
                        <select id="slotFilterPeriod" class="form-control form-control-sm">
                            <option value="">Tất cả tiết</option>
                            @for ($period = 1; $period <= 9; $period++)
                                <option value="{{ $period }}">Tiết {{ $period }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Lớp (tìm nhanh)</label>
                        <input type="text" id="slotFilterClass" class="form-control form-control-sm" placeholder="Nhập mã/tên lớp">
                    </div>
                    <div class="col-md-8 mb-2 d-flex align-items-end justify-content-end gap-2">
                        <span id="selectedSlotSummary" class="badge badge-info mr-2">Đã chọn 0 tiết</span>
                        <button type="button" class="btn btn-sm btn-outline-primary mr-2" id="clearSlotFilterBtn">Bỏ lọc</button>
                        <button type="button" class="btn btn-sm btn-primary" id="openChangeEditorBtn">Tạo phiếu từ tiết đã chọn</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Mon hoc</label>
                        <input type="text" id="slotFilterSubject" class="form-control form-control-sm" placeholder="Nhap mon hoc">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Giang vien</label>
                        <input type="text" id="slotFilterTeacher" class="form-control form-control-sm" placeholder="Nhap ten/ma GV">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Loai phan cong</label>
                        <select id="slotFilterAssignmentType" class="form-control form-control-sm">
                            <option value="">Tat ca</option>
                            <option value="assigned">Co giang vien</option>
                            <option value="self_study">Lop tu nghien cuu</option>
                            <option value="unassigned">Chua phan cong</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Ke hoach / nhom lich</label>
                        <select id="slotFilterPlan" class="form-control form-control-sm">
                            <option value="">Tat ca ke hoach</option>
                        </select>
                    </div>
                </div>

                <div data-change-pane="aggregate" class="change-request-pane">
                    <div class="small text-muted mb-2">
                        Xem tat ca tiet trong thang da chon, ket hop bo loc ngang de khoanh nhanh cac slot can sua.
                    </div>
                </div>

                <div data-change-pane="class-plan" class="change-request-pane d-none">
                    <div class="small text-muted mb-2">
                        Tap trung theo lop hoac ke hoach. Thuong phu hop khi da biet nhom slot can dieu chinh.
                    </div>
                </div>

                <div data-change-pane="slot-browser" class="change-request-pane">
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered" id="draftSlotTable">
                        <thead>
                            <tr>
                                <th style="width: 45px;">Chọn</th>
                                <th>Ngày</th>
                                <th>Tiết</th>
                                <th>Lớp</th>
                                <th>Môn học</th>
                                <th>Bài học</th>
                                <th>Giảng viên hiện tại</th>
                                <th>Theo kế hoạch</th>
                                <th>Phòng hiện tại</th>
                            </tr>
                        </thead>
                        <tbody id="draftSlotTableBody">
                            <tr>
                                <td colspan="9" class="text-muted text-center">Chọn lịch tháng để hiển thị tiết học.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>

                <div data-change-pane="selected" class="change-request-pane d-none">
                    <div class="border rounded bg-white p-2 mt-2">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <div>
                                <div class="font-weight-bold">Danh sach tiet da chon</div>
                                <div class="small text-muted">Ra soat nhanh truoc khi mo trinh chinh sua chi tiet.</div>
                            </div>
                            <div class="mt-2 mt-sm-0">
                                <span id="selectedSlotSummaryInline" class="badge badge-info mr-2">Da chon 0 tiet</span>
                                <button type="button" class="btn btn-sm btn-primary" id="openChangeEditorBtnInline">Mo trinh chinh sua</button>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Ngay</th>
                                        <th>Tiet</th>
                                        <th>Lop</th>
                                        <th>Mon hoc</th>
                                        <th>Giang vien</th>
                                        <th>Ke hoach</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedSlotReviewTableBody">
                                    <tr>
                                        <td colspan="6" class="text-muted text-center">Chua co tiet duoc chon.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div id="selectedSlotPreviewInline" class="border rounded bg-light p-2">
                            <div class="text-muted">Chua co thay doi hop le de hien thi doi chieu truoc/sau.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="changeRequestModal" tabindex="-1" role="dialog" aria-labelledby="changeRequestModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changeRequestModalLabel">Bước 2: Chỉnh sửa chi tiết trước khi tạo phiếu</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="changeEditorSection" class="border rounded bg-white p-2">
                                <div class="row">
                                    <input type="hidden" name="apply_mode" value="all_or_none">
                                    <div class="col-md-12 mb-2">
                                        <label class="mb-1">Lý do đề nghị thay đổi</label>
                                        <input type="text" name="reason" class="form-control form-control-sm"
                                            value="{{ old('reason') }}" maxlength="1000"
                                            placeholder="Nhập lý do tạo phiếu đề nghị" required>
                                    </div>
                                </div>

                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered" id="selectedSlotEditorTable">
                                        <thead>
                                            <tr>
                                                <th>Ngày</th>
                                                <th>Tiết</th>
                                                <th>Lớp</th>
                                                <th>Môn học</th>
                                                <th>Phạm vi</th>
                                                <th>Loại phân công</th>
                                                <th>Giảng viên mới</th>
                                                <th>Bài học mới</th>
                                                <th>Nội dung mới</th>
                                                <th>Phòng mới</th>
                                                <th>Ghi chú mới</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedSlotEditorTableBody">
                                            <tr>
                                                <td colspan="11" class="text-muted text-center">Chưa có tiết được chọn.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div id="slotChangePreview" class="border rounded bg-light p-2 mb-2">
                                    <div class="text-muted">Chưa có thay đổi hợp lệ để hiển thị đối chiếu trước/sau.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <small class="text-muted mr-auto" id="modalSelectedSlotSummary">Đã chọn 0 tiết</small>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-sm btn-primary">Tạo phiếu thay đổi</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const schedulesData = @json($changeRequestDraftData);
            const rooms = @json($roomsJs);
            const teachers = @json($teachersJs);
            const subjectLessons = @json($subjectLessonsJs);
            // During testing we keep past slots visible; future-only filtering can be restored later.
            const showPastSlots = true;

            const scheduleSelect = document.getElementById('draftMonthlySchedule');
            const filterPeriod = document.getElementById('slotFilterPeriod');
            const filterClass = document.getElementById('slotFilterClass');
            const filterSubject = document.getElementById('slotFilterSubject');
            const filterTeacher = document.getElementById('slotFilterTeacher');
            const filterAssignmentType = document.getElementById('slotFilterAssignmentType');
            const filterPlan = document.getElementById('slotFilterPlan');
            const clearBtn = document.getElementById('clearSlotFilterBtn');
            const openEditorBtn = document.getElementById('openChangeEditorBtn');
            const openEditorBtnInline = document.getElementById('openChangeEditorBtnInline');
            const selectedSlotSummary = document.getElementById('selectedSlotSummary');
            const selectedSlotSummaryInline = document.getElementById('selectedSlotSummaryInline');
            const modalSelectedSlotSummary = document.getElementById('modalSelectedSlotSummary');
            const changeRequestModal = document.getElementById('changeRequestModal');
            const tableBody = document.getElementById('draftSlotTableBody');
            const selectedSlotsJsonInput = document.getElementById('selectedSlotsJsonInput');
            const editorSection = document.getElementById('changeEditorSection');
            const editorTableBody = document.getElementById('selectedSlotEditorTableBody');
            const previewBox = document.getElementById('slotChangePreview');
            const selectedReviewTableBody = document.getElementById('selectedSlotReviewTableBody');
            const selectedPreviewInline = document.getElementById('selectedSlotPreviewInline');
            const tabButtons = Array.from(document.querySelectorAll('[data-change-tab]'));
            const tabHint = document.getElementById('currentTabHint');
            const tabPanes = {
                aggregate: document.querySelector('[data-change-pane="aggregate"]'),
                classPlan: document.querySelector('[data-change-pane="class-plan"]'),
                selected: document.querySelector('[data-change-pane="selected"]'),
                slotBrowser: document.querySelector('[data-change-pane="slot-browser"]'),
            };

            const selectedSlotIds = new Set();
            const draftBySlotId = new Map();
            let activeTab = 'aggregate';
            const splitFromMergedGroupAction = 'split_from_merged_group';

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const normalizeNullableNumber = (value) => {
                if (value === '' || value === null || value === undefined) {
                    return null;
                }
                const parsed = Number(value);
                return Number.isNaN(parsed) ? null : parsed;
            };

            const getActiveGroupId = (slot) => {
                if (!slot || String(slot.schedule_slot_group_status || '') !== 'active') {
                    return null;
                }

                const groupId = normalizeNullableNumber(slot.schedule_slot_group_id);
                return groupId && groupId > 0 ? groupId : null;
            };

            const getDateFieldElements = (fieldKey) => {
                const wrapper = document.querySelector(`[data-field="${fieldKey}"]`);
                if (!wrapper) return {};
                return {
                    wrapper,
                    displayInput: wrapper.querySelector('[data-date-display]'),
                    nativeInput: wrapper.querySelector('[data-date-native]'),
                };
            };

            const getDateFieldValue = (fieldKey) => {
                return getDateFieldElements(fieldKey).nativeInput?.value || '';
            };

            const setDateFieldValue = (fieldKey, value) => {
                if (window.ScheduleDatePicker?.setValue) {
                    return window.ScheduleDatePicker.setValue(fieldKey, value);
                }

                const { displayInput, nativeInput } = getDateFieldElements(fieldKey);
                if (!displayInput || !nativeInput) return false;

                displayInput.value = value || '';
                nativeInput.value = value || '';
                displayInput.dispatchEvent(new Event('change', { bubbles: true }));
                nativeInput.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            };

            const filterDateFieldKey = 'slotFilterDate';
            const filterDateInput = getDateFieldElements(filterDateFieldKey).displayInput;

            const buildTeacherOptionsHtml = (currentTeacherId) => {
                const currentId = normalizeNullableNumber(currentTeacherId);
                return ['<option value="">-- Xoa giang vien --</option>']
                    .concat((teachers || []).map((teacher) => {
                        const label = teacher.employee_code
                            ? `${teacher.name} (${teacher.employee_code})`
                            : teacher.name;
                        const suffix = Number(teacher.id) === currentId ? ' (hien tai)' : '';
                        const selected = Number(teacher.id) === currentId ? ' selected' : '';
                        return `<option value="${teacher.id}"${selected}>${escapeHtml(label + suffix)}</option>`;
                    }))
                    .join('');
            };

            const buildAssignmentTypeOptionsHtml = (currentType) => {
                const currentValue = String(currentType || '');
                const options = [
                    { value: '', label: 'Giang vien dung lop' },
                    { value: 'self_study', label: 'Lop tu nghien cuu' },
                ];

                return options.map((option) => {
                    const selected = option.value === currentValue ? ' selected' : '';
                    return `<option value="${option.value}"${selected}>${escapeHtml(option.label)}</option>`;
                }).join('');
            };

            const resolveTeacherLabelById = (teacherId) => {
                const normalizedId = normalizeNullableNumber(teacherId);
                if (!normalizedId) {
                    return null;
                }

                const teacher = (teachers || []).find((item) => Number(item.id) === Number(normalizedId));
                if (teacher) {
                    return teacher.employee_code
                        ? `${teacher.name} (${teacher.employee_code})`
                        : teacher.name;
                }

                return `ID ${normalizedId}`;
            };

            const resolveAssignmentTypeLabel = (assignmentType) => {
                const value = String(assignmentType || '').trim();
                if (!value) {
                    return 'Giang vien dung lop';
                }

                if (value === 'self_study') {
                    return 'Lop tu nghien cuu';
                }

                return value;
            };

            const resolveLessonLabelById = (lessonId) => {
                const normalizedId = normalizeNullableNumber(lessonId);
                if (!normalizedId) {
                    return null;
                }

                const lesson = (subjectLessons || []).find((item) => Number(item.id) === Number(normalizedId));
                if (lesson) {
                    return `B${lesson.lesson_no ?? '?'}: ${lesson.title}`;
                }

                return `ID ${normalizedId}`;
            };

            const buildLessonOptionsHtml = (currentLessonId, slotSubjectId) => {
                const currentId = normalizeNullableNumber(currentLessonId);
                const subjectId = normalizeNullableNumber(slotSubjectId);
                const filtered = (subjectLessons || []).filter(
                    (lesson) => !subjectId || Number(lesson.subject_id) === subjectId
                );
                return ['<option value="">-- Xoa bai hoc --</option>']
                    .concat(filtered.map((lesson) => {
                        const subjectLabel = [lesson.subject_code, lesson.subject_name]
                            .filter((item) => !!item)
                            .join(' - ');
                        const base = subjectLabel
                            ? `${subjectLabel} | B${lesson.lesson_no ?? '?'}: ${lesson.title}`
                            : `B${lesson.lesson_no ?? '?'}: ${lesson.title}`;
                        const suffix = Number(lesson.id) === currentId ? ' (hien tai)' : '';
                        const selected = Number(lesson.id) === currentId ? ' selected' : '';
                        return `<option value="${lesson.id}"${selected}>${escapeHtml(base + suffix)}</option>`;
                    }))
                    .join('');
            };

            const buildRoomOptionsHtml = (currentRoomId) => {
                const currentId = normalizeNullableNumber(currentRoomId);
                return ['<option value="">-- Xoa phong hoc --</option>']
                    .concat((rooms || []).map((room) => {
                        const label = room.label || ('Phòng #' + room.id);
                        const suffix = Number(room.id) === currentId ? ' (hien tai)' : '';
                        const selected = Number(room.id) === currentId ? ' selected' : '';
                        return `<option value="${room.id}"${selected}>${escapeHtml(label + suffix)}</option>`;
                    }))
                    .join('');
            };

            const getSelectedBatch = () => {
                const selectedId = Number(scheduleSelect?.value || 0);
                if (!selectedId) return null;

                const anchor = (schedulesData || []).find((item) => Number(item.id) === selectedId) || null;
                if (!anchor) {
                    return null;
                }

                const month = Number(anchor.month || 0);
                const year = Number(anchor.year || 0);

                return {
                    anchor,
                    month,
                    year,
                    schedules: (schedulesData || []).filter((item) => Number(item.month) === month && Number(item.year) === year),
                };
            };

            const getCurrentScheduleSlotMap = () => {
                const batch = getSelectedBatch();
                const map = new Map();
                if (!batch || !Array.isArray(batch.schedules)) {
                    return map;
                }

                batch.schedules.forEach((schedule) => {
                    (schedule.slots || []).forEach((slot) => {
                        map.set(Number(slot.id), slot);
                    });
                });
                return map;
            };

            const getRelatedGroupSlotIds = (slotId) => {
                const slotMap = getCurrentScheduleSlotMap();
                const slot = slotMap.get(Number(slotId));
                const groupId = getActiveGroupId(slot);

                if (!groupId) {
                    return [Number(slotId)];
                }

                return Array.from(slotMap.values())
                    .filter((item) => getActiveGroupId(item) === groupId)
                    .map((item) => Number(item.id))
                    .filter((id) => Number.isFinite(id) && id > 0);
            };

            const getDraftTemplateForSlot = (slot) => ({
                action: null,
                assignment_type: String(slot?.assignment_type ?? ''),
                teacher_id: normalizeNullableNumber(slot?.teacher_id),
                subject_lesson_id: normalizeNullableNumber(slot?.subject_lesson_id),
                content: String(slot?.content ?? ''),
                room_id: normalizeNullableNumber(slot?.room_id),
                note: String(slot?.note ?? ''),
            });

            const isSplitFromMergedGroupDraft = (draft) => {
                return String(draft?.action || '') === splitFromMergedGroupAction;
            };

            const copyDraft = (draft) => ({
                action: isSplitFromMergedGroupDraft(draft) ? splitFromMergedGroupAction : null,
                assignment_type: String(draft?.assignment_type ?? ''),
                teacher_id: normalizeNullableNumber(draft?.teacher_id),
                subject_lesson_id: normalizeNullableNumber(draft?.subject_lesson_id),
                content: String(draft?.content ?? ''),
                room_id: normalizeNullableNumber(draft?.room_id),
                note: String(draft?.note ?? ''),
            });

            const resolveEffectiveAssignmentTypeForSlot = (slot, draft) => {
                if (draft && Object.prototype.hasOwnProperty.call(draft, 'assignment_type')) {
                    return String(draft.assignment_type ?? '').trim();
                }

                const slotType = String(slot?.assignment_type ?? '').trim();

                return slotType || '';
            };

            const applyAssignmentTypeStateToRow = (row, slot, draft) => {
                if (!row) {
                    return;
                }

                const mergeActionSelect = row.querySelector('.edit-merge-action');
                const assignmentTypeSelect = row.querySelector('.edit-assignment-type');
                const teacherSelect = row.querySelector('.edit-teacher-id');
                const lessonSelect = row.querySelector('.edit-subject-lesson-id');
                const effectiveType = resolveEffectiveAssignmentTypeForSlot(slot, draft);
                const isSelfStudy = effectiveType === 'self_study';

                if (mergeActionSelect) {
                    mergeActionSelect.value = isSplitFromMergedGroupDraft(draft) ? splitFromMergedGroupAction : '';
                }

                if (assignmentTypeSelect) {
                    assignmentTypeSelect.value = String(draft?.assignment_type ?? '');
                }

                if (teacherSelect) {
                    teacherSelect.disabled = isSelfStudy;
                    if (isSelfStudy) {
                        teacherSelect.value = '';
                    } else if (draft?.teacher_id !== null && draft?.teacher_id !== undefined) {
                        teacherSelect.value = String(draft.teacher_id);
                    }
                }

                if (lessonSelect) {
                    lessonSelect.disabled = isSelfStudy;
                    if (isSelfStudy) {
                        lessonSelect.value = '';
                    } else if (draft?.subject_lesson_id !== null && draft?.subject_lesson_id !== undefined) {
                        lessonSelect.value = String(draft.subject_lesson_id);
                    }
                }
            };

            const setDraftForSlotIds = (slotIds, draft, options = {}) => {
                slotIds.forEach((slotId) => {
                    const normalizedSlotId = Number(slotId);
                    const existingDraft = draftBySlotId.get(normalizedSlotId);
                    if (!options.includeSplitRows && isSplitFromMergedGroupDraft(existingDraft) && !isSplitFromMergedGroupDraft(draft)) {
                        return;
                    }

                    draftBySlotId.set(normalizedSlotId, copyDraft(draft));
                });
            };

            const syncEditorRowsForSlotIds = (slotIds, draft, options = {}) => {
                const slotMap = getCurrentScheduleSlotMap();
                slotIds.forEach((slotId) => {
                    const row = editorTableBody?.querySelector(`[data-edit-slot-id="${Number(slotId)}"]`);
                    if (!row) {
                        return;
                    }
                    const slot = slotMap.get(Number(slotId));
                    const rowDraft = draftBySlotId.get(Number(slotId));
                    if (!options.includeSplitRows && isSplitFromMergedGroupDraft(rowDraft) && !isSplitFromMergedGroupDraft(draft)) {
                        return;
                    }

                    const contentInput = row.querySelector('.edit-content');
                    const roomSelect = row.querySelector('.edit-room-id');
                    const noteInput = row.querySelector('.edit-note');

                    applyAssignmentTypeStateToRow(row, slot, draft);

                    if (contentInput) {
                        contentInput.value = String(draft?.content ?? '');
                    }
                    if (roomSelect) {
                        roomSelect.value = draft?.room_id === null ? '' : String(draft.room_id);
                    }
                    if (noteInput) {
                        noteInput.value = String(draft?.note ?? '');
                    }
                });
            };

            const clearDraftForSlotIds = (slotIds) => {
                slotIds.forEach((slotId) => {
                    draftBySlotId.delete(Number(slotId));
                });
            };

            const syncTableCheckboxesForSlotIds = (slotIds, checked) => {
                slotIds.forEach((slotId) => {
                    const row = tableBody?.querySelector(`[data-slot-id="${Number(slotId)}"]`);
                    const checkbox = row?.querySelector('.draft-slot-check');
                    if (checkbox) {
                        checkbox.checked = checked;
                    }
                });
            };

            const seedDraftForSlotGroup = (slotId) => {
                const slotMap = getCurrentScheduleSlotMap();
                const slot = slotMap.get(Number(slotId));
                if (!slot) {
                    return;
                }

                const relatedSlotIds = getRelatedGroupSlotIds(slotId);
                const template = draftBySlotId.get(Number(slotId)) || getDraftTemplateForSlot(slot);
                setDraftForSlotIds(relatedSlotIds, template);
            };

            const syncDraftForSlotGroup = (slotId, draft) => {
                if (isSplitFromMergedGroupDraft(draft)) {
                    setDraftForSlotIds([slotId], draft, { includeSplitRows: true });
                    syncEditorRowsForSlotIds([slotId], draft, { includeSplitRows: true });
                    return;
                }

                const relatedSlotIds = getRelatedGroupSlotIds(slotId);
                setDraftForSlotIds(relatedSlotIds, draft);
                syncEditorRowsForSlotIds(relatedSlotIds, draft);
            };

            const refreshPlanFilterOptions = () => {
                if (!filterPlan) {
                    return;
                }

                const currentValue = String(filterPlan.value || '');
                const batch = getSelectedBatch();
                const options = ['<option value="">Tat ca ke hoach</option>'];

                (batch?.schedules || []).forEach((schedule) => {
                    const value = String(schedule.id || '');
                    const selected = value === currentValue ? ' selected' : '';
                    const label = schedule.plan_label || schedule.label || (`Ke hoach #${value}`);
                    options.push(`<option value="${escapeHtml(value)}"${selected}>${escapeHtml(label)}</option>`);
                });

                filterPlan.innerHTML = options.join('');

                if (currentValue && !Array.from(filterPlan.options).some((option) => option.value === currentValue)) {
                    filterPlan.value = '';
                }
            };

            const setActiveTab = (tabName) => {
                activeTab = tabName === 'selected' ? 'selected' : tabName === 'class-plan' ? 'class-plan' : 'aggregate';

                tabButtons.forEach((button) => {
                    const isActive = button.dataset.changeTab === activeTab;
                    button.classList.toggle('btn-primary', isActive);
                    button.classList.toggle('btn-outline-primary', !isActive);
                });

                if (tabPanes.aggregate) {
                    tabPanes.aggregate.classList.toggle('d-none', activeTab !== 'aggregate');
                }
                if (tabPanes.classPlan) {
                    tabPanes.classPlan.classList.toggle('d-none', activeTab !== 'class-plan');
                }
                if (tabPanes.selected) {
                    tabPanes.selected.classList.toggle('d-none', activeTab !== 'selected');
                }
                if (tabPanes.slotBrowser) {
                    tabPanes.slotBrowser.classList.toggle('d-none', activeTab === 'selected');
                }

                if (tabHint) {
                    tabHint.textContent = activeTab === 'selected'
                        ? 'Kiem tra cac slot da tick, doi chieu thay doi va mo trinh chinh sua neu can.'
                        : activeTab === 'class-plan'
                            ? 'Dung bo loc Lop va Ke hoach de khoanh nhanh nhom slot can sua.'
                            : 'Quet nhanh toan bo slot trong thang, sau do khoanh bang bo loc ngang.';
                }
            };

            const slotMatchesFilter = (slot) => {
                const dateValue = getDateFieldValue(filterDateFieldKey);
                const periodValue = filterPeriod?.value || '';
                const classKeyword = (filterClass?.value || '').trim().toLowerCase();
                const subjectKeyword = (filterSubject?.value || '').trim().toLowerCase();
                const teacherKeyword = (filterTeacher?.value || '').trim().toLowerCase();
                const assignmentTypeValue = String(filterAssignmentType?.value || '').trim();
                const planValue = String(filterPlan?.value || '').trim();

                if (dateValue && String(slot.date || '') !== dateValue) {
                    return false;
                }
                if (periodValue && String(slot.period_number || '') !== String(periodValue)) {
                    return false;
                }
                if (classKeyword) {
                    const classText = String(slot.class_name || '').toLowerCase();
                    if (!classText.includes(classKeyword)) {
                        return false;
                    }
                }
                if (subjectKeyword) {
                    const subjectText = String(slot.subject || '').toLowerCase();
                    if (!subjectText.includes(subjectKeyword)) {
                        return false;
                    }
                }
                if (teacherKeyword) {
                    const teacherText = String(slot.teacher_label || slot.teacher_name || slot.teacher_code || '').toLowerCase();
                    if (!teacherText.includes(teacherKeyword)) {
                        return false;
                    }
                }
                if (assignmentTypeValue === 'self_study' && String(slot.assignment_type || '') !== 'self_study') {
                    return false;
                }
                if (assignmentTypeValue === 'assigned' && !normalizeNullableNumber(slot.teacher_id)) {
                    return false;
                }
                if (assignmentTypeValue === 'unassigned' && (String(slot.assignment_type || '') === 'self_study' || normalizeNullableNumber(slot.teacher_id))) {
                    return false;
                }
                if (planValue && String(slot.monthly_schedule_id || '') !== planValue) {
                    return false;
                }
                return true;
            };

            const updateSelectedSummary = () => {
                const count = selectedSlotIds.size;
                const label = `Da chon ${count} tiet`;

                if (selectedSlotSummary) {
                    selectedSlotSummary.textContent = label;
                }

                if (modalSelectedSlotSummary) {
                    modalSelectedSlotSummary.textContent = label;
                }

                if (selectedSlotSummaryInline) {
                    selectedSlotSummaryInline.textContent = label;
                }

                if (openEditorBtn) {
                    openEditorBtn.disabled = count === 0;
                }

                if (openEditorBtnInline) {
                    openEditorBtnInline.disabled = count === 0;
                }
            };

            const renderSelectedReview = () => {
                if (!selectedReviewTableBody) {
                    return;
                }

                const slotMap = getCurrentScheduleSlotMap();
                const selectedSlots = Array.from(selectedSlotIds)
                    .map((slotId) => slotMap.get(Number(slotId)))
                    .filter((slot) => !!slot);

                if (!selectedSlots.length) {
                    selectedReviewTableBody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">Chua co tiet duoc chon.</td></tr>';
                    return;
                }

                selectedReviewTableBody.innerHTML = selectedSlots.map((slot) => `
                    <tr>
                        <td>${escapeHtml(slot.date || '-')}</td>
                        <td>Tiet ${escapeHtml(slot.period_number || '-')}</td>
                        <td>${escapeHtml(slot.class_name || '-')}</td>
                        <td>${escapeHtml(slot.subject || '-')}</td>
                        <td>${escapeHtml(slot.teacher_label || resolveTeacherLabelById(slot.teacher_id) || '-')}</td>
                        <td>${escapeHtml(slot.plan_label || slot.schedule_label || '-')}</td>
                    </tr>
                `).join('');
            };

            const renderTable = () => {
                const batch = getSelectedBatch();

                if (!batch) {
                    tableBody.innerHTML = '<tr><td colspan="9" class="text-muted text-center">Chọn lịch tháng để hiển thị tiết học.</td></tr>';
                    selectedSlotIds.clear();
                    draftBySlotId.clear();
                    selectedSlotsJsonInput.value = '[]';
                    renderPreview();
                    renderSelectedReview();
                    updateSelectedSummary();
                    return;
                }

                const filteredSlots = (batch.schedules || [])
                    .flatMap((schedule) => schedule.slots || [])
                    .filter(slotMatchesFilter)
                    .filter((slot) => {
                        if (showPastSlots) {
                            return true;
                        }

                        if (!slot.date) {
                            return true;
                        }

                        const slotDate = new Date(`${slot.date}T00:00:00`);
                        if (Number.isNaN(slotDate.getTime())) {
                            return true;
                        }

                        const today = new Date();
                        today.setHours(0, 0, 0, 0);

                        return slotDate >= today;
                    });

                if (!filteredSlots.length) {
                    tableBody.innerHTML = '<tr><td colspan="9" class="text-muted text-center">Khong co tiet hoc nao phu hop bo loc.</td></tr>';
                    renderSelectedReview();
                    updateSelectedSummary();
                    return;
                }

                tableBody.innerHTML = filteredSlots.map((slot) => {
                    const checked = selectedSlotIds.has(Number(slot.id)) ? 'checked' : '';
                    const teacherLabel = slot.teacher_label || resolveTeacherLabelById(slot.teacher_id) || '-';
                    const lessonLabel = resolveLessonLabelById(slot.subject_lesson_id) || '-';

                    return `
                        <tr data-slot-id="${slot.id}">
                            <td class="text-center">
                                <input type="checkbox" class="draft-slot-check" ${checked}>
                            </td>
                            <td>${escapeHtml(slot.date || '-')}</td>
                            <td>Tiết ${escapeHtml(slot.period_number || '-')}</td>
                            <td>${escapeHtml(slot.class_name || '-')}</td>
                            <td>${escapeHtml(slot.subject || '-')}</td>
                            <td>${escapeHtml(lessonLabel)}</td>
                            <td>${escapeHtml(teacherLabel)}</td>
                            <td>${escapeHtml(slot.content || '-')}</td>
                            <td>${escapeHtml(slot.room_name || '-')}</td>
                        </tr>
                    `;
                }).join('');

                bindRowEvents();
                renderSelectedReview();
                updateSelectedSummary();
            };

            const upsertDraftFromSlot = (slot) => {
                if (!slot) {
                    return;
                }
                const slotId = Number(slot.id);
                if (!draftBySlotId.has(slotId)) {
                    draftBySlotId.set(slotId, {
                        assignment_type: String(slot.assignment_type ?? ''),
                        teacher_id: normalizeNullableNumber(slot.teacher_id),
                        subject_lesson_id: normalizeNullableNumber(slot.subject_lesson_id),
                        content: String(slot.content ?? ''),
                        room_id: normalizeNullableNumber(slot.room_id),
                        note: String(slot.note ?? ''),
                    });
                }
            };

            const renderEditor = () => {
                const slotMap = getCurrentScheduleSlotMap();
                const selectedSlots = Array.from(selectedSlotIds)
                    .map((slotId) => slotMap.get(Number(slotId)))
                    .filter((slot) => !!slot);

                if (!selectedSlots.length) {
                    editorTableBody.innerHTML = '<tr><td colspan="11" class="text-muted text-center">Chua co tiet duoc chon.</td></tr>';
                    selectedSlotsJsonInput.value = '[]';
                    renderPreview();
                    return;
                }

                editorTableBody.innerHTML = selectedSlots.map((slot) => {
                    const slotId = Number(slot.id);
                    upsertDraftFromSlot(slot);
                    const draft = draftBySlotId.get(slotId);

                    const contentValue = draft ? String(draft.content ?? '') : '';
                    const noteValue = draft ? String(draft.note ?? '') : '';

                    return `
                        <tr data-edit-slot-id="${slotId}">
                            <td>${escapeHtml(slot.date || '-')}</td>
                            <td>Tiết ${escapeHtml(slot.period_number || '-')}</td>
                            <td>${escapeHtml(slot.class_name || '-')}</td>
                            <td>${escapeHtml(slot.subject || '-')}</td>
                            <td>
                                <select class="form-control form-control-sm edit-merge-action" ${slot.is_merged_group ? '' : 'disabled'}>
                                    <option value="">${slot.is_merged_group ? 'Ca nhom ghep' : 'Tiet rieng'}</option>
                                    ${slot.is_merged_group ? `<option value="${splitFromMergedGroupAction}">Tach tiet nay</option>` : ''}
                                </select>
                            </td>
                            <td>
                                <select class="form-control form-control-sm edit-assignment-type">
                                    ${buildAssignmentTypeOptionsHtml(slot.assignment_type)}
                                </select>
                            </td>
                            <td>
                                <select class="form-control form-control-sm edit-teacher-id">
                                    ${buildTeacherOptionsHtml(slot.teacher_id)}
                                </select>
                            </td>
                            <td>
                                <select class="form-control form-control-sm edit-subject-lesson-id">
                                    ${buildLessonOptionsHtml(slot.subject_lesson_id, slot.subject_id)}
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm edit-content" value="${escapeHtml(contentValue)}" placeholder="Noi dung moi">
                            </td>
                            <td>
                                <select class="form-control form-control-sm edit-room-id">
                                    ${buildRoomOptionsHtml(slot.room_id)}
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm edit-note" value="${escapeHtml(noteValue)}" placeholder="Ghi chu moi">
                            </td>
                        </tr>
                    `;
                }).join('');

                editorTableBody.querySelectorAll('tr[data-edit-slot-id]').forEach((row) => {
                    const slotId = Number(row.dataset.editSlotId || 0);
                    const slot = slotMap.get(slotId);
                    const draft = draftBySlotId.get(slotId);

                    const mergeActionSelect = row.querySelector('.edit-merge-action');
                    const assignmentTypeSelect = row.querySelector('.edit-assignment-type');
                    const teacherSelect = row.querySelector('.edit-teacher-id');
                    const lessonSelect = row.querySelector('.edit-subject-lesson-id');
                    const roomSelect = row.querySelector('.edit-room-id');

                    if (mergeActionSelect) {
                        mergeActionSelect.value = isSplitFromMergedGroupDraft(draft) ? splitFromMergedGroupAction : '';
                    }
                    if (assignmentTypeSelect) {
                        assignmentTypeSelect.value = draft && draft.assignment_type !== undefined ? String(draft.assignment_type ?? '') : '';
                    }
                    if (teacherSelect) {
                        teacherSelect.value = draft && draft.teacher_id !== null ? String(draft.teacher_id) : '';
                    }
                    if (lessonSelect) {
                        lessonSelect.value = draft && draft.subject_lesson_id !== null ? String(draft.subject_lesson_id) : '';
                    }
                    if (roomSelect) {
                        roomSelect.value = draft && draft.room_id !== null ? String(draft.room_id) : '';
                    }

                    applyAssignmentTypeStateToRow(row, slot, draft);
                });

                bindEditorEvents();
                renderPreview();
            };

            const buildChangedEntries = () => {
                const slotMap = getCurrentScheduleSlotMap();
                const changedEntries = [];

                Array.from(selectedSlotIds).forEach((slotId) => {
                    const slot = slotMap.get(Number(slotId));
                    const draft = draftBySlotId.get(Number(slotId));

                    if (!slot || !draft) {
                        return;
                    }

                    const newPayload = {};
                    const oldAssignmentType = String(slot.assignment_type ?? '');
                    const draftAssignmentType = String(draft.assignment_type ?? '');
                    const oldTeacherId = normalizeNullableNumber(slot.teacher_id);
                    const oldSubjectLessonId = normalizeNullableNumber(slot.subject_lesson_id);
                    const oldRoomId = normalizeNullableNumber(slot.room_id);
                    const oldContent = String(slot.content ?? '');
                    const oldNote = String(slot.note ?? '');

                    if (isSplitFromMergedGroupDraft(draft)) {
                        newPayload.action = splitFromMergedGroupAction;
                    }

                    if (draftAssignmentType !== oldAssignmentType) {
                        newPayload.assignment_type = draftAssignmentType || null;
                    }

                    if (draftAssignmentType === 'self_study') {
                        if (oldTeacherId !== null) {
                            newPayload.teacher_id = null;
                        }

                        if (oldSubjectLessonId !== null) {
                            newPayload.subject_lesson_id = null;
                        }
                    }

                    if (draftAssignmentType !== 'self_study' && normalizeNullableNumber(draft.teacher_id) !== oldTeacherId) {
                        newPayload.teacher_id = normalizeNullableNumber(draft.teacher_id);
                    }
                    if (draftAssignmentType !== 'self_study' && normalizeNullableNumber(draft.subject_lesson_id) !== oldSubjectLessonId) {
                        newPayload.subject_lesson_id = normalizeNullableNumber(draft.subject_lesson_id);
                    }
                    if (normalizeNullableNumber(draft.room_id) !== oldRoomId) {
                        newPayload.room_id = normalizeNullableNumber(draft.room_id);
                    }
                    if (String(draft.content ?? '') !== oldContent) {
                        newPayload.content = String(draft.content ?? '');
                    }
                    if (String(draft.note ?? '') !== oldNote) {
                        newPayload.note = String(draft.note ?? '');
                    }

                    if (Object.keys(newPayload).length > 0) {
                        changedEntries.push({
                            slot_id: Number(slotId),
                            new_payload: newPayload,
                            old_slot: slot,
                        });
                    }
                });

                return changedEntries;
            };

            const renderPreview = () => {
                const changedEntries = buildChangedEntries();
                const slotMap = getCurrentScheduleSlotMap();
                const emptyPreviewHtml = '<div class="text-muted">Chua co thay doi hop le de hien thi doi chieu truoc/sau.</div>';

                if (!changedEntries.length) {
                    selectedSlotsJsonInput.value = '[]';
                    previewBox.innerHTML = emptyPreviewHtml;
                    if (selectedPreviewInline) {
                        selectedPreviewInline.innerHTML = emptyPreviewHtml;
                    }
                    return;
                }

                selectedSlotsJsonInput.value = JSON.stringify(changedEntries.map((item) => ({
                    slot_id: item.slot_id,
                    new_payload: item.new_payload,
                })));

                const rows = changedEntries.map((item, index) => {
                    const oldSlot = slotMap.get(Number(item.slot_id));
                    if (!oldSlot) return '';

                    const oldSummary = [
                        `Ngày: ${oldSlot.date || '-'}`,
                        `Tiết: ${oldSlot.period_number || '-'}`,
                        `Lớp: ${oldSlot.class_name || '-'}`,
                        `Phạm vi: ${oldSlot.is_merged_group ? 'Nhom ghep' : 'Tiet rieng'}`,
                        `Loại phân công: ${resolveAssignmentTypeLabel(oldSlot.assignment_type)}`,
                        `Giang vien: ${oldSlot.teacher_label || resolveTeacherLabelById(oldSlot.teacher_id) || '-'}`,
                        `Bài học: ${resolveLessonLabelById(oldSlot.subject_lesson_id) || '-'}`,
                        `Noi dung: ${oldSlot.content || '-'}`,
                        `Phòng: ${oldSlot.room_name || '-'}`,
                        `Ghi chu: ${oldSlot.note || '-'}`,
                    ].join('<br>');

                    const assignmentTypeLabel = resolveAssignmentTypeLabel(item.new_payload.assignment_type);
                    const teacherLabel = Object.prototype.hasOwnProperty.call(item.new_payload, 'teacher_id')
                        ? resolveTeacherLabelById(item.new_payload.teacher_id)
                        : (oldSlot.teacher_label || resolveTeacherLabelById(oldSlot.teacher_id));
                    const lessonLabel = Object.prototype.hasOwnProperty.call(item.new_payload, 'subject_lesson_id')
                        ? resolveLessonLabelById(item.new_payload.subject_lesson_id)
                        : resolveLessonLabelById(oldSlot.subject_lesson_id);
                    const roomTarget = (rooms || []).find((room) => Number(room.id) === Number(item.new_payload.room_id));
                    const scopeLabel = item.new_payload.action === splitFromMergedGroupAction
                        ? 'Tach tiet nay khoi nhom ghep'
                        : (oldSlot.is_merged_group ? 'Ca nhom ghep' : 'Tiet rieng');

                    const newSummary = [
                        `Ngày: ${oldSlot.date || '-'}`,
                        `Tiết: ${oldSlot.period_number || '-'}`,
                        `Lớp: ${oldSlot.class_name || '-'}`,
                        `Phạm vi: ${scopeLabel}`,
                        `Loại phân công: ${assignmentTypeLabel}`,
                        `Giang vien: ${teacherLabel ?? '-'}`,
                        `Bài học: ${lessonLabel ?? '-'}`,
                        `Noi dung: ${item.new_payload.content ?? oldSlot.content ?? '-'}`,
                        `Phòng: ${roomTarget?.label ?? oldSlot.room_name ?? '-'}`,
                        `Ghi chu: ${item.new_payload.note ?? oldSlot.note ?? '-'}`,
                    ].join('<br>');

                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${escapeHtml(oldSlot.subject || '-')}</td>
                            <td>${oldSummary}</td>
                            <td>${newSummary}</td>
                        </tr>
                    `;
                }).join('');

                const previewHtml = `
                    <div class="font-weight-bold mb-2">Bang doi chieu truoc / sau khi tao phieu</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th style="width:60px;">TT</th>
                                    <th>Noi dung</th>
                                    <th>Theo ke hoach</th>
                                    <th>Thay doi thanh</th>
                                </tr>
                            </thead>
                            <tbody>${rows || '<tr><td colspan="4" class="text-muted text-center">Khong co du lieu doi chieu.</td></tr>'}</tbody>
                        </table>
                    </div>
                `;
                previewBox.innerHTML = previewHtml;
                if (selectedPreviewInline) {
                    selectedPreviewInline.innerHTML = previewHtml;
                }
            };

            const bindRowEvents = () => {
                tableBody.querySelectorAll('tr[data-slot-id]').forEach((row) => {
                    const slotId = Number(row.dataset.slotId || 0);
                    const checkbox = row.querySelector('.draft-slot-check');

                    if (!checkbox || !slotId) {
                        return;
                    }

                    checkbox.addEventListener('change', () => {
                        const relatedSlotIds = getRelatedGroupSlotIds(slotId);

                        if (checkbox.checked) {
                            relatedSlotIds.forEach((relatedSlotId) => selectedSlotIds.add(Number(relatedSlotId)));
                            seedDraftForSlotGroup(slotId);
                        } else {
                            relatedSlotIds.forEach((relatedSlotId) => selectedSlotIds.delete(Number(relatedSlotId)));
                            clearDraftForSlotIds(relatedSlotIds);
                        }

                        syncTableCheckboxesForSlotIds(relatedSlotIds, checkbox.checked);
                        renderSelectedReview();
                        renderPreview();
                        updateSelectedSummary();
                    });
                });
            };

            const bindEditorEvents = () => {
                const slotMap = getCurrentScheduleSlotMap();
                editorTableBody.querySelectorAll('tr[data-edit-slot-id]').forEach((row) => {
                    const slotId = Number(row.dataset.editSlotId || 0);
                    const slot = slotMap.get(slotId);
                    const draft = draftBySlotId.get(slotId);

                    if (!slotId || !slot || !draft) {
                        return;
                    }

                    const mergeActionSelect = row.querySelector('.edit-merge-action');
                    const teacherSelect = row.querySelector('.edit-teacher-id');
                    const assignmentTypeSelect = row.querySelector('.edit-assignment-type');
                    const lessonSelect = row.querySelector('.edit-subject-lesson-id');
                    const contentInput = row.querySelector('.edit-content');
                    const roomSelect = row.querySelector('.edit-room-id');
                    const noteInput = row.querySelector('.edit-note');

                    const syncDraft = () => {
                        const selectedAssignmentType = String(assignmentTypeSelect?.value ?? '');
                        const effectiveAssignmentType = selectedAssignmentType;
                        const isSelfStudy = effectiveAssignmentType === 'self_study';
                        const selectedMergeAction = String(mergeActionSelect?.value ?? '');

                        if (isSelfStudy) {
                            if (teacherSelect) {
                                teacherSelect.value = '';
                            }
                            if (lessonSelect) {
                                lessonSelect.value = '';
                            }
                        }

                        draft.action = selectedMergeAction === splitFromMergedGroupAction ? splitFromMergedGroupAction : null;
                        draft.assignment_type = selectedAssignmentType;
                        draft.teacher_id = isSelfStudy ? null : normalizeNullableNumber(teacherSelect?.value ?? null);
                        draft.subject_lesson_id = isSelfStudy ? null : normalizeNullableNumber(lessonSelect?.value ?? null);
                        draft.content = String(contentInput?.value ?? '');
                        draft.room_id = normalizeNullableNumber(roomSelect?.value ?? null);
                        draft.note = String(noteInput?.value ?? '');
                        if (teacherSelect) {
                            teacherSelect.disabled = isSelfStudy;
                        }
                        if (lessonSelect) {
                            lessonSelect.disabled = isSelfStudy;
                        }
                        syncDraftForSlotGroup(slotId, draft);
                        renderSelectedReview();
                        renderPreview();
                    };

                    applyAssignmentTypeStateToRow(row, slot, draft);
                    mergeActionSelect?.addEventListener('change', syncDraft);
                    assignmentTypeSelect?.addEventListener('change', syncDraft);
                    teacherSelect?.addEventListener('change', syncDraft);
                    lessonSelect?.addEventListener('change', syncDraft);
                    contentInput?.addEventListener('input', syncDraft);
                    roomSelect?.addEventListener('change', syncDraft);
                    noteInput?.addEventListener('input', syncDraft);
                });
            };

            const openEditorModal = () => {
                if (!selectedSlotIds.size) {
                    alert('Ban can chon it nhat 1 tiet o Buoc 1 truoc khi tao phieu.');
                    return;
                }

                renderEditor();

                if (window.jQuery && changeRequestModal) {
                    window.jQuery(changeRequestModal).modal('show');
                }
            };

            scheduleSelect?.addEventListener('change', () => {
                selectedSlotIds.clear();
                draftBySlotId.clear();
                refreshPlanFilterOptions();
                renderTable();
            });
            filterDateInput?.addEventListener('change', renderTable);
            getDateFieldElements(filterDateFieldKey).nativeInput?.addEventListener('change', renderTable);
            filterPeriod?.addEventListener('change', renderTable);
            filterClass?.addEventListener('input', renderTable);
            filterSubject?.addEventListener('input', renderTable);
            filterTeacher?.addEventListener('input', renderTable);
            filterAssignmentType?.addEventListener('change', renderTable);
            filterPlan?.addEventListener('change', renderTable);

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    setActiveTab(button.dataset.changeTab || 'aggregate');
                    if ((button.dataset.changeTab || '') === 'selected') {
                        renderSelectedReview();
                        renderPreview();
                    }
                });
            });

            openEditorBtn?.addEventListener('click', openEditorModal);
            openEditorBtnInline?.addEventListener('click', openEditorModal);

            clearBtn?.addEventListener('click', () => {
                setDateFieldValue(filterDateFieldKey, '');
                filterPeriod.value = '';
                filterClass.value = '';
                if (filterSubject) filterSubject.value = '';
                if (filterTeacher) filterTeacher.value = '';
                if (filterAssignmentType) filterAssignmentType.value = '';
                if (filterPlan) filterPlan.value = '';
                renderTable();
            });

            const createForm = scheduleSelect?.closest('form');
            const modalSubmitButton = changeRequestModal?.querySelector('button[type="submit"]');

            modalSubmitButton?.addEventListener('click', (event) => {
                const reasonInput = createForm?.querySelector('input[name="reason"]');
                if (reasonInput && !String(reasonInput.value || '').trim()) {
                    event.preventDefault();
                    event.stopPropagation();
                    alert('Vui long nhap ly do de nghi thay doi truoc khi tao phieu.');
                    reasonInput.focus();
                }
            });

            createForm?.addEventListener('submit', (event) => {
                if (!selectedSlotIds.size) {
                    event.preventDefault();
                    alert('Vui lòng thực hiện Bước 1 và bấm "Tạo phiếu từ tiết đã chọn" trước khi gửi.');
                    return;
                }

                const changedEntries = buildChangedEntries();
                if (!changedEntries.length) {
                    event.preventDefault();
                    alert('Can co it nhat 1 thay doi thuc su (GV, bai hoc, noi dung, phong, ghi chu) moi duoc tao phieu.');
                    return;
                }

                // Kiem tra trung giang vien: moi GV khong duoc day nhieu lop cung tiet cung ngay
                const slotMap = getCurrentScheduleSlotMap();
                const teacherSlotKeyMap = new Map();
                const checkedGroupIds = new Set();
                let conflictMsg = null;

                for (const slotId of selectedSlotIds) {
                    const slot = slotMap.get(Number(slotId));
                    if (!slot) continue;
                    const draft = draftBySlotId.get(Number(slotId));
                    const groupId = getActiveGroupId(slot);
                    const isSplitFromGroup = isSplitFromMergedGroupDraft(draft);
                    const effectiveAssignmentType = String((draft && draft.assignment_type !== undefined)
                        ? draft.assignment_type
                        : slot.assignment_type || '');

                    if (effectiveAssignmentType === 'self_study') {
                        continue;
                    }

                    if (groupId && !isSplitFromGroup && checkedGroupIds.has(groupId)) {
                        continue;
                    }

                    const effectiveTeacherId = (draft && draft.teacher_id !== null)
                        ? draft.teacher_id
                        : normalizeNullableNumber(slot.teacher_id);
                    if (!effectiveTeacherId) continue;
                    const key = `${effectiveTeacherId}|${slot.date}|${slot.period_number}`;
                    if (teacherSlotKeyMap.has(key)) {
                        const other = teacherSlotKeyMap.get(key);
                        const teacherLabel = resolveTeacherLabelById(effectiveTeacherId) || `ID ${effectiveTeacherId}`;
                        conflictMsg =
                            `Phat hien trung giang vien:\n` +
                            `  GV: ${teacherLabel}\n` +
                            `  - Tiết ${slot.period_number}, ngày ${slot.date}, lớp: ${slot.class_name || ('#' + slot.id)}\n` +
                            `  - Tiết ${other.period_number}, ngày ${other.date}, lớp: ${other.class_name || ('#' + other.id)}\n` +
                            `Vui long chinh sua lai truoc khi tao phieu.`;
                        break;
                    }
                    teacherSlotKeyMap.set(key, slot);

                    if (groupId && !isSplitFromGroup) {
                        checkedGroupIds.add(groupId);
                    }
                }

                if (conflictMsg) {
                    event.preventDefault();
                    alert(conflictMsg);
                    return;
                }

                selectedSlotsJsonInput.value = JSON.stringify(changedEntries.map((item) => ({
                    slot_id: item.slot_id,
                    new_payload: item.new_payload,
                })));
            });

            updateSelectedSummary();
            refreshPlanFilterOptions();
            setActiveTab('aggregate');
            renderTable();
        });
    </script>
@endif
