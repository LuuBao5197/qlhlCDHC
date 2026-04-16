@extends('layouts.dashboard')

@section('title', 'Sua lich tong quat hoc ky')

@section('content')
    @php
        $selectedClassIds = collect(old('selected_class_ids', $selectedClassIds ?? []))->map(fn($id) => (string) $id)->all();
        $oldRules = old('class_tab_rules', $oldRules ?? []);
        $oldExtraClass = old('class_name', '');
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
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Hoc ky</label>
                                <select name="semester" class="form-control" required>
                                    <option value="1" {{ old('semester', $plan->semester) == 1 ? 'selected' : '' }}>Hoc ky 1</option>
                                    <option value="2" {{ old('semester', $plan->semester) == 2 ? 'selected' : '' }}>Hoc ky 2</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nam hoc</label>
                                <input type="number" name="year" class="form-control"
                                    value="{{ old('year', $plan->year) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngay bat dau</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date', $plan->effective_from?->toDateString()) }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngay ket thuc</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ old('end_date', $plan->effective_to?->toDateString()) }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Mon mac dinh</label>
                                <input type="text" name="default_subject" class="form-control" list="subject-suggestions"
                                    value="{{ old('default_subject') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Noi dung mac dinh</label>
                                <input type="text" name="default_content" class="form-control"
                                    value="{{ old('default_content', 'Noi dung se cap nhat sau') }}">
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
            const importUrl = @json(route('schedule.import-template'));
            const tabs = document.getElementById('classTabs');
            const content = document.getElementById('classTabContent');
            const emptyMsg = document.getElementById('noClassSelectedMsg');
            const filter = document.getElementById('classFilterInput');
            const planStartInput = document.querySelector('input[name="start_date"]');
            const planEndInput = document.querySelector('input[name="end_date"]');
            const checkboxes = Array.from(document.querySelectorAll('.class-checkbox'));
            const form = document.getElementById('scheduleForm');
            const submitButton = form.querySelector('button[type="submit"]');
            const counters = {};

            const esc = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            const safe = (v) => String(v).replace(/[^A-Za-z0-9_-]/g, '_');
            const tabId = (k) => `tab-${safe(k)}`;
            const paneId = (k) => `pane-${safe(k)}`;
            const rulesId = (k) => `rules-${safe(k)}`;
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

                submitButton.disabled = hasConflict;
                return !hasConflict;
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
            };

            const hydrate = (k) => {
                const pane = document.getElementById(paneId(k));
                if (!pane || pane.dataset.hydrated === '1') return;
                if (Array.isArray(oldRules[k])) oldRules[k].forEach((r) => addRule(k, r));
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
                            <button type="button" class="btn btn-sm btn-primary add-rule" data-key="${esc(k)}">Them rule</button>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Import CSV</strong>
                                <a href="${esc(importUrl)}" class="btn btn-sm btn-outline-secondary">Tai template</a>
                            </div>
                            <input type="file" name="import_file[${esc(k)}]" class="form-control form-control-sm">
                            <small class="text-muted">Cot bat buoc: class_code, date, period_from, period_to, subject.</small>
                        </div>
                        <div id="${rulesId(k)}"></div>
                    `;
                    content.appendChild(pane);
                    counters[k] = 0;
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
                updateEmpty();
                validateRuleConflicts();
                if (active && tabs.firstElementChild) activate(tabs.firstElementChild.dataset.key);
            };

            const syncChecks = () => {
                checkboxes.forEach((cb) => cb.checked ? ensurePane(cb.value) : removePane(cb.value));
            };

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
                const remove = e.target.closest('.remove-rule');
                if (!remove) return;
                const pane = remove.closest('.class-pane');
                remove.closest('.rule-card').remove();
                updateRuleEmpty(pane.dataset.key);
                validateRuleConflicts();
            });

            content.addEventListener('input', validateRuleConflicts);
            content.addEventListener('change', validateRuleConflicts);
            form.addEventListener('submit', function(e) {
                if (!validateRuleConflicts()) {
                    e.preventDefault();
                }
            });

            syncChecks();
            updateEmpty();
            validateRuleConflicts();
        });
    </script>
@endsection
