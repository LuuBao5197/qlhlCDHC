@extends('layouts.dashboard')

@section('title', 'Tao lich tong quat hoc ky')

@section('content')
    @php
        $selectedTrainingBatchId = (string) old('training_batch_id', '');
        $oldRules = old('class_tab_rules', []);
        $oldGlobalEvents = old('global_semester_events', []);
        $oldClassEvents = old('class_semester_events', []);
        $hasAvailableTrainingBatches = $trainingBatches->isNotEmpty();
    @endphp

    <style>
        .box {
            border: 1px solid #dbe7f3;
            border-radius: 12px;
            background: #fff;
        }

        .box-head {
            padding: 18px 20px;
            background: linear-gradient(135deg, #0f4c81, #1677b3);
            color: #fff;
        }

        .box-body {
            padding: 20px;
        }

        .class-list {
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #dbe7f3;
            border-radius: 10px;
            padding: 12px;
        }

        .class-pane {
            display: none;
            border: 1px solid #dbe7f3;
            border-radius: 12px;
            padding: 16px;
            background: #fff;
        }

        .class-pane.active {
            display: block;
        }

        .rule-card {
            border: 1px solid #dfe8f1;
            border-radius: 10px;
            padding: 14px;
            background: #f8fbff;
        }

        .rule-card.has-error {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .weekday-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .weekday-list label {
            margin-bottom: 0;
            padding: 6px 10px;
            border: 1px solid #dbe7f3;
            border-radius: 999px;
            background: #fff;
        }

        .event-card {
            border: 1px solid #dfe8f1;
            border-radius: 10px;
            padding: 12px;
            background: #fffdf7;
        }

        .event-card.has-error {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .event-card .form-label {
            margin-bottom: .25rem;
            font-size: .82rem;
            color: #52637a;
        }

        .event-card .form-control-sm {
            border-radius: 8px;
        }

        .event-card .row {
            margin-left: -6px;
            margin-right: -6px;
        }

        .event-card .row > [class*="col-"] {
            padding-left: 6px;
            padding-right: 6px;
        }

        .quick-actions {
            position: fixed;
            top: 84px;
            left: 276px;
            right: 20px;
            z-index: 1050;
            width: auto;
            padding: 12px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #dbe7f3;
            box-shadow: 0 14px 36px rgba(15, 76, 129, 0.16);
            backdrop-filter: blur(10px);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .quick-actions .quick-title {
            font-size: .78rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6b7b8f;
            font-weight: 700;
            margin-right: 6px;
        }

        .quick-actions .btn {
            min-width: 132px;
        }

        .quick-actions .quick-spacer {
            flex: 1 1 auto;
        }

        .quick-actions .quick-label {
            font-size: .88rem;
            color: #516072;
            white-space: nowrap;
        }

        .quick-actions .quick-label strong {
            color: #0f4c81;
        }

        .quick-actions-spacer {
            height: 76px;
        }

        @media (max-width: 1200px) {
            .quick-actions {
                left: 20px;
                right: 20px;
            }
        }

        @media (max-width: 768px) {
            .quick-actions {
                top: 12px;
                border-radius: 14px;
            }

            .quick-actions-spacer {
                height: 96px;
            }

            .quick-actions .btn {
                min-width: unset;
                flex: 1 1 calc(50% - 4px);
            }

            .quick-actions .quick-spacer {
                flex-basis: 100%;
                height: 0;
            }
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="box shadow-sm">
                <div class="box-head">
                    <h4 class="mb-2">Tao ke hoach va lich tong quat hoc ky</h4>
                    <div>Moi lop can co it nhat mot rule hop le hoac mot file import Excel/CSV.</div>
                </div>

                <div class="box-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
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

                    <form action="{{ route('schedule.store') }}" method="POST" enctype="multipart/form-data"
                        id="scheduleForm">
                        @csrf

                        <div class="quick-actions shadow-sm">
                            <div class="quick-title">Menu nhanh</div>
                            <button type="button" class="btn btn-sm btn-primary" id="quickAddRuleBtn">
                                Thêm rule
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="quickAddClassEventBtn">
                                Thêm sự kiện
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="quickAddHolidayBtn">
                                Thêm nghỉ lễ
                            </button>
                            <button type="button" class="btn btn-sm btn-light border" id="quickScrollTopBtn">
                                Lên đầu
                            </button>
                            <div class="quick-spacer"></div>
                            <div class="quick-label">
                                Lớp hiện tại: <strong id="quickActiveClassLabel">Chưa chọn</strong>
                            </div>
                        </div>
                        <div class="quick-actions-spacer"></div>

                        <h5 class="mb-3">1. Thong tin hoc ky</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Khoa dao tao <span class="text-danger">*</span></label>
                                <select name="training_batch_id" id="trainingBatchSelect" class="form-control" required
                                    {{ $hasAvailableTrainingBatches ? '' : 'disabled' }}>
                                    <option value="">Chon khoa dao tao</option>
                                    @foreach ($trainingBatches as $batch)
                                        @php
                                            $programLabel = $batch->trainingProgram
                                                ? $batch->trainingProgram->code . ' - ' . $batch->trainingProgram->name
                                                : null;
                                        @endphp
                                        <option value="{{ $batch->id }}"
                                            data-class-count="{{ $batch->classes_count }}"
                                            {{ $selectedTrainingBatchId === (string) $batch->id ? 'selected' : '' }}>
                                            {{ $batch->code }} - {{ $batch->name }}
                                            @if ($programLabel)
                                                ({{ $programLabel }})
                                            @endif
                                            - {{ $batch->classes_count }} lop
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-2">Chi hien thi khoa dao tao dang hoat dong.</small>
                                @if (!$hasAvailableTrainingBatches)
                                    <div class="alert alert-warning mt-3 mb-0">
                                        Chua co khoa dao tao kha dung de tao ke hoach moi.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label">Hoc ky</label>
                                <select name="semester" class="form-control" required>
                                    <option value="1" {{ old('semester') == 1 ? 'selected' : '' }}>Hoc ky 1</option>
                                    <option value="2" {{ old('semester') == 2 ? 'selected' : '' }}>Hoc ky 2</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nam hoc</label>
                                <input type="number" name="year" class="form-control"
                                    value="{{ old('year', date('Y')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngay bat dau</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngay ket thuc</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                            </div>
                        </div>
                        <div id="planDuplicateHint" class="alert alert-warning mt-3 d-none"></div>

                        <div class="mt-3">
                            <label class="form-label">Mo ta ke hoach</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">2. Chon lop ap dung</h5>
                        <div class="row">
                            <div class="col-lg-7">
                                <div id="classBatchHint" class="alert alert-light border mb-3">
                                    Chon khoa dao tao de hien thi danh sach lop.
                                </div>
                                <input type="text" class="form-control mb-3" id="classFilterInput"
                                    placeholder="Tim theo ma hoac ten lop">
                                <div class="class-list">
                                    @foreach ($classes as $class)
                                        <div class="class-item mb-2"
                                            data-search="{{ strtolower($class->code . ' ' . $class->name) }}">
                                            <div class="form-check">
                                                <input class="form-check-input class-checkbox" type="checkbox"
                                                    value="{{ $class->id }}" disabled
                                                    data-batch-id="{{ $class->training_batch_id }}"
                                                    data-label="{{ $class->code }} - {{ $class->name }}"
                                                    data-code="{{ $class->code }}" id="class_{{ $class->id }}">
                                                <label class="form-check-label" for="class_{{ $class->id }}">
                                                    <strong>{{ $class->code }}</strong> - {{ $class->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div id="selectedClassInputs"></div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">3. Cau hinh tung lop</h5>
                            <a href="{{ route('schedule.import-template') }}" class="btn btn-sm btn-outline-secondary">
                                Tai file mau
                            </a>
                        </div>
                        <div id="classTabs" class="d-flex flex-wrap mb-3"></div>
                        <div id="classTabContent">
                            <div id="noClassSelectedMsg" class="alert alert-light border">
                                Chon it nhat mot lop o tren de them rule hoac upload file import.
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">4. Su kien chung</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addGlobalHolidayBtn">
                                Them nghi le
                            </button>
                        </div>
                        <div class="text-muted small mb-3">
                            Chi dung cho su kien ap dung cho tat ca cac lop, vi du nghi le. Su kien nay chi can
                            nhap mot lan va se hien thi tren tat ca tab lop.
                        </div>
                        <div id="globalHolidayEventList"></div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary px-4"
                                {{ $hasAvailableTrainingBatches ? '' : 'disabled' }}>Tao ke hoach</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <datalist id="subject-suggestions">
        @foreach ($subjectSuggestions as $subject)
            <option value="{{ $subject['code'] }}">{{ $subject['name'] }}</option>
        @endforeach
    </datalist>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weekdayOptions = @json($weekdayOptions);
            const oldRules = @json($oldRules);
            const oldGlobalEvents = @json($oldGlobalEvents);
            const oldClassEvents = @json($oldClassEvents);
            const existingPlanKeys = new Set(@json($existingPlanKeys));
            const importUrl = @json(route('schedule.import-template'));
            const globalEventTypes = @json($globalEventTypes);
            const classEventTypes = @json($classEventTypes);
            const tabs = document.getElementById('classTabs');
            const content = document.getElementById('classTabContent');
            const emptyMsg = document.getElementById('noClassSelectedMsg');
            const filter = document.getElementById('classFilterInput');
            const batchSelect = document.getElementById('trainingBatchSelect');
            const classBatchHint = document.getElementById('classBatchHint');
            const selectedClassInputs = document.getElementById('selectedClassInputs');
            const planDuplicateHint = document.getElementById('planDuplicateHint');
            const semesterInput = document.querySelector('select[name="semester"]');
            const yearInput = document.querySelector('input[name="year"]');
            const planStartInput = document.querySelector('input[name="start_date"]');
            const planEndInput = document.querySelector('input[name="end_date"]');
            const checkboxes = Array.from(document.querySelectorAll('.class-checkbox'));
            const form = document.getElementById('scheduleForm');
            const submitButton = form.querySelector('button[type="submit"]');
            const globalHolidayEventList = document.getElementById('globalHolidayEventList');
            const addGlobalHolidayBtn = document.getElementById('addGlobalHolidayBtn');
            const quickAddRuleBtn = document.getElementById('quickAddRuleBtn');
            const quickAddClassEventBtn = document.getElementById('quickAddClassEventBtn');
            const quickAddHolidayBtn = document.getElementById('quickAddHolidayBtn');
            const quickScrollTopBtn = document.getElementById('quickScrollTopBtn');
            const quickActiveClassLabel = document.getElementById('quickActiveClassLabel');
            const counters = {};
            const eventCounters = { global: 0, classes: {} };
            let activeClassKey = null;

            const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            const safe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '_');
            const tabId = (k) => `tab-${safe(k)}`;
            const paneId = (k) => `pane-${safe(k)}`;
            const rulesId = (k) => `rules-${safe(k)}`;
            const classEventsId = (k) => `class-events-${safe(k)}`;
            const labelOf = (k) => {
                const cb = checkboxes.find((item) => item.value === String(k));
                if (cb) return cb.dataset.label;
                return String(k);
            };

            const activate = (k) => {
                activeClassKey = String(k);
                tabs.querySelectorAll('[data-key]').forEach((btn) => {
                    btn.className =
                        `btn btn-sm mr-2 mb-2 ${btn.dataset.key === String(k) ? 'btn-primary' : 'btn-outline-primary'}`;
                });
                content.querySelectorAll('.class-pane').forEach((pane) => pane.classList.toggle('active', pane
                    .dataset.key === String(k)));
                syncQuickActions();
            };

            const scrollToEl = (el) => {
                if (!el) return;
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            const syncQuickActions = () => {
                const hasActiveClass = Boolean(activeClassKey && document.getElementById(paneId(activeClassKey)));
                if (quickAddRuleBtn) quickAddRuleBtn.disabled = !hasActiveClass;
                if (quickAddClassEventBtn) quickAddClassEventBtn.disabled = !hasActiveClass;
                if (quickActiveClassLabel) {
                    quickActiveClassLabel.textContent = hasActiveClass ? labelOf(activeClassKey) : 'Chua chon';
                }
            };

            const updateEmpty = () => {
                emptyMsg.style.display = tabs.children.length ? 'none' : 'block';
            };

            const updateRuleEmpty = (k) => {
                const wrap = document.getElementById(rulesId(k));
                if (!wrap) return;
                let hint = wrap.querySelector('.rule-empty');
                if (!hint) {
                    hint = document.createElement('div');
                    hint.className = 'alert alert-light border rule-empty mb-0';
                    hint.textContent = 'Chua co rule. Ban co the them rule tay hoac chi upload file import.';
                    wrap.appendChild(hint);
                }
                hint.style.display = wrap.querySelectorAll('.rule-card').length ? 'none' : 'block';
            };

            const clearRuleErrors = () => {
                content.querySelectorAll('.rule-card').forEach((card) => {
                    card.classList.remove('has-error');
                    const errorNode = card.querySelector('.rule-error');
                    if (errorNode) errorNode.remove();
                });
            };

            const appendRuleError = (card, message) => {
                card.classList.add('has-error');
                let errorNode = card.querySelector('.rule-error');
                if (!errorNode) {
                    errorNode = document.createElement('div');
                    errorNode.className = 'rule-error text-danger small mt-2';
                    card.appendChild(errorNode);
                }

                const messages = errorNode.dataset.messages ? errorNode.dataset.messages.split('||') : [];
                if (!messages.includes(message)) {
                    messages.push(message);
                    errorNode.dataset.messages = messages.join('||');
                    errorNode.innerHTML = messages.map((item) => `<div>${esc(item)}</div>`).join('');
                }
            };

            const parseRule = (card) => {
                const startDate = card.querySelector('input[name$="[start_date]"]')?.value || '';
                const endDate = card.querySelector('input[name$="[end_date]"]')?.value || '';
                const periodFrom = Number(card.querySelector('input[name$="[period_from]"]')?.value || '');
                const periodTo = Number(card.querySelector('input[name$="[period_to]"]')?.value || '');
                const weekdays = Array.from(card.querySelectorAll('input[name*="[weekdays]"]:checked')).map((
                    input) => Number(input.value));

                if (!startDate || !endDate || !periodFrom || !periodTo || weekdays.length === 0) {
                    return null;
                }

                if (periodFrom > periodTo) {
                    return null;
                }

                return {
                    startDate,
                    endDate,
                    periodFrom,
                    periodTo,
                    weekdays
                };
            };

            const formatDateKey = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            const uiDayOfWeek = (date) => {
                const jsDay = date.getDay();
                return jsDay === 0 ? 8 : jsDay + 1;
            };

            const selectedPlanKey = () => {
                const batchId = batchSelect ? String(batchSelect.value || '') : '';
                const semester = semesterInput ? String(semesterInput.value || '') : '';
                const year = yearInput ? String(yearInput.value || '') : '';

                return batchId && semester && year ? `${batchId}|${semester}|${year}` : null;
            };

            const updatePlanDuplicateHint = () => {
                const key = selectedPlanKey();
                const duplicated = key ? existingPlanKeys.has(key) : false;

                if (planDuplicateHint) {
                    if (duplicated) {
                        planDuplicateHint.classList.remove('d-none');
                        planDuplicateHint.textContent =
                            `Khoa dao tao nay da co ke hoach hoc ky ${semesterInput.value} nam ${yearInput.value}.`;
                    } else {
                        planDuplicateHint.classList.add('d-none');
                        planDuplicateHint.textContent = '';
                    }
                }

                return duplicated;
            };

            const validateRuleConflicts = () => {
                clearRuleErrors();

                let hasConflict = false;
                content.querySelectorAll('.class-pane').forEach((pane) => {
                    const slotOwners = new Map();
                    const classLabel = pane.querySelector('h6')?.textContent || 'lop';

                    pane.querySelectorAll('.rule-card').forEach((card) => {
                        const parsed = parseRule(card);
                        if (!parsed) {
                            return;
                        }

                        const start = new Date(`${parsed.startDate}T00:00:00`);
                        const end = new Date(`${parsed.endDate}T00:00:00`);
                        for (const cursor = new Date(start); cursor <= end; cursor.setDate(
                                cursor.getDate() + 1)) {
                            if (!parsed.weekdays.includes(uiDayOfWeek(cursor))) {
                                continue;
                            }

                            const dateKey = formatDateKey(cursor);
                            for (let period = parsed.periodFrom; period <= parsed
                                .periodTo; period++) {
                                const slotKey = `${dateKey}|${period}`;
                                if (slotOwners.has(slotKey)) {
                                    hasConflict = true;
                                    appendRuleError(card,
                                        `Trung lich voi rule khac trong ${classLabel} tai ${dateKey}, tiet ${period}.`
                                    );
                                    appendRuleError(slotOwners.get(slotKey),
                                        `Trung lich voi rule khac trong ${classLabel} tai ${dateKey}, tiet ${period}.`
                                    );
                                } else {
                                    slotOwners.set(slotKey, card);
                                }
                            }
                        }
                    });
                });

                const batchReady = batchSelect ? !batchSelect.disabled && Boolean(batchSelect.value) : true;
                const hasSelectedClass = selectedClassInputs
                    ? selectedClassInputs.querySelectorAll('input[name="selected_class_ids[]"]').length > 0
                    : checkboxes.some((cb) => cb.checked);
                const hasDuplicatePlan = updatePlanDuplicateHint();
                submitButton.disabled = hasConflict || !batchReady || !hasSelectedClass || hasDuplicatePlan;
                return !hasConflict && batchReady && hasSelectedClass && !hasDuplicatePlan;
            };

            const weekdayHtml = (k, i, picked) => weekdayOptions.map((d) => {
                const checked = Array.isArray(picked) && picked.map(String).includes(String(d.value)) ?
                    'checked' : '';
                return `<label><input type="checkbox" name="class_tab_rules[${esc(k)}][${i}][weekdays][]" value="${d.value}" ${checked}> ${esc(d.label)}</label>`;
            }).join('');

            const addRule = (k, data = {}) => {
                const box = document.getElementById(rulesId(k));
                const i = counters[k] ?? 0;
                const startDate = data.start_date || planStartInput?.value || '';
                const endDate = data.end_date || planEndInput?.value || '';
                counters[k] = i + 1;
                const div = document.createElement('div');
                div.className = 'rule-card mb-3';
                div.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong>Rule</strong>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-rule">Xoa</button>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><label class="form-label">Tu ngay</label><input type="date" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][start_date]" value="${esc(startDate)}"></div>
                        <div class="col-md-3"><label class="form-label">Den ngay</label><input type="date" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][end_date]" value="${esc(endDate)}"></div>
                        <div class="col-md-3"><label class="form-label">Tiet bat dau</label><input type="number" min="1" max="9" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][period_from]" value="${esc(data.period_from || '')}"></div>
                        <div class="col-md-3"><label class="form-label">Tiet ket thuc</label><input type="number" min="1" max="9" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][period_to]" value="${esc(data.period_to || '')}"></div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6"><label class="form-label">Mon hoc</label><input type="text" list="subject-suggestions" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][subject]" value="${esc(data.subject || '')}"></div>
                        <div class="col-md-6"><label class="form-label">Noi dung</label><input type="text" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][content]" value="${esc(data.content || '')}"></div>
                    </div>
                    <div class="mt-3"><label class="form-label d-block">Thu hoc</label><div class="weekday-list">${weekdayHtml(k, i, data.weekdays || [])}</div></div>
                `;
                box.appendChild(div);
                updateRuleEmpty(k);
                validateRuleConflicts();
                scrollToEl(div);
                const firstInput = div.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus({ preventScroll: true });
            };

            const eventMeta = (type, list) => list.find((item) => String(item.value) === String(type));
            const eventLabel = (type, list) => eventMeta(type, list)?.label || 'Su kien';
            const eventColor = (type, list) => eventMeta(type, list)?.color || '#ede9fe';
            const eventCount = (listEl) => listEl ? listEl.querySelectorAll('.event-card').length : 0;

            const updateEventEmpty = (listEl, message) => {
                if (!listEl) return;
                const hint = listEl.querySelector('.event-empty');
                if (eventCount(listEl) === 0) {
                    if (!hint) {
                        const empty = document.createElement('div');
                        empty.className = 'alert alert-light border event-empty mb-0';
                        empty.textContent = message;
                        listEl.appendChild(empty);
                    }
                } else if (hint) {
                    hint.remove();
                }
            };

            const clearEventErrors = (listEl) => {
                listEl?.querySelectorAll('.event-card').forEach((card) => {
                    card.classList.remove('has-error');
                    const errorNode = card.querySelector('.event-error');
                    if (errorNode) errorNode.remove();
                });
            };

            const appendEventError = (card, message) => {
                card.classList.add('has-error');
                let errorNode = card.querySelector('.event-error');
                if (!errorNode) {
                    errorNode = document.createElement('div');
                    errorNode.className = 'event-error text-danger small mt-2';
                    card.appendChild(errorNode);
                }

                const messages = errorNode.dataset.messages ? errorNode.dataset.messages.split('||') : [];
                if (!messages.includes(message)) {
                    messages.push(message);
                    errorNode.dataset.messages = messages.join('||');
                    errorNode.innerHTML = messages.map((item) => `<div>${esc(item)}</div>`).join('');
                }
            };

            const parseEvent = (card) => {
                const eventType = card.querySelector('select[name*="[event_type]"]')?.value || '';
                const title = card.querySelector('input[name*="[title]"]')?.value || '';
                const startDate = card.querySelector('input[name*="[start_date]"]')?.value || '';
                const endDate = card.querySelector('input[name*="[end_date]"]')?.value || '';
                const periodFrom = Number(card.querySelector('input[name*="[period_from]"]')?.value || '');
                const periodTo = Number(card.querySelector('input[name*="[period_to]"]')?.value || '');

                if (!eventType || !title || !startDate || !endDate) {
                    return null;
                }

                return {
                    eventType,
                    title,
                    startDate,
                    endDate,
                    periodFrom: Number.isFinite(periodFrom) && periodFrom >= 1 ? periodFrom : 1,
                    periodTo: Number.isFinite(periodTo) && periodTo >= 1 ? periodTo : 9,
                };
            };

            const buildEventSlots = (event) => {
                const slots = [];
                if (!event) return slots;

                const start = new Date(`${event.startDate}T00:00:00`);
                const end = new Date(`${event.endDate}T00:00:00`);
                for (const cursor = new Date(start); cursor <= end; cursor.setDate(cursor.getDate() + 1)) {
                    const dateKey = formatDateKey(cursor);
                    for (let period = event.periodFrom; period <= event.periodTo; period++) {
                        slots.push(`${dateKey}|${period}`);
                    }
                }

                return slots;
            };

            const buildEventOptions = (types, selected) => types.map((type) => {
                const isSelected = String(type.value) === String(selected) ? 'selected' : '';
                return `<option value="${esc(type.value)}" ${isSelected}>${esc(type.label)}</option>`;
            }).join('');

            const eventBlockHtml = (types, data, index, namePrefix, defaultLabel) => {
                const eventType = data.event_type || types[0]?.value || '';
                const color = data.color || eventColor(eventType, types);
                const label = data.title || defaultLabel || eventLabel(eventType, types);

                return `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong>${esc(defaultLabel || 'Su kien')} #${index + 1}</strong>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-event">Xoa</button>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Loai su kien</label>
                            <select class="form-control form-control-sm" name="${namePrefix}[event_type]">
                                ${buildEventOptions(types, eventType)}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ten su kien</label>
                            <input type="text" class="form-control form-control-sm" name="${namePrefix}[title]" value="${esc(label)}" placeholder="Nhap ten su kien">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tu ngay</label>
                            <input type="date" class="form-control form-control-sm" name="${namePrefix}[start_date]" value="${esc(data.start_date || planStartInput?.value || '')}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Den ngay</label>
                            <input type="date" class="form-control form-control-sm" name="${namePrefix}[end_date]" value="${esc(data.end_date || planEndInput?.value || '')}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-2">
                            <label class="form-label">Tiet bat dau</label>
                            <input type="number" min="1" max="9" class="form-control form-control-sm" name="${namePrefix}[period_from]" value="${esc(data.period_from || 1)}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tiet ket thuc</label>
                            <input type="number" min="1" max="9" class="form-control form-control-sm" name="${namePrefix}[period_to]" value="${esc(data.period_to || 9)}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ghi chu</label>
                            <input type="text" class="form-control form-control-sm" name="${namePrefix}[note]" value="${esc(data.note || '')}">
                        </div>
                    </div>
                    <input type="hidden" name="${namePrefix}[color]" value="${esc(color)}">
                    <div class="mt-2 small text-muted">Mau hien thi: <span class="badge badge-light" style="background:${esc(color)};">${esc(eventLabel(eventType, types))}</span></div>
                `;
            };

            const addGlobalHoliday = (data = {}) => {
                if (!globalHolidayEventList) return;
                const index = eventCounters.global;
                eventCounters.global += 1;
                const card = document.createElement('div');
                card.className = 'event-card mb-3';
                card.innerHTML = eventBlockHtml(
                    globalEventTypes,
                    data,
                    index,
                    `global_semester_events[${index}]`,
                    'Nghi le'
                );
                globalHolidayEventList.appendChild(card);
                updateEventEmpty(globalHolidayEventList, 'Chua co su kien nghi le nao.');
                scrollToEl(card);
                const firstInput = card.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus({ preventScroll: true });
            };

            const addClassEvent = (classKey, data = {}) => {
                const listEl = document.getElementById(classEventsId(classKey));
                if (!listEl) return;
                const index = eventCounters.classes[classKey] ?? 0;
                eventCounters.classes[classKey] = index + 1;
                const card = document.createElement('div');
                card.className = 'event-card mb-3';
                card.innerHTML = eventBlockHtml(
                    classEventTypes,
                    data,
                    index,
                    `class_semester_events[${classKey}][${index}]`,
                    'Su kien lop'
                );
                listEl.appendChild(card);
                updateEventEmpty(listEl, 'Chua co su kien nao trong lop nay.');
                scrollToEl(card);
                const firstInput = card.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus({ preventScroll: true });
            };

            const validateEventsInList = (listEl) => {
                clearEventErrors(listEl);
                let valid = true;
                const seenSlots = new Map();

                listEl?.querySelectorAll('.event-card').forEach((card) => {
                    const parsed = parseEvent(card);
                    if (!parsed) {
                        valid = false;
                        appendEventError(card, 'Vui long dien day du thong tin su kien.');
                        return;
                    }

                    if (parsed.periodFrom > parsed.periodTo) {
                        valid = false;
                        appendEventError(card, 'Tiet ket thuc phai lon hon hoac bang tiet bat dau.');
                        return;
                    }

                    buildEventSlots(parsed).forEach((slotKey) => {
                        if (!seenSlots.has(slotKey)) {
                            seenSlots.set(slotKey, card);
                            return;
                        }

                        const otherCard = seenSlots.get(slotKey);
                        if (otherCard !== card) {
                            valid = false;
                            const conflictMessage = `Bi trung ngay/tiet tai ${slotKey.replace('|', ', tiet ')}.`;
                            appendEventError(card, conflictMessage);
                            appendEventError(otherCard, conflictMessage);
                        }
                    });
                });

                return valid;
            };

            const eventsOverlap = (first, second) => {
                const firstStart = new Date(`${first.startDate}T00:00:00`);
                const firstEnd = new Date(`${first.endDate}T00:00:00`);
                const secondStart = new Date(`${second.startDate}T00:00:00`);
                const secondEnd = new Date(`${second.endDate}T00:00:00`);

                return firstStart <= secondEnd &&
                    secondStart <= firstEnd &&
                    Math.max(first.periodFrom, second.periodFrom) <= Math.min(first.periodTo, second.periodTo);
            };

            const validateAllSemesterEvents = () => {
                const classListEls = Object.keys(eventCounters.classes)
                    .map((classKey) => document.getElementById(classEventsId(classKey)))
                    .filter(Boolean);

                let valid = validateEventsInList(globalHolidayEventList);
                classListEls.forEach((listEl) => {
                    valid = validateEventsInList(listEl) && valid;
                });

                const globalCards = Array.from(globalHolidayEventList?.querySelectorAll('.event-card') || []);
                const classCardsByList = classListEls.map((listEl) => ({
                    cards: Array.from(listEl.querySelectorAll('.event-card')),
                }));

                globalCards.forEach((globalCard) => {
                    const globalEvent = parseEvent(globalCard);
                    if (!globalEvent) {
                        return;
                    }

                    classCardsByList.forEach(({ cards }) => {
                        cards.forEach((classCard) => {
                            const classEvent = parseEvent(classCard);
                            if (!classEvent) {
                                return;
                            }

                            if (eventsOverlap(globalEvent, classEvent)) {
                                valid = false;
                                const conflictMessage =
                                    `Su kien chung '${globalEvent.title}' bi trung ngay/tiet voi su kien lop '${classEvent.title}'.`;
                                appendEventError(globalCard, conflictMessage);
                                appendEventError(classCard, conflictMessage);
                            }
                        });
                    });
                });

                return valid;
            };

            const hydrate = (k) => {
                const pane = document.getElementById(paneId(k));
                if (!pane || pane.dataset.hydrated === '1') return;
                if (Array.isArray(oldRules[k])) oldRules[k].forEach((r) => addRule(k, r));
                if (Array.isArray(oldClassEvents[k])) {
                    oldClassEvents[k].forEach((event) => addClassEvent(k, event));
                }
                pane.dataset.hydrated = '1';
            };

            const ensurePane = (k) => {
                if (!document.getElementById(tabId(k))) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.id = tabId(k);
                    btn.className = 'btn btn-sm btn-outline-primary mr-2 mb-2';
                    btn.dataset.key = String(k);
                    btn.textContent = labelOf(k);
                    tabs.appendChild(btn);

                    const pane = document.createElement('div');
                    pane.id = paneId(k);
                    pane.className = 'class-pane';
                    pane.dataset.key = String(k);
                    pane.innerHTML = `
                        <div class="pane-toolbar mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${esc(labelOf(k))}</h6>
                                <small class="text-muted">Them rule tay hoac upload Excel/CSV rieng cho lop nay.</small>
                            </div>
                                <div class="pane-actions">
                                    <button type="button" class="btn btn-sm btn-primary add-rule" data-key="${esc(k)}">Them rule</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary add-class-event" data-key="${esc(k)}">Them su kien</button>
                                </div>
                            </div>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Su kien cua lop</strong>
                            </div>
                            <small class="text-muted d-block mb-2">Dung cho on thi, thi hoac su kien rieng cua lop nay.</small>
                            <div id="${classEventsId(k)}"></div>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Import Excel/CSV</strong>
                                <a href="${esc(importUrl)}" class="btn btn-sm btn-outline-secondary">Tai template</a>
                            </div>
                            <input type="file" name="import_file[${esc(k)}]" class="form-control form-control-sm" accept=".xlsx,.csv,.txt">
                            <small class="text-muted">Cot chinh: row_type, class_code, start_date, end_date, period_from, period_to. Dong rule dung them subject/content/weekdays; dong event dung event_type/title/note.</small>
                        </div>
                        <div id="${rulesId(k)}"></div>
                    `;
                    content.appendChild(pane);
                    counters[k] = 0;
                    eventCounters.classes[k] = eventCounters.classes[k] ?? 0;
                } else {
                    document.getElementById(tabId(k)).textContent = labelOf(k);
                    const title = document.querySelector(`#${paneId(k)} h6`);
                    if (title) title.textContent = labelOf(k);
                }
                hydrate(k);
                updateRuleEmpty(k);
                updateEmpty();
                if (tabs.children.length === 1) activate(k);
            };

            const removePane = (k) => {
                const btn = document.getElementById(tabId(k));
                const pane = document.getElementById(paneId(k));
                const active = btn && btn.classList.contains('btn-primary');
                if (btn) btn.remove();
                if (pane) pane.remove();
                delete counters[k];
                delete eventCounters.classes[k];
                updateEmpty();
                if (tabs.children.length === 0) {
                    activeClassKey = null;
                    syncQuickActions();
                }
                validateRuleConflicts();
                if (active && tabs.firstElementChild) activate(tabs.firstElementChild.dataset.key);
            };

            const hydrateSemesterEvents = () => {
                const globalValues = Array.isArray(oldGlobalEvents) ? oldGlobalEvents : Object.values(oldGlobalEvents || {});
                globalValues.forEach((event) => {
                    if (event && typeof event === 'object') {
                        addGlobalHoliday(event);
                    }
                });

                Object.keys(oldClassEvents || {}).forEach((classKey) => {
                    const classValues = Array.isArray(oldClassEvents[classKey])
                        ? oldClassEvents[classKey]
                        : Object.values(oldClassEvents[classKey] || {});
                    classValues.forEach((event) => {
                        if (event && typeof event === 'object') {
                            addClassEvent(classKey, event);
                        }
                    });
                });

                updateEventEmpty(globalHolidayEventList, 'Chua co su kien nghi le nao.');
                Object.keys(eventCounters.classes).forEach((classKey) => {
                    updateEventEmpty(document.getElementById(classEventsId(classKey)), 'Chua co su kien nao trong lop nay.');
                });
            };

            const syncChecks = () => {
                checkboxes.forEach((cb) => cb.checked ? ensurePane(cb.value) : removePane(cb.value));
            };

            const syncSelectedClassInputs = () => {
                if (!selectedClassInputs) return;

                selectedClassInputs.innerHTML = '';

                checkboxes
                    .filter((cb) => cb.checked)
                    .forEach((cb) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_class_ids[]';
                        input.value = cb.value;
                        selectedClassInputs.appendChild(input);
                    });
            };

            const applyClassFilters = () => {
                const batchId = batchSelect ? String(batchSelect.value || '') : '';
                const q = filter ? filter.value.trim().toLowerCase() : '';
                let batchClassCount = 0;
                let visibleClassCount = 0;

                checkboxes.forEach((cb) => {
                    const item = cb.closest('.class-item');
                    const belongsToBatch = batchId !== '' && String(cb.dataset.batchId || '') === batchId;
                    const matchesSearch = item ? item.dataset.search.includes(q) : true;

                    if (belongsToBatch) {
                        batchClassCount++;
                    }

                    if (item) {
                        item.style.display = belongsToBatch && matchesSearch ? '' : 'none';
                    }

                    cb.disabled = true;
                    cb.checked = belongsToBatch;

                    if (belongsToBatch) {
                        ensurePane(cb.value);
                    } else {
                        removePane(cb.value);
                    }

                    if (belongsToBatch && matchesSearch) {
                        visibleClassCount++;
                    }
                });

                if (classBatchHint) {
                    classBatchHint.className = 'alert border mb-3 ' + (batchId ? 'alert-info' : 'alert-light');

                    if (!batchId) {
                        classBatchHint.textContent = 'Chon khoa dao tao de hien thi danh sach lop.';
                    } else if (batchClassCount === 0) {
                        classBatchHint.className = 'alert alert-warning border mb-3';
                        classBatchHint.textContent = 'Khoa dao tao nay chua co lop nao.';
                    } else if (visibleClassCount === 0) {
                        classBatchHint.textContent = 'Khong co lop nao khop voi tu khoa tim kiem.';
                    } else {
                        classBatchHint.textContent =
                            `Da tu dong chon tat ca ${batchClassCount} lop thuoc khoa dao tao. Dang hien thi ${visibleClassCount} lop.`;
                    }
                }

                syncSelectedClassInputs();
                updateEmpty();
                validateRuleConflicts();
            };

            if (filter) {
                filter.addEventListener('input', applyClassFilters);
            }

            if (batchSelect) {
                batchSelect.addEventListener('change', function() {
                    applyClassFilters();
                    syncChecks();
                    validateRuleConflicts();
                });
            }

            if (semesterInput) {
                semesterInput.addEventListener('change', validateRuleConflicts);
            }

            if (yearInput) {
                yearInput.addEventListener('input', validateRuleConflicts);
                yearInput.addEventListener('change', validateRuleConflicts);
            }

            checkboxes.forEach((cb) => cb.addEventListener('click', function(e) {
                e.preventDefault();
            }));

            tabs.addEventListener('click', function(e) {
                const btn = e.target.closest('[data-key]');
                if (btn) activate(btn.dataset.key);
            });

            content.addEventListener('click', function(e) {
                const add = e.target.closest('.add-rule');
                if (add) return addRule(add.dataset.key);
                const addClassEventBtn = e.target.closest('.add-class-event');
                if (addClassEventBtn) return addClassEvent(addClassEventBtn.dataset.key);
                const removeEvent = e.target.closest('.remove-event');
                if (removeEvent) {
                    const card = removeEvent.closest('.event-card');
                    if (card) {
                        const listEl = card.parentElement;
                        const emptyMessage = listEl === globalHolidayEventList
                            ? 'Chua co su kien nghi le nao.'
                            : 'Chua co su kien nao trong lop nay.';
                        card.remove();
                        updateEventEmpty(listEl, emptyMessage);
                        validateAllSemesterEvents();
                    }
                    return;
                }
                const remove = e.target.closest('.remove-rule');
                if (!remove) return;
                const card = remove.closest('.event-card');
                if (card) {
                    const listEl = card.parentElement;
                    card.remove();
                    updateEventEmpty(listEl, 'Chua co su kien nao trong lop nay.');
                    validateAllSemesterEvents();
                    return;
                }
                const pane = remove.closest('.class-pane');
                if (!pane) return;
                remove.closest('.rule-card').remove();
                updateRuleEmpty(pane.dataset.key);
                validateRuleConflicts();
            });

            content.addEventListener('input', validateRuleConflicts);
            content.addEventListener('change', validateRuleConflicts);
            globalHolidayEventList?.addEventListener('click', function(e) {
                const removeEvent = e.target.closest('.remove-event');
                if (!removeEvent) return;

                const card = removeEvent.closest('.event-card');
                if (!card) return;

                const listEl = card.parentElement;
                card.remove();
                updateEventEmpty(listEl, 'Chua co su kien nghi le nao.');
                validateAllSemesterEvents();
            });
            globalHolidayEventList?.addEventListener('input', () => validateAllSemesterEvents());
            globalHolidayEventList?.addEventListener('change', () => validateAllSemesterEvents());
            addGlobalHolidayBtn?.addEventListener('click', () => {
                addGlobalHoliday();
                validateAllSemesterEvents();
            });
            quickAddRuleBtn?.addEventListener('click', () => {
                if (!activeClassKey) return;
                addRule(activeClassKey);
            });
            quickAddClassEventBtn?.addEventListener('click', () => {
                if (!activeClassKey) return;
                addClassEvent(activeClassKey);
            });
            quickAddHolidayBtn?.addEventListener('click', () => {
                addGlobalHoliday();
                validateAllSemesterEvents();
            });
            quickScrollTopBtn?.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            form.addEventListener('submit', function(e) {
                if (!validateRuleConflicts() || !validateAllSemesterEvents()) {
                    e.preventDefault();
                }
            });

            applyClassFilters();
            syncChecks();
            hydrateSemesterEvents();
            updateEmpty();
            validateRuleConflicts();
            validateAllSemesterEvents();
            syncQuickActions();
        });
    </script>
@endsection
