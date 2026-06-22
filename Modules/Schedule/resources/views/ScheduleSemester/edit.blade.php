@extends('layouts.dashboard')

@section('title', 'Sua lich tong quat hoc ky')

@section('content')
    @php
        $selectedClassIds = collect(old('selected_class_ids', $selectedClassIds ?? []))
            ->map(fn($id) => (string) $id)
            ->all();
        $oldRules = old('class_tab_rules', $oldRules ?? []);
        $oldGlobalEvents = old('global_semester_events', $oldGlobalEvents ?? []);
        $oldClassEvents = old('class_semester_events', $oldClassEvents ?? []);
        $minAllowedPlanDate = now()->startOfDay()->subMonth()->toDateString();
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

        .event-group-shell {
            border: 1px solid #dbe7f3;
            border-radius: 12px;
            background: #fff;
            padding: 16px;
        }

        .pane-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .date-field {
            display: flex;
            align-items: stretch;
            gap: 8px;
        }

        .date-display-input {
            background: #fff;
            min-width: 0;
        }

        .date-picker-btn {
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .btn.btn-primary {
            cursor: pointer;
        }

        .btn.btn-primary:disabled {
            cursor: not-allowed;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="box shadow-sm">
                <div class="box-head">
                    <h4 class="mb-2">Sua ke hoach va lich tong quat hoc ky</h4>
                    <div>Cap nhat ke hoach va lich cho lop. Moi lop can co it nhat mot rule hoac mot file CSV.</div>
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

                    <form action="{{ route('schedule.update', $plan->id) }}" method="POST" enctype="multipart/form-data"
                        id="scheduleForm">
                        @csrf
                        @method('PUT')

                        <h5 class="mb-3">1. Thong tin hoc ky</h5>
                        @if ($plan->trainingBatch)
                            <div class="alert alert-info">
                                Khoa dao tao: <strong>{{ $plan->trainingBatch->code }}</strong> - {{ $plan->trainingBatch->name }}
                                @if ($plan->trainingBatch->trainingProgram)
                                    ({{ $plan->trainingBatch->trainingProgram->code }} - {{ $plan->trainingBatch->trainingProgram->name }})
                                @endif
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Hoc ky</label>
                                <select name="semester" class="form-control" required>
                                    <option value="1" {{ old('semester', $plan->semester) == 1 ? 'selected' : '' }}>Hoc
                                        ky 1</option>
                                    <option value="2" {{ old('semester', $plan->semester) == 2 ? 'selected' : '' }}>Hoc
                                        ky 2</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nam hoc</label>
                                <input type="number" name="year" class="form-control"
                                    value="{{ old('year', $plan->year) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngay bat dau</label>
                                @include('schedule::partials._date-picker-field', [
                                    'name' => 'start_date',
                                    'field' => 'start_date',
                                    'value' => old('start_date', $plan->effective_from?->toDateString()),
                                    'required' => true,
                                    'min' => $minAllowedPlanDate,
                                    'buttonLabel' => 'Lich',
                                ])
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngay ket thuc</label>
                                @include('schedule::partials._date-picker-field', [
                                    'name' => 'end_date',
                                    'field' => 'end_date',
                                    'value' => old('end_date', $plan->effective_to?->toDateString()),
                                    'required' => true,
                                    'min' => $minAllowedPlanDate,
                                    'buttonLabel' => 'Lich',
                                ])
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Mo ta ke hoach</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $plan->description) }}</textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">2. Chon lop ap dung</h5>
                        <div class="row">
                            <div class="col-lg-7">
                                <input type="text" class="form-control mb-3" id="classFilterInput"
                                    placeholder="Tim theo ma hoac ten lop">
                                <div class="class-list">
                                    @foreach ($classes as $class)
                                        <div class="class-item mb-2"
                                            data-search="{{ strtolower($class->code . ' ' . $class->name) }}">
                                            <div class="form-check">
                                                <input class="form-check-input class-checkbox" type="checkbox"
                                                    name="selected_class_ids[]" value="{{ $class->id }}"
                                                    data-label="{{ $class->code }} - {{ $class->name }}"
                                                    data-code="{{ $class->code }}" id="class_{{ $class->id }}"
                                                    {{ in_array((string) $class->id, $selectedClassIds, true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="class_{{ $class->id }}">
                                                    <strong>{{ $class->code }}</strong> - {{ $class->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">3. Cau hinh tung lop</h5>
                            <a href="{{ route('schedule.import-template') }}" class="btn btn-sm btn-outline-secondary">
                                Tai file CSV mau
                            </a>
                        </div>
                        <div id="classTabs" class="d-flex flex-wrap mb-3"></div>
                        <div id="classTabContent">
                            <div id="noClassSelectedMsg" class="alert alert-light border">
                                Chon it nhat mot lop o tren de them rule hoac upload CSV.
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">4. Su kien hoc ky</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addGlobalHolidayBtn">
                                Them nghi le
                            </button>
                        </div>
                        <div class="text-muted small mb-3">
                            Nghi le ap dung cho tat ca cac lop, con on thi/thi/su kien khac nam ngay trong tung tab lop.
                        </div>
                        <div id="globalHolidayEventList" class="event-group-shell"></div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary px-4">Huy</a>
                            <button type="submit" class="btn btn-primary px-4">Cap nhat ke hoach</button>
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
            const serverErrors = @json($errors->all());
            const importUrl = @json(route('schedule.import-template'));
            const globalEventTypes = @json($globalEventTypes);
            const classEventTypes = @json($classEventTypes);
            const tabs = document.getElementById('classTabs');
            const content = document.getElementById('classTabContent');
            const emptyMsg = document.getElementById('noClassSelectedMsg');
            const filter = document.getElementById('classFilterInput');
            const planStartInput = document.querySelector('input[name="start_date"]');
            const planEndInput = document.querySelector('input[name="end_date"]');
            const checkboxes = Array.from(document.querySelectorAll('.class-checkbox'));
            const form = document.getElementById('scheduleForm');
            const submitButton = form.querySelector('button[type="submit"]');
            const globalHolidayEventList = document.getElementById('globalHolidayEventList');
            const addGlobalHolidayBtn = document.getElementById('addGlobalHolidayBtn');
            const displayDateInputs = Array.from(document.querySelectorAll('[data-date-display]'));
            const nativeDateInputs = Array.from(document.querySelectorAll('[data-date-native]'));
            const minAllowedPlanDate = @json($minAllowedPlanDate);
            const topActionBar = form.querySelector('.d-flex.justify-content-between.mt-4');
            const validationAlert = document.createElement('div');
            validationAlert.className = 'alert alert-danger d-none';
            validationAlert.id = 'ruleValidationAlert';
            form.insertBefore(validationAlert, topActionBar);
            const counters = {};
            const eventCounters = { global: 0, classes: {} };
            const classEventsId = (k) => `class-events-${safe(k)}`;

            const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            const safe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '_');
            const tabId = (k) => `tab-${safe(k)}`;
            const paneId = (k) => `pane-${safe(k)}`;
            const rulesId = (k) => `rules-${safe(k)}`;
            const formatDateDisplay = (value) => window.ScheduleDatePicker?.formatDisplay(value) || '';
            const syncDateDisplay = (fieldName) => {
                const displayInput = document.querySelector(`[data-date-display="${fieldName}"]`);
                const nativeInput = document.querySelector(`[data-date-native="${fieldName}"]`);
                if (!displayInput || !nativeInput) return;
                displayInput.value = formatDateDisplay(nativeInput.value);
            };
            const validatePlanDate = (displayInput, nativeInput) => {
                if (!displayInput || !nativeInput) return true;
                displayInput.setCustomValidity('');
                if (!nativeInput.value) {
                    displayInput.setCustomValidity('Vui lòng chọn ngày.');
                    return false;
                }
                if (nativeInput.value < minAllowedPlanDate) {
                    displayInput.setCustomValidity(`Ngày không được sớm hơn ${formatDateDisplay(minAllowedPlanDate)}.`);
                    return false;
                }
                return true;
            };
            const syncAllDateDisplays = () => {
                displayDateInputs.forEach((input) => syncDateDisplay(input.dataset.dateDisplay));
            };
            const validatePlanDates = () => {
                const start = planStartInput;
                const end = planEndInput;
                const startDisplay = document.querySelector('[data-date-display="start_date"]');
                const endDisplay = document.querySelector('[data-date-display="end_date"]');
                let valid = true;
                if (startDisplay && start && !validatePlanDate(startDisplay, start)) valid = false;
                if (endDisplay && end && !validatePlanDate(endDisplay, end)) valid = false;
                if (start && end && start.value && end.value && end.value < start.value) {
                    if (endDisplay) {
                        endDisplay.setCustomValidity('Ngay ket thuc phai lon hon hoac bang ngay bat dau.');
                    }
                    valid = false;
                }
                syncAllDateDisplays();
                window.ScheduleDatePicker?.refreshBounds(document);
                return valid;
            };
            const openNativeDatePicker = (fieldName) => {
                const displayInput = document.querySelector(`[data-date-display="${fieldName}"]`);
                if (!displayInput) return;
                if (window.jQuery && typeof window.jQuery(displayInput).datepicker === 'function') {
                    window.jQuery(displayInput).datepicker('show');
                    return;
                }
                displayInput.focus();
            };
            const labelOf = (k) => {
                const cb = checkboxes.find((item) => item.value === String(k));
                return cb ? cb.dataset.label : String(k);
            };

            const activate = (k) => {
                tabs.querySelectorAll('[data-key]').forEach((btn) => {
                    btn.className =
                        `btn btn-sm mr-2 mb-2 ${btn.dataset.key === String(k) ? 'btn-primary' : 'btn-outline-primary'}`;
                });
                content.querySelectorAll('.class-pane').forEach((pane) => pane.classList.toggle('active', pane
                    .dataset.key === String(k)));
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
                    hint.textContent = 'Chua co rule. Ban co the them rule tay hoac chi upload CSV.';
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

            const renderDateField = (label, name, value, fieldName, options = {}) => {
                const fieldOptions = {
                    label,
                    name,
                    value,
                    fieldName,
                    required: true,
                    buttonLabel: 'Lich',
                    minSource: 'start_date',
                    maxSource: 'end_date',
                    ...options,
                };

                if (window.ScheduleDatePicker?.fieldHtml) {
                    return window.ScheduleDatePicker.fieldHtml(fieldOptions);
                }

                return `
                    <div class="schedule-date-field js-schedule-date-field" data-field="${esc(fieldName)}"
                        data-min-source="${esc(fieldOptions.minSource || '')}"
                        data-max-source="${esc(fieldOptions.maxSource || '')}">
                        <label class="form-label">${esc(label)}</label>
                        <div class="schedule-date-input-group input-group">
                            <input type="text" class="form-control schedule-date-display js-schedule-date-display"
                                data-date-display="${esc(fieldName)}" placeholder="DD/MM/YYYY" value="${esc(window.ScheduleDatePicker?.formatDisplay(value) || '')}" readonly required>
                            <input type="hidden" name="${esc(name)}" data-date-native="${esc(fieldName)}" value="${esc(value || '')}">
                            <button type="button" class="btn btn-outline-secondary schedule-date-toggle"
                                data-date-picker="${esc(fieldName)}">Lich</button>
                        </div>
                    </div>
                `;
            };

            const eventBlockHtml = (types, data, index, namePrefix, defaultLabel) => {
                const eventType = data.event_type || types[0]?.value || '';
                const color = data.color || eventColor(eventType, types);
                const label = data.title || defaultLabel || eventLabel(eventType, types);
                const fieldKeyBase = safe(namePrefix);

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
                        <div class="col-md-3">${renderDateField('Tu ngay', `${namePrefix}[start_date]`, data.start_date || planStartInput?.value || '', `${fieldKeyBase}_start_date`)}</div>
                        <div class="col-md-3">${renderDateField('Den ngay', `${namePrefix}[end_date]`, data.end_date || planEndInput?.value || '', `${fieldKeyBase}_end_date`)}</div>
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

            const addGlobalHoliday = (data = {}, options = {}) => {
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
                window.ScheduleDatePicker?.init(card);
                updateEventEmpty(globalHolidayEventList, 'Chua co su kien nghi le nao.');
                if (!options.silent) {
                    scrollToEl(card);
                    const firstInput = card.querySelector('input, select, textarea');
                    if (firstInput) firstInput.focus({ preventScroll: true });
                }
            };

            const addClassEvent = (classKey, data = {}, options = {}) => {
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
                window.ScheduleDatePicker?.init(card);
                updateEventEmpty(listEl, 'Chua co su kien nao trong lop nay.');
                if (!options.silent) {
                    scrollToEl(card);
                    const firstInput = card.querySelector('input, select, textarea');
                    if (firstInput) firstInput.focus({ preventScroll: true });
                }
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
                        valid = false;
                        const message = `Trung su kien voi su kien khac tai ${slotKey}.`;
                        appendEventError(card, message);
                        appendEventError(otherCard, message);
                    });
                });

                return valid;
            };

            const eventsOverlap = (first, second) => {
                const firstStart = new Date(`${first.startDate}T00:00:00`);
                const firstEnd = new Date(`${first.endDate}T00:00:00`);
                const secondStart = new Date(`${second.startDate}T00:00:00`);
                const secondEnd = new Date(`${second.endDate}T00:00:00`);
                const periodFrom = Math.max(first.periodFrom, second.periodFrom);
                const periodTo = Math.min(first.periodTo, second.periodTo);

                return firstStart <= secondEnd && secondStart <= firstEnd && periodFrom <= periodTo;
            };

            const validateAllSemesterEvents = () => {
                let valid = true;
                const classListEls = Object.keys(eventCounters.classes)
                    .map((classKey) => document.getElementById(classEventsId(classKey)))
                    .filter(Boolean);

                if (!validateEventsInList(globalHolidayEventList)) valid = false;
                classListEls.forEach((listEl) => {
                    if (!validateEventsInList(listEl)) valid = false;
                });

                const globalCards = Array.from(globalHolidayEventList?.querySelectorAll('.event-card') || []);
                const classCardsByList = classListEls.map((listEl) => ({
                    listEl,
                    cards: Array.from(listEl.querySelectorAll('.event-card')),
                }));

                globalCards.forEach((globalCard) => {
                    const globalEvent = parseEvent(globalCard);
                    if (!globalEvent) {
                        valid = false;
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

                const hasRuleErrors = content.querySelector('.rule-card.has-error') !== null;
                submitButton.disabled = hasRuleErrors || !valid;

                return valid;
            };

            const parseDateSafe = (value) => {
                if (!value) return null;
                const date = new Date(`${value}T00:00:00`);
                return Number.isNaN(date.getTime()) ? null : date;
            };

            const validateRuleShape = (card) => {
                const startDate = card.querySelector('input[name$="[start_date]"]')?.value || '';
                const endDate = card.querySelector('input[name$="[end_date]"]')?.value || '';
                const periodFromValue = card.querySelector('input[name$="[period_from]"]')?.value || '';
                const periodToValue = card.querySelector('input[name$="[period_to]"]')?.value || '';
                const periodFrom = Number(periodFromValue);
                const periodTo = Number(periodToValue);
                const weekdays = Array.from(card.querySelectorAll('input[name*="[weekdays]"]:checked')).map((
                    input) => Number(input.value));
                const errors = [];
                const parsedStart = parseDateSafe(startDate);
                const parsedEnd = parseDateSafe(endDate);

                if (!parsedStart) errors.push('Thieu hoac sai ngay bat dau.');
                if (!parsedEnd) errors.push('Thieu hoac sai ngay ket thuc.');

                if (parsedStart && parsedEnd && parsedEnd < parsedStart) {
                    errors.push('Ngay ket thuc phai lon hon hoac bang ngay bat dau.');
                }

                if (!Number.isInteger(periodFrom) || periodFrom < 1 || periodFrom > 9) {
                    errors.push('Tiet bat dau phai nam trong khoang 1-9.');
                }

                if (!Number.isInteger(periodTo) || periodTo < 1 || periodTo > 9) {
                    errors.push('Tiet ket thuc phai nam trong khoang 1-9.');
                }

                if (Number.isInteger(periodFrom) && Number.isInteger(periodTo) && periodFrom > periodTo) {
                    errors.push('Tiet ket thuc phai lon hon hoac bang tiet bat dau.');
                }

                if (weekdays.length === 0) {
                    errors.push('Phai chon it nhat mot thu hoc.');
                }

                return {
                    valid: errors.length === 0,
                    errors,
                    data: {
                        startDate,
                        endDate,
                        periodFrom,
                        periodTo,
                        weekdays
                    }
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

            const validateRuleConflicts = () => {
                clearRuleErrors();

                let hasConflict = false;
                let hasInvalidRule = false;
                let firstErrorPaneKey = null;
                const summaryMessages = new Set();
                content.querySelectorAll('.class-pane').forEach((pane) => {
                    const slotOwners = new Map();
                    const classLabel = pane.querySelector('h6')?.textContent || 'lop';

                    pane.querySelectorAll('.rule-card').forEach((card) => {
                        const checked = validateRuleShape(card);
                        if (!checked.valid) {
                            hasInvalidRule = true;
                            checked.errors.forEach((message) => appendRuleError(card, message));
                            summaryMessages.add(`Co rule du lieu chua hop le trong ${classLabel}.`);
                            if (!firstErrorPaneKey) {
                                firstErrorPaneKey = pane.dataset.key;
                            }
                            return;
                        }

                        const parsed = checked.data;

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
                                    summaryMessages.add(`Co rule bi trung lich trong ${classLabel}.`);
                                    if (!firstErrorPaneKey) {
                                        firstErrorPaneKey = pane.dataset.key;
                                    }
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

                const hasErrors = hasConflict || hasInvalidRule;
                const eventValid = validateAllSemesterEvents();
                submitButton.disabled = hasErrors || !eventValid;

                if (hasErrors) {
                    validationAlert.classList.remove('d-none');
                    validationAlert.innerHTML = Array.from(summaryMessages)
                        .map((message) => `<div>${esc(message)}</div>`)
                        .join('');

                    if (firstErrorPaneKey) {
                        activate(firstErrorPaneKey);
                    }
                } else {
                    validationAlert.classList.add('d-none');
                    validationAlert.innerHTML = '';
                }

                return !hasErrors && eventValid;
            };

            const resolveClassKeyFromServerLabel = (serverLabel) => {
                const normalized = String(serverLabel ?? '').trim().toLowerCase();
                if (!normalized) return null;

                const match = checkboxes.find((cb) => {
                    const code = String(cb.dataset.code ?? '').trim().toLowerCase();
                    const label = String(cb.dataset.label ?? '').trim().toLowerCase();
                    return code === normalized || label === normalized || label.startsWith(`${normalized} -`);
                });

                return match ? String(match.value) : null;
            };

            const applyServerRuleErrors = () => {
                if (!Array.isArray(serverErrors) || serverErrors.length === 0) {
                    return;
                }

                const summaryMessages = new Set();
                const rulePattern = /^Dong quy tac #(\d+) cua lop '([^']+)'\s*(.*)$/i;
                let hasMappedError = false;
                let firstPaneKey = null;

                serverErrors.forEach((message) => {
                    const text = String(message ?? '').trim();
                    if (!text) {
                        return;
                    }

                    const matched = text.match(rulePattern);
                    if (!matched) {
                        summaryMessages.add(text);
                        return;
                    }

                    const ruleIndex = Math.max(0, Number(matched[1]) - 1);
                    const classLabel = matched[2];
                    const detail = matched[3] ? matched[3].trim() : text;
                    const classKey = resolveClassKeyFromServerLabel(classLabel);

                    if (!classKey) {
                        summaryMessages.add(text);
                        return;
                    }

                    ensurePane(classKey);
                    const pane = document.getElementById(paneId(classKey));
                    const cards = pane ? Array.from(pane.querySelectorAll('.rule-card')) : [];
                    const card = cards[ruleIndex] ?? null;

                    if (!card) {
                        summaryMessages.add(text);
                        return;
                    }

                    appendRuleError(card, detail);
                    hasMappedError = true;
                    summaryMessages.add(`Co loi du lieu o lop ${labelOf(classKey)}.`);
                    if (!firstPaneKey) {
                        firstPaneKey = classKey;
                    }
                });

                if (!hasMappedError && summaryMessages.size === 0) {
                    return;
                }

                submitButton.disabled = true;
                validationAlert.classList.remove('d-none');
                validationAlert.innerHTML = Array.from(summaryMessages)
                    .map((item) => `<div>${esc(item)}</div>`)
                    .join('');

                if (firstPaneKey) {
                    activate(firstPaneKey);
                }
            };

            const weekdayHtml = (k, i, picked) => weekdayOptions.map((d) => {
                const checked = Array.isArray(picked) && picked.map(String).includes(String(d.value)) ?
                    'checked' : '';
                return `<label><input type="checkbox" name="class_tab_rules[${esc(k)}][${i}][weekdays][]" value="${d.value}" ${checked}> ${esc(d.label)}</label>`;
            }).join('');

            const addRule = (k, data = {}, options = {}) => {
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
                        <div class="col-md-3">${renderDateField('Tu ngay', `class_tab_rules[${esc(k)}][${i}][start_date]`, startDate, `${safe(k)}_${i}_start_date`)}</div>
                        <div class="col-md-3">${renderDateField('Den ngay', `class_tab_rules[${esc(k)}][${i}][end_date]`, endDate, `${safe(k)}_${i}_end_date`)}</div>
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
                if (!options.silent) {
                    scrollToEl(div);
                    const firstInput = div.querySelector('input, select, textarea');
                    if (firstInput) firstInput.focus({ preventScroll: true });
                }
            };

            const hydrate = (k) => {
                const pane = document.getElementById(paneId(k));
                if (!pane || pane.dataset.hydrated === '1') return;
                if (Array.isArray(oldRules[k])) oldRules[k].forEach((r) => addRule(k, r, { silent: true }));
                if (Array.isArray(oldClassEvents[k])) {
                    oldClassEvents[k].forEach((event) => addClassEvent(k, event, { silent: true }));
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
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1">${esc(labelOf(k))}</h6>
                                <small class="text-muted">Them rule tay hoac upload CSV rieng cho lop nay.</small>
                            </div>
                            <div class="pane-actions">
                                <button type="button" class="btn btn-sm btn-primary add-rule" data-key="${esc(k)}">Them rule</button>
                                <button type="button" class="btn btn-sm btn-outline-primary add-class-event" data-key="${esc(k)}">Them su kien</button>
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
                validateRuleConflicts();
                if (active && tabs.firstElementChild) activate(tabs.firstElementChild.dataset.key);
            };

            const syncChecks = () => {
                checkboxes.forEach((cb) => cb.checked ? ensurePane(cb.value) : removePane(cb.value));
            };

            document.querySelectorAll('[data-date-picker]').forEach((button) => {
                button.addEventListener('click', () => {
                    openNativeDatePicker(button.dataset.datePicker);
                });
            });

            nativeDateInputs.forEach((input) => {
                input.addEventListener('input', () => {
                    syncDateDisplay(input.dataset.dateNative);
                    validatePlanDates();
                    window.ScheduleDatePicker?.refreshBounds(document);
                });
                input.addEventListener('change', () => {
                    syncDateDisplay(input.dataset.dateNative);
                    validatePlanDates();
                    window.ScheduleDatePicker?.refreshBounds(document);
                });
            });

            filter.addEventListener('input', function() {
                const q = this.value.trim().toLowerCase();
                document.querySelectorAll('.class-item').forEach((item) => item.style.display = item.dataset
                    .search.includes(q) ? '' : 'none');
            });

            checkboxes.forEach((cb) => cb.addEventListener('change', function() {
                syncChecks();
                if (this.checked) activate(this.value);
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
                const ruleCard = remove.closest('.rule-card');
                if (!ruleCard) return;
                const pane = remove.closest('.class-pane');
                ruleCard.remove();
                updateRuleEmpty(pane.dataset.key);
                validateRuleConflicts();
            });

            content.addEventListener('input', function() {
                validateRuleConflicts();
                validateAllSemesterEvents();
            });
            content.addEventListener('change', function() {
                validateRuleConflicts();
                validateAllSemesterEvents();
            });
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
            form.addEventListener('submit', function(e) {
                if (!validateRuleConflicts() || !validateAllSemesterEvents()) {
                    e.preventDefault();
                }
            });

            syncChecks();
            updateEmpty();
            syncAllDateDisplays();
            validatePlanDates();
            validateRuleConflicts();
            if (globalHolidayEventList) {
                updateEventEmpty(globalHolidayEventList, 'Chua co su kien nghi le nao.');
            }
            const globalValues = Array.isArray(oldGlobalEvents) ? oldGlobalEvents : Object.values(oldGlobalEvents || {});
            globalValues.forEach((event) => {
                if (event && typeof event === 'object') {
                    addGlobalHoliday(event, { silent: true });
                }
            });
            validateAllSemesterEvents();
            applyServerRuleErrors();
        });


    </script>
@endsection
