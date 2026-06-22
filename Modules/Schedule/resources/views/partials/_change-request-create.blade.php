@php
    $changeModeOld = old('apply_mode', 'all_or_none');
    $canHolidayRescheduleRequest = auth()->user() && (auth()->user()->isTrainingOffice() || auth()->user()->isAdmin());

    $teachersById = ($teachers ?? collect())->keyBy('id');

    $slotTeacherIds = ($monthlySchedules ?? collect())
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

    $changeRequestDraftData = $monthlySchedules->map(function ($schedule) use ($teachersById, $trainingTeachersById) {
        return [
            'id' => $schedule->id,
            'label' => '#'.$schedule->id.' - '.($schedule->class_name ?? 'Lop').
                ' ('.$schedule->month.'/'.$schedule->year.')',
            'class_name' => $schedule->class_name,
            'month' => $schedule->month,
            'year' => $schedule->year,
            'slots' => $schedule->scheduleSlots->map(function ($slot) use ($teachersById, $trainingTeachersById) {
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
                    'date' => optional($slot->date)->format('Y-m-d'),
                    'period_number' => $slot->period_number,
                    'class_name' => $slot->trainingClass?->code ?? $slot->trainingClass?->name,
                    'teacher_id' => $slot->teacher_id,
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
        <h6 class="mb-2">Tao phieu de nghi thay doi moi</h6>
        <form method="POST" action="{{ route('change-request.store') }}">
            @csrf
            <input type="hidden" name="selected_slots_json" id="selectedSlotsJsonInput" value="[]">

            <div class="border rounded bg-white p-2 mb-3">
                <h6 class="mb-2">Buoc 1: Chon tiet can thay doi</h6>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Lich thang</label>
                        <select id="draftMonthlySchedule" name="monthly_schedule_id" class="form-control form-control-sm" required>
                            <option value="">-- Chon lich thang --</option>
                            @foreach ($monthlySchedules as $monthlySchedule)
                                <option value="{{ $monthlySchedule->id }}"
                                    @selected((int) old('monthly_schedule_id') === (int) $monthlySchedule->id)>
                                    #{{ $monthlySchedule->id }} - {{ $monthlySchedule->class_name }}
                                    ({{ $monthlySchedule->month }}/{{ $monthlySchedule->year }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Ngay hoc</label>
                        @include('schedule::partials._date-picker-field', [
                            'label' => '',
                            'name' => 'slot_filter_date',
                            'field' => 'slotFilterDate',
                            'displayId' => 'slotFilterDate',
                            'nativeId' => 'slotFilterDateNative',
                            'value' => '',
                            'inputClass' => 'form-control-sm',
                            'wrapperClass' => 'mb-0',
                            'buttonLabel' => 'Lich',
                        ])
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Tiet hoc</label>
                        <select id="slotFilterPeriod" class="form-control form-control-sm">
                            <option value="">Tat ca tiet</option>
                            @for ($period = 1; $period <= 9; $period++)
                                <option value="{{ $period }}">Tiet {{ $period }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Lop (tim nhanh)</label>
                        <input type="text" id="slotFilterClass" class="form-control form-control-sm" placeholder="Nhap ma/ten lop">
                    </div>
                    <div class="col-md-8 mb-2 d-flex align-items-end justify-content-end gap-2">
                        <span id="selectedSlotSummary" class="badge badge-info mr-2">Da chon 0 tiet</span>
                        <button type="button" class="btn btn-sm btn-outline-primary mr-2" id="clearSlotFilterBtn">Bo loc</button>
                        <button type="button" class="btn btn-sm btn-primary" id="openChangeEditorBtn">Tao phieu tu tiet da chon</button>
                    </div>
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered" id="draftSlotTable">
                        <thead>
                            <tr>
                                <th style="width: 45px;">Chon</th>
                                <th>Ngay</th>
                                <th>Tiet</th>
                                <th>Lop</th>
                                <th>Mon/Bai hoc</th>
                                <th>Giang vien hien tai</th>
                                <th>Theo ke hoach</th>
                                <th>Phong hien tai</th>
                            </tr>
                        </thead>
                        <tbody id="draftSlotTableBody">
                            <tr>
                                <td colspan="8" class="text-muted text-center">Chon lich thang de hien thi tiet hoc.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal fade" id="changeRequestModal" tabindex="-1" role="dialog" aria-labelledby="changeRequestModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changeRequestModalLabel">Buoc 2: Chinh sua chi tiet truoc khi tao phieu</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="changeEditorSection" class="border rounded bg-white p-2">
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="mb-1">Che do ap dung khi duyet</label>
                                        <select name="apply_mode" class="form-control form-control-sm">
                                            <option value="all_or_none" @selected($changeModeOld === 'all_or_none')>all_or_none</option>
                                            <option value="best_effort" @selected($changeModeOld === 'best_effort')>best_effort</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8 mb-2">
                                        <label class="mb-1">Ly do de nghi thay doi</label>
                                        <input type="text" name="reason" class="form-control form-control-sm"
                                            value="{{ old('reason') }}" maxlength="1000"
                                            placeholder="Nhap ly do tao phieu de nghi" required>
                                    </div>
                                </div>

                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered" id="selectedSlotEditorTable">
                                        <thead>
                                            <tr>
                                                <th>Ngay</th>
                                                <th>Tiet</th>
                                                <th>Lop</th>
                                                <th>Mon/Bai hoc</th>
                                                <th>Giang vien moi</th>
                                                <th>Bai hoc moi</th>
                                                <th>Noi dung moi</th>
                                                <th>Phong moi</th>
                                                <th>Ghi chu moi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="selectedSlotEditorTableBody">
                                            <tr>
                                                <td colspan="9" class="text-muted text-center">Chua co tiet duoc chon.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div id="slotChangePreview" class="border rounded bg-light p-2 mb-2">
                                    <div class="text-muted">Chua co thay doi hop le de hien thi doi chieu truoc/sau.</div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <small class="text-muted mr-auto" id="modalSelectedSlotSummary">Da chon 0 tiet</small>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Dong</button>
                            <button type="submit" class="btn btn-sm btn-primary">Tao phieu thay doi</button>
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
            const subjectLessons = @json($subjectLessonsJs)

            const scheduleSelect = document.getElementById('draftMonthlySchedule');
            const filterPeriod = document.getElementById('slotFilterPeriod');
            const filterClass = document.getElementById('slotFilterClass');
            const clearBtn = document.getElementById('clearSlotFilterBtn');
            const openEditorBtn = document.getElementById('openChangeEditorBtn');
            const selectedSlotSummary = document.getElementById('selectedSlotSummary');
            const modalSelectedSlotSummary = document.getElementById('modalSelectedSlotSummary');
            const changeRequestModal = document.getElementById('changeRequestModal');
            const tableBody = document.getElementById('draftSlotTableBody');
            const selectedSlotsJsonInput = document.getElementById('selectedSlotsJsonInput');
            const editorSection = document.getElementById('changeEditorSection');
            const editorTableBody = document.getElementById('selectedSlotEditorTableBody');
            const previewBox = document.getElementById('slotChangePreview');

            const selectedSlotIds = new Set();
            const draftBySlotId = new Map();

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
                            ? `${subjectLabel} | B${lesson.lesson_no}: ${lesson.title}`
                            : `B${lesson.lesson_no}: ${lesson.title}`;
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
                        const label = room.label || ('Phong #' + room.id);
                        const suffix = Number(room.id) === currentId ? ' (hien tai)' : '';
                        const selected = Number(room.id) === currentId ? ' selected' : '';
                        return `<option value="${room.id}"${selected}>${escapeHtml(label + suffix)}</option>`;
                    }))
                    .join('');
            };

            const getCurrentSchedule = () => {
                const selectedId = Number(scheduleSelect?.value || 0);
                if (!selectedId) return null;
                return (schedulesData || []).find((item) => Number(item.id) === selectedId) || null;
            };

            const getCurrentScheduleSlotMap = () => {
                const schedule = getCurrentSchedule();
                const map = new Map();
                if (!schedule || !Array.isArray(schedule.slots)) {
                    return map;
                }
                schedule.slots.forEach((slot) => {
                    map.set(Number(slot.id), slot);
                });
                return map;
            };

            const slotMatchesFilter = (slot) => {
                const dateValue = getDateFieldValue(filterDateFieldKey);
                const periodValue = filterPeriod?.value || '';
                const classKeyword = (filterClass?.value || '').trim().toLowerCase();

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

                if (openEditorBtn) {
                    openEditorBtn.disabled = count === 0;
                }
            };

            const renderTable = () => {
                const schedule = getCurrentSchedule();

                if (!schedule) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-muted text-center">Chon lich thang de hien thi tiet hoc.</td></tr>';
                    selectedSlotIds.clear();
                    draftBySlotId.clear();
                    selectedSlotsJsonInput.value = '[]';
                    renderPreview();
                    updateSelectedSummary();
                    return;
                }

                const filteredSlots = (schedule.slots || []).filter(slotMatchesFilter);

                if (!filteredSlots.length) {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-muted text-center">Khong co tiet hoc nao phu hop bo loc.</td></tr>';
                    updateSelectedSummary();
                    return;
                }

                tableBody.innerHTML = filteredSlots.map((slot) => {
                    const checked = selectedSlotIds.has(Number(slot.id)) ? 'checked' : '';
                    const teacherLabel = slot.teacher_label || resolveTeacherLabelById(slot.teacher_id) || '-';

                    return `
                        <tr data-slot-id="${slot.id}">
                            <td class="text-center">
                                <input type="checkbox" class="draft-slot-check" ${checked}>
                            </td>
                            <td>${escapeHtml(slot.date || '-')}</td>
                            <td>Tiet ${escapeHtml(slot.period_number || '-')}</td>
                            <td>${escapeHtml(slot.class_name || '-')}</td>
                            <td>${escapeHtml(slot.subject || '-')}</td>
                            <td>${escapeHtml(teacherLabel)}</td>
                            <td>${escapeHtml(slot.content || '-')}</td>
                            <td>${escapeHtml(slot.room_name || '-')}</td>
                        </tr>
                    `;
                }).join('');

                bindRowEvents();
                updateSelectedSummary();
            };

            const upsertDraftFromSlot = (slot) => {
                if (!slot) {
                    return;
                }
                const slotId = Number(slot.id);
                if (!draftBySlotId.has(slotId)) {
                    draftBySlotId.set(slotId, {
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
                    editorTableBody.innerHTML = '<tr><td colspan="9" class="text-muted text-center">Chua co tiet duoc chon.</td></tr>';
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
                            <td>Tiet ${escapeHtml(slot.period_number || '-')}</td>
                            <td>${escapeHtml(slot.class_name || '-')}</td>
                            <td>${escapeHtml(slot.subject || '-')}</td>
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
                    const draft = draftBySlotId.get(slotId);

                    const teacherSelect = row.querySelector('.edit-teacher-id');
                    const lessonSelect = row.querySelector('.edit-subject-lesson-id');
                    const roomSelect = row.querySelector('.edit-room-id');

                    if (teacherSelect) {
                        teacherSelect.value = draft && draft.teacher_id !== null ? String(draft.teacher_id) : '';
                    }
                    if (lessonSelect) {
                        lessonSelect.value = draft && draft.subject_lesson_id !== null ? String(draft.subject_lesson_id) : '';
                    }
                    if (roomSelect) {
                        roomSelect.value = draft && draft.room_id !== null ? String(draft.room_id) : '';
                    }
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
                    const oldTeacherId = normalizeNullableNumber(slot.teacher_id);
                    const oldSubjectLessonId = normalizeNullableNumber(slot.subject_lesson_id);
                    const oldRoomId = normalizeNullableNumber(slot.room_id);
                    const oldContent = String(slot.content ?? '');
                    const oldNote = String(slot.note ?? '');

                    if (normalizeNullableNumber(draft.teacher_id) !== oldTeacherId) {
                        newPayload.teacher_id = normalizeNullableNumber(draft.teacher_id);
                    }
                    if (normalizeNullableNumber(draft.subject_lesson_id) !== oldSubjectLessonId) {
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

                if (!changedEntries.length) {
                    selectedSlotsJsonInput.value = '[]';
                    previewBox.innerHTML = '<div class="text-muted">Chua co thay doi hop le de hien thi doi chieu truoc/sau.</div>';
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
                        `Ngay: ${oldSlot.date || '-'}`,
                        `Tiet: ${oldSlot.period_number || '-'}`,
                        `Lop: ${oldSlot.class_name || '-'}`,
                        `Giang vien: ${oldSlot.teacher_label || resolveTeacherLabelById(oldSlot.teacher_id) || '-'}`,
                        `Noi dung: ${oldSlot.content || '-'}`,
                        `Phong: ${oldSlot.room_name || '-'}`,
                        `Ghi chu: ${oldSlot.note || '-'}`,
                    ].join('<br>');

                    const teacherLabel = resolveTeacherLabelById(item.new_payload.teacher_id);
                    const lessonTarget = (subjectLessons || []).find((lesson) => Number(lesson.id) === Number(item.new_payload.subject_lesson_id));
                    const lessonLabel = lessonTarget
                        ? `B${lessonTarget.lesson_no}: ${lessonTarget.title}`
                        : null;
                    const roomTarget = (rooms || []).find((room) => Number(room.id) === Number(item.new_payload.room_id));

                    const newSummary = [
                        `Ngay: ${oldSlot.date || '-'}`,
                        `Tiet: ${oldSlot.period_number || '-'}`,
                        `Lop: ${oldSlot.class_name || '-'}`,
                        `Giang vien: ${teacherLabel ?? '-'}`,
                        `Bai hoc: ${lessonLabel ?? '-'}`,
                        `Noi dung: ${item.new_payload.content ?? oldSlot.content ?? '-'}`,
                        `Phong: ${roomTarget?.label ?? oldSlot.room_name ?? '-'}`,
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

                previewBox.innerHTML = `
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
            };

            const bindRowEvents = () => {
                tableBody.querySelectorAll('tr[data-slot-id]').forEach((row) => {
                    const slotId = Number(row.dataset.slotId || 0);
                    const checkbox = row.querySelector('.draft-slot-check');

                    if (!checkbox || !slotId) {
                        return;
                    }

                    checkbox.addEventListener('change', () => {
                        if (checkbox.checked) {
                            selectedSlotIds.add(slotId);
                        } else {
                            selectedSlotIds.delete(slotId);
                            draftBySlotId.delete(slotId);
                        }

                        updateSelectedSummary();
                    });
                });
            };

            const bindEditorEvents = () => {
                editorTableBody.querySelectorAll('tr[data-edit-slot-id]').forEach((row) => {
                    const slotId = Number(row.dataset.editSlotId || 0);
                    const draft = draftBySlotId.get(slotId);

                    if (!slotId || !draft) {
                        return;
                    }

                    const teacherSelect = row.querySelector('.edit-teacher-id');
                    const lessonSelect = row.querySelector('.edit-subject-lesson-id');
                    const contentInput = row.querySelector('.edit-content');
                    const roomSelect = row.querySelector('.edit-room-id');
                    const noteInput = row.querySelector('.edit-note');

                    const syncDraft = () => {
                        draft.teacher_id = normalizeNullableNumber(teacherSelect?.value ?? null);
                        draft.subject_lesson_id = normalizeNullableNumber(lessonSelect?.value ?? null);
                        draft.content = String(contentInput?.value ?? '');
                        draft.room_id = normalizeNullableNumber(roomSelect?.value ?? null);
                        draft.note = String(noteInput?.value ?? '');
                        draftBySlotId.set(slotId, draft);
                        renderPreview();
                    };

                    teacherSelect?.addEventListener('change', syncDraft);
                    lessonSelect?.addEventListener('change', syncDraft);
                    contentInput?.addEventListener('input', syncDraft);
                    roomSelect?.addEventListener('change', syncDraft);
                    noteInput?.addEventListener('input', syncDraft);
                });
            };

            scheduleSelect?.addEventListener('change', renderTable);
            filterDateInput?.addEventListener('change', renderTable);
            getDateFieldElements(filterDateFieldKey).nativeInput?.addEventListener('change', renderTable);
            filterPeriod?.addEventListener('change', renderTable);
            filterClass?.addEventListener('input', renderTable);

            openEditorBtn?.addEventListener('click', () => {
                if (!selectedSlotIds.size) {
                    alert('Ban can chon it nhat 1 tiet o Buoc 1 truoc khi tao phieu.');
                    return;
                }

                renderEditor();

                if (window.jQuery && changeRequestModal) {
                    window.jQuery(changeRequestModal).modal('show');
                }
            });

            clearBtn?.addEventListener('click', () => {
                setDateFieldValue(filterDateFieldKey, '');
                filterPeriod.value = '';
                filterClass.value = '';
                renderTable();
            });

            const createForm = scheduleSelect?.closest('form');
            createForm?.addEventListener('submit', (event) => {
                if (!selectedSlotIds.size) {
                    event.preventDefault();
                    alert('Vui long thuc hien Buoc 1 va bam "Tao phieu tu tiet da chon" truoc khi submit.');
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
                let conflictMsg = null;

                for (const slotId of selectedSlotIds) {
                    const slot = slotMap.get(Number(slotId));
                    if (!slot) continue;
                    const draft = draftBySlotId.get(Number(slotId));
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
                            `  - Tiet ${slot.period_number}, ngay ${slot.date}, lop: ${slot.class_name || ('#' + slot.id)}\n` +
                            `  - Tiet ${other.period_number}, ngay ${other.date}, lop: ${other.class_name || ('#' + other.id)}\n` +
                            `Vui long chinh sua lai truoc khi tao phieu.`;
                        break;
                    }
                    teacherSlotKeyMap.set(key, slot);
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
            renderTable();
        });
    </script>
@endif

@if ($canHolidayRescheduleRequest)
    <div class="border rounded p-3 mt-3 bg-white">
        <h6 class="mb-2">Tao phieu doi lich do nghi le/tet</h6>
        <form method="POST" action="{{ route('change-request.holiday-reschedule.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="mb-1">Lich thang</label>
                    <select name="monthly_schedule_id" class="form-control form-control-sm" required>
                        <option value="">-- Chon lich thang --</option>
                        @foreach ($monthlySchedules as $monthlySchedule)
                            <option value="{{ $monthlySchedule->id }}"
                                @selected((int) old('monthly_schedule_id') === (int) $monthlySchedule->id)>
                                #{{ $monthlySchedule->id }} - {{ $monthlySchedule->class_name }}
                                ({{ $monthlySchedule->month }}/{{ $monthlySchedule->year }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-2">
                    <label class="mb-1">Khoang ngay nghi (tu)</label>
                    @include('schedule::partials._date-picker-field', [
                        'label' => '',
                        'name' => 'holiday_start_date',
                        'field' => 'holiday_start_date',
                        'displayId' => 'holidayStartDate',
                        'nativeId' => 'holidayStartDateNative',
                        'value' => old('holiday_start_date'),
                        'inputClass' => 'form-control-sm',
                        'wrapperClass' => 'mb-0',
                        'buttonLabel' => 'Lich',
                    ])
                </div>

                <div class="col-md-4 mb-2">
                    <label class="mb-1">Khoang ngay nghi (den)</label>
                    @include('schedule::partials._date-picker-field', [
                        'label' => '',
                        'name' => 'holiday_end_date',
                        'field' => 'holiday_end_date',
                        'displayId' => 'holidayEndDate',
                        'nativeId' => 'holidayEndDateNative',
                        'value' => old('holiday_end_date'),
                        'inputClass' => 'form-control-sm',
                        'wrapperClass' => 'mb-0',
                        'buttonLabel' => 'Lich',
                    ])
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="mb-1">Danh sach ngay nghi roi rac (YYYY-MM-DD, cach nhau boi dau phay)</label>
                    <input type="text" name="holiday_dates_csv" class="form-control form-control-sm"
                        value="{{ old('holiday_dates_csv') }}"
                        placeholder="Vi du: 2026-09-02, 2026-09-03">
                    @if (($holidayCalendars ?? collect())->isNotEmpty())
                        <small class="text-muted d-block mt-1">
                            Ngay nghi dang khai bao:
                            {{ $holidayCalendars->map(fn($item) => optional($item->date)->format('Y-m-d'))->filter()->implode(', ') }}
                        </small>
                    @endif
                </div>

                <div class="col-md-3 mb-2">
                    <label class="mb-1">Ngay dich de doi lich</label>
                    @include('schedule::partials._date-picker-field', [
                        'label' => '',
                        'name' => 'target_date',
                        'field' => 'target_date',
                        'displayId' => 'targetDate',
                        'nativeId' => 'targetDateNative',
                        'value' => old('target_date'),
                        'inputClass' => 'form-control-sm',
                        'wrapperClass' => 'mb-0',
                        'buttonLabel' => 'Lich',
                        'required' => true,
                        'help' => 'Ngay bat dau tim lich thay the (thuong la ngay ngay sau khoang nghi).',
                    ])
                </div>

                <div class="col-md-3 mb-2">
                    <label class="mb-1">Che do ap dung khi duyet</label>
                    <select name="apply_mode" class="form-control form-control-sm">
                        <option value="best_effort" @selected(old('apply_mode', 'best_effort') === 'best_effort')>best_effort</option>
                        <option value="all_or_none" @selected(old('apply_mode', 'best_effort') === 'all_or_none')>all_or_none</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-10 mb-2">
                    <label class="mb-1">Ly do</label>
                    <input type="text" name="reason" class="form-control form-control-sm"
                        value="{{ old('reason') }}" maxlength="1000"
                        placeholder="Vi du: Nghi le quoc gia, doi lich hoc trong 7 ngay tiep theo" required>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-warning w-100">Tao phieu doi lich</button>
                </div>
            </div>

            <small class="text-muted d-block mt-1">
                He thong se tim ngay thay the tu ngay dich, toi da {{ (int) config('schedule.holiday_reschedule.max_shift_days', 7) }} ngay,
                bo qua T7/CN va cac ngay khong lam viec trong holiday calendar.
            </small>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            const holidayStartFieldKey = 'holiday_start_date';
            const holidayEndFieldKey = 'holiday_end_date';
            const targetDateFieldKey = 'target_date';
            const filterDateFieldKey = 'slotFilterDate';

            const filterDateInput = getDateFieldElements(filterDateFieldKey).displayInput;
            const holidayStartInput = getDateFieldElements(holidayStartFieldKey).displayInput;
            const holidayEndInput = getDateFieldElements(holidayEndFieldKey).displayInput;
            const targetDateInput = getDateFieldElements(targetDateFieldKey).displayInput;

            if (!filterDateInput || !holidayEndInput || !targetDateInput) {
                return;
            }

            const toDateString = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            const computeSuggestedTarget = () => {
                const endValue = getDateFieldValue(holidayEndFieldKey);
                const startValue = getDateFieldValue(holidayStartFieldKey);
                const baseValue = endValue || startValue;

                if (!baseValue) {
                    return null;
                }

                const base = new Date(`${baseValue}T00:00:00`);
                if (Number.isNaN(base.getTime())) {
                    return null;
                }

                base.setDate(base.getDate() + 1);
                return toDateString(base);
            };

            const maybeSuggestTargetDate = (force = false) => {
                const suggested = computeSuggestedTarget();
                if (!suggested) {
                    return;
                }

                const current = getDateFieldValue(targetDateFieldKey);
                if (force || !current || current < suggested) {
                    setDateFieldValue(targetDateFieldKey, suggested);
                }
            };

            holidayEndInput.addEventListener('change', () => maybeSuggestTargetDate(false));
            if (holidayStartInput) {
                holidayStartInput.addEventListener('change', () => maybeSuggestTargetDate(false));
            }

            filterDateInput.addEventListener('change', renderTable);
            getDateFieldElements(filterDateFieldKey).nativeInput?.addEventListener('change', renderTable);

            maybeSuggestTargetDate(false);
        });
    </script>
@endif
