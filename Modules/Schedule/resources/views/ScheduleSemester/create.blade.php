@extends('layouts.dashboard')

@section('title', 'Tạo lịch tổng quát học kỳ')

@section('content')
    @php
        $selectedTrainingBatchId = (string) old('training_batch_id', '');
        $oldRules = old('class_tab_rules', []);
        $oldGlobalEvents = old('global_semester_events', []);
        $oldClassEvents = old('class_semester_events', []);
        $hasAvailableTrainingBatches = $trainingBatches->isNotEmpty();
        $minAllowedPlanDate = now()->startOfDay()->subMonth()->toDateString();
        $isAdminUser = auth()->user()?->isAdmin() === true;
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
            padding: 10px 12px 12px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid #dbe7f3;
            box-shadow: 0 14px 36px rgba(15, 76, 129, 0.16);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .quick-actions-top {
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .quick-actions-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .quick-actions .quick-title {
            font-size: .78rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6b7b8f;
            font-weight: 700;
            margin-right: 2px;
        }

        .quick-actions-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #dbe7f3;
            border-radius: 999px;
            background: #fff;
            color: #0f4c81;
            padding: 8px 12px;
            font-size: .86rem;
            font-weight: 700;
            line-height: 1;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        }

        .quick-actions-toggle:hover {
            background: #f5f9ff;
            border-color: #b9d6ef;
            box-shadow: 0 4px 12px rgba(15, 76, 129, 0.08);
        }

        .quick-actions-toggle-icon {
            display: inline-flex;
            flex-direction: column;
            gap: 3px;
        }

        .quick-actions-toggle-icon span {
            display: block;
            width: 15px;
            height: 2px;
            border-radius: 999px;
            background: currentColor;
        }

        .quick-actions-toggle .quick-actions-caret {
            transition: transform .2s ease;
        }

        .quick-actions.is-collapsed .quick-actions-caret {
            transform: rotate(-90deg);
        }

        .quick-actions-body {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
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

        .quick-actions.is-collapsed .quick-actions-body {
            display: none;
        }

        .quick-actions-spacer {
            height: 90px;
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
                height: 108px;
            }

            .quick-actions-top {
                align-items: flex-start;
            }

            .quick-actions .quick-label {
                width: 100%;
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

        .subject-picker-input {
            background: #fff;
            cursor: pointer;
        }

        .subject-picker-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 16px;
        }

        .subject-picker-dialog {
            background: #fff;
            border-radius: 12px;
            width: 440px;
            max-width: 100%;
            max-height: 82vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        }

        .subject-picker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid #e5e9f0;
        }

        .subject-picker-header strong {
            color: #0f4c81;
        }

        .subject-picker-close {
            border: none;
            background: transparent;
            font-size: 1.4rem;
            line-height: 1;
            color: #6b7785;
            cursor: pointer;
        }

        .subject-picker-body {
            padding: 14px 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .subject-picker-list {
            overflow-y: auto;
            border: 1px solid #e5e9f0;
            border-radius: 8px;
            max-height: 50vh;
        }

        .subject-picker-item {
            padding: 9px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f2f5;
        }

        .subject-picker-item:last-child {
            border-bottom: none;
        }

        .subject-picker-item:hover,
        .subject-picker-item.is-active {
            background: #eef4fb;
        }

        .subject-picker-item .subject-picker-code {
            font-weight: 700;
            color: #0f4c81;
            margin-right: 6px;
        }

        .subject-picker-empty {
            padding: 14px;
            color: #6b7785;
            text-align: center;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="box shadow-sm">
                <div class="box-head">
                    <h4 class="mb-2">Tạo kế hoạch và lịch tổng quát học kỳ</h4>
                    <div>Mỗi lớp cần có ít nhất một rule hợp lệ hoặc một file import Excel/CSV.</div>
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

                        @if (auth()->user()?->isAdmin())
                            <div class="alert alert-warning" style="border:1px dashed #b98900;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="admin_backfill" value="1"
                                        id="adminBackfillToggle" {{ old('admin_backfill') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="adminBackfillToggle">
                                        Bổ sung dữ liệu cũ (bỏ qua ràng buộc ngày bắt đầu/kết thúc)
                                    </label>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label">Lý do bổ sung dữ liệu cũ <span class="text-danger">*</span></label>
                                    <textarea name="admin_backfill_reason" class="form-control @error('admin_backfill_reason') is-invalid @enderror"
                                        rows="2" placeholder="Ví dụ: Nhập bù lịch học kỳ trước khi hệ thống vận hành...">{{ old('admin_backfill_reason') }}</textarea>
                                    @error('admin_backfill_reason')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <div class="quick-actions shadow-sm" id="quickActionsBar">
                            <div class="quick-actions-top">
                                <div class="quick-actions-left">
                                    <button type="button" class="quick-actions-toggle" id="quickActionsToggleBtn"
                                        aria-expanded="true" aria-controls="quickActionsBody">
                                        <span class="quick-actions-toggle-icon" aria-hidden="true">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </span>
                                        <span>Menu nhanh</span>
                                        <span class="quick-actions-caret" aria-hidden="true">▾</span>
                                    </button>
                                    <div class="quick-title">Thao tác nhanh</div>
                                </div>
                                <div class="quick-label">
                                    Lớp hiện tại: <strong id="quickActiveClassLabel">Chưa chọn</strong>
                                </div>
                            </div>
                            <div class="quick-actions-body" id="quickActionsBody">
                                <button type="button" class="btn btn-sm btn-primary" id="quickAddRuleBtn">
                                    Thêm rule
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="quickAddClassEventBtn">
                                    Thêm sự kiện
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="quickAddHolidayBtn">
                                    Thêm nghỉ lễ
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="quickPreviewBtn">
                                    Xem trước
                                </button>
                                <button type="button" class="btn btn-sm btn-light border" id="quickScrollTopBtn">
                                    Lên đầu
                                </button>
                            </div>
                        </div>
                        <div class="quick-actions-spacer"></div>

                        <h5 class="mb-3">1. Thông tin học kỳ</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Khóa đào tạo <span class="text-danger">*</span></label>
                                <select name="training_batch_id" id="trainingBatchSelect" class="form-control" required
                                    {{ $hasAvailableTrainingBatches ? '' : 'disabled' }}>
                                    <option value="">Chọn khóa đào tạo</option>
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
                                            - {{ $batch->classes_count }} lớp
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-2">Chỉ hiển thị khóa đào tạo đang hoạt động.</small>
                                @if (!$hasAvailableTrainingBatches)
                                    <div class="alert alert-warning mt-3 mb-0">
                                        Chưa có khóa đào tạo khả dụng để tạo kế hoạch mới.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-3">
                                <label class="form-label">Học kỳ</label>
                                <select name="semester" class="form-control" required>
                                    <option value="1" {{ old('semester') == 1 ? 'selected' : '' }}>Học kỳ 1</option>
                                    <option value="2" {{ old('semester') == 2 ? 'selected' : '' }}>Học kỳ 2</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Năm học</label>
                                <input type="number" name="year" class="form-control"
                                    value="{{ old('year', date('Y')) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngày bắt đầu</label>
                                @include('schedule::partials._date-picker-field', [
                                    'name' => 'start_date',
                                    'field' => 'start_date',
                                    'value' => old('start_date'),
                                    'required' => true,
                                    'min' => $minAllowedPlanDate,
                                    'buttonLabel' => 'Lịch',
                                    'readonly' => ! $isAdminUser,
                                ])
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngày kết thúc</label>
                                @include('schedule::partials._date-picker-field', [
                                    'name' => 'end_date',
                                    'field' => 'end_date',
                                    'value' => old('end_date'),
                                    'required' => true,
                                    'min' => $minAllowedPlanDate,
                                    'buttonLabel' => 'Lịch',
                                    'readonly' => ! $isAdminUser,
                                ])
                            </div>
                        </div>
                        <div id="planDuplicateHint" class="alert alert-warning mt-3 d-none"></div>

                        <div class="mt-3">
                            <label class="form-label">Mô tả kế hoạch</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">2. Chọn lớp áp dụng</h5>
                        <div class="row">
                            <div class="col-lg-7">
                                <div id="classBatchHint" class="alert alert-light border mb-3">
                                    Chọn khóa đào tạo để hiển thị danh sách lớp.
                                </div>
                                <input type="text" class="form-control mb-3" id="classFilterInput"
                                    placeholder="Tìm theo mã hoặc tên lớp">
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
                            <h5 class="mb-0">3. Cấu hình từng lớp</h5>
                            <a href="{{ route('schedule.import-template') }}" class="btn btn-sm btn-outline-secondary">
                                Tải file mẫu
                            </a>
                        </div>
                        <div id="classTabs" class="d-flex flex-wrap mb-3"></div>
                        <div id="classTabContent">
                            <div id="noClassSelectedMsg" class="alert alert-light border">
                                Chọn ít nhất một lớp ở trên để thêm rule hoặc upload file import.
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">4. Sự kiện chung</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addGlobalHolidayBtn">
                                Thêm nghỉ lễ
                            </button>
                        </div>
                        <div class="text-muted small mb-3">
                            Chỉ dùng cho sự kiện áp dụng cho tất cả các lớp, ví dụ nghỉ lễ. Sự kiện này chỉ cần
                            nhập một lần và sẽ hiển thị trên tất cả tab lớp.
                        </div>
                        <div id="globalHolidayEventList"></div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary px-4"
                                {{ $hasAvailableTrainingBatches ? '' : 'disabled' }}>Tạo kế hoạch</button>
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

    <div id="subjectPickerModal" class="subject-picker-overlay" style="display:none;">
        <div class="subject-picker-dialog">
            <div class="subject-picker-header">
                <strong>Chọn môn học</strong>
                <button type="button" class="subject-picker-close" id="subjectPickerClose" aria-label="Đóng">&times;</button>
            </div>
            <div class="subject-picker-body">
                <input type="text" id="subjectPickerSearch" class="form-control form-control-sm"
                    placeholder="Tìm theo mã hoặc tên môn học...">
                <div id="subjectPickerList" class="subject-picker-list"></div>
            </div>
        </div>
    </div>

    @include('schedule::ScheduleSemester.partials.preview-modal')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const previewUrl = @json(route('schedule.semester-preview'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            window.ScheduleSemesterPreview?.init({ url: previewUrl, csrfToken });
            const weekdayOptions = @json($weekdayOptions);
            const oldRules = @json($oldRules);
            const oldGlobalEvents = @json($oldGlobalEvents);
            const oldClassEvents = @json($oldClassEvents);
            const existingPlanKeys = new Set(@json($existingPlanKeys));
            const importUrl = @json(route('schedule.import-template'));
            const globalEventTypes = @json($globalEventTypes);
            const classEventTypes = @json($classEventTypes);
            const allSubjectSuggestions = @json($subjectSuggestions);
            const batchProgramMap = @json($batchProgramMap);
            const programSubjectCodes = @json($programSubjectCodes);
            const subjectSuggestionsList = document.getElementById('subject-suggestions');
            let currentSubjectList = allSubjectSuggestions;
            const subjectPickerModal = document.getElementById('subjectPickerModal');
            const subjectPickerSearch = document.getElementById('subjectPickerSearch');
            const subjectPickerList = document.getElementById('subjectPickerList');
            const subjectPickerClose = document.getElementById('subjectPickerClose');
            let subjectPickerTargetInput = null;
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
            const quickPreviewBtn = document.getElementById('quickPreviewBtn');
            const quickScrollTopBtn = document.getElementById('quickScrollTopBtn');
            const quickActiveClassLabel = document.getElementById('quickActiveClassLabel');
            const quickActionsBar = document.getElementById('quickActionsBar');
            const quickActionsToggleBtn = document.getElementById('quickActionsToggleBtn');
            const quickActionsBody = document.getElementById('quickActionsBody');
            const quickActionsStorageKey = 'schedule-create-quick-actions-collapsed';
            const displayDateInputs = Array.from(document.querySelectorAll('[data-date-display]'));
            const nativeDateInputs = Array.from(document.querySelectorAll('[data-date-native]'));
            const baseMinAllowedPlanDate = @json($minAllowedPlanDate);
            let minAllowedPlanDate = baseMinAllowedPlanDate;
            const adminBackfillToggle = document.getElementById('adminBackfillToggle');
            const planDateFieldWrappers = Array.from(document.querySelectorAll(
                '.js-schedule-date-field[data-field="start_date"], .js-schedule-date-field[data-field="end_date"]'
            ));
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
                        endDisplay.setCustomValidity('Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.');
                    }
                    valid = false;
                }

                syncAllDateDisplays();
                window.ScheduleDatePicker?.refreshBounds(document);

                return valid;
            };

            const applyAdminBackfillDateBounds = () => {
                const active = !!(adminBackfillToggle && adminBackfillToggle.checked);
                minAllowedPlanDate = active ? '' : baseMinAllowedPlanDate;
                planDateFieldWrappers.forEach((wrapper) => {
                    if (active) {
                        wrapper.removeAttribute('data-min');
                    } else {
                        wrapper.setAttribute('data-min', baseMinAllowedPlanDate);
                    }
                });
                window.ScheduleDatePicker?.refreshBounds(document);
                validatePlanDates();
            };

            const syncAllDateDisplays = () => {
                displayDateInputs.forEach((input) => syncDateDisplay(input.dataset.dateDisplay));
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

            const syncQuickActionsCollapsedState = (collapsed) => {
                if (!quickActionsBar || !quickActionsToggleBtn) return;
                quickActionsBar.classList.toggle('is-collapsed', collapsed);
                quickActionsToggleBtn.setAttribute('aria-expanded', String(!collapsed));
                quickActionsToggleBtn.dataset.collapsed = collapsed ? '1' : '0';
                try {
                    localStorage.setItem(quickActionsStorageKey, collapsed ? '1' : '0');
                } catch (error) {
                    // Ignore storage failures and keep the menu usable.
                }
            };

            const syncQuickActions = () => {
                const hasActiveClass = Boolean(activeClassKey && document.getElementById(paneId(activeClassKey)));
                if (quickAddRuleBtn) quickAddRuleBtn.disabled = !hasActiveClass;
                if (quickAddClassEventBtn) quickAddClassEventBtn.disabled = !hasActiveClass;
                if (quickPreviewBtn) quickPreviewBtn.disabled = !hasActiveClass;
                if (quickActiveClassLabel) {
                    quickActiveClassLabel.textContent = hasActiveClass ? labelOf(activeClassKey) : 'Chưa chọn';
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
                    hint.textContent = 'Chưa có rule. Bạn có thể thêm rule tay hoặc chỉ upload file import.';
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
                            `Khóa đào tạo này đã có kế hoạch học kỳ ${semesterInput.value} năm ${yearInput.value}.`;
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
                    const classLabel = pane.querySelector('h6')?.textContent || 'lớp';

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
                                        `Trùng lịch với rule khác trong ${classLabel} tại ${dateKey}, tiết ${period}.`
                                    );
                                    appendRuleError(slotOwners.get(slotKey),
                                        `Trùng lịch với rule khác trong ${classLabel} tại ${dateKey}, tiết ${period}.`
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

            const renderDateField = (label, name, value, fieldName, options = {}) => {
                const fieldOptions = {
                    label,
                    name,
                    value,
                    fieldName,
                    required: true,
                    buttonLabel: 'Lịch',
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
                                data-date-display="${esc(fieldName)}" placeholder="DD/MM/YYYY" value="${esc(window.ScheduleDatePicker?.formatDisplay(value) || '')}"${fieldOptions.readonly ? ' readonly' : ''} required>
                            <input type="hidden" name="${esc(name)}" data-date-native="${esc(fieldName)}" value="${esc(value || '')}">
                            <button type="button" class="btn btn-outline-secondary schedule-date-toggle"
                                data-date-picker="${esc(fieldName)}">Lịch</button>
                        </div>
                    </div>
                `;
            };

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
                        <button type="button" class="btn btn-sm btn-outline-danger remove-rule">Xóa</button>
                    </div>
                    <div class="row">
                        <div class="col-md-3">${renderDateField('Từ ngày', `class_tab_rules[${esc(k)}][${i}][start_date]`, startDate, `${safe(k)}_${i}_start_date`)}</div>
                        <div class="col-md-3">${renderDateField('Đến ngày', `class_tab_rules[${esc(k)}][${i}][end_date]`, endDate, `${safe(k)}_${i}_end_date`)}</div>
                        <div class="col-md-3"><label class="form-label">Tiết bắt đầu</label><input type="number" min="1" max="9" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][period_from]" value="${esc(data.period_from || '')}"></div>
                        <div class="col-md-3"><label class="form-label">Tiết kết thúc</label><input type="number" min="1" max="9" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][period_to]" value="${esc(data.period_to || '')}"></div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Môn học</label>
                            <div class="input-group input-group-sm subject-picker-group">
                                <input type="text" class="form-control subject-picker-input" name="class_tab_rules[${esc(k)}][${i}][subject]" value="${esc(data.subject || '')}" placeholder="Chọn môn học..." readonly>
                                <button type="button" class="btn btn-outline-secondary subject-picker-trigger">Tìm</button>
                            </div>
                        </div>
                        <div class="col-md-6"><label class="form-label">Nội dung</label><input type="text" class="form-control form-control-sm" name="class_tab_rules[${esc(k)}][${i}][content]" value="${esc(data.content || '')}"></div>
                    </div>
                    <div class="mt-3"><label class="form-label d-block">Thứ học</label><div class="weekday-list">${weekdayHtml(k, i, data.weekdays || [])}</div></div>
                `;
                box.appendChild(div);
                window.ScheduleDatePicker?.init(div);
                updateRuleEmpty(k);
                validateRuleConflicts();
                scrollToEl(div);
                const firstInput = div.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus({ preventScroll: true });
            };

            const collectPreviewPayload = (k) => {
                const pane = document.getElementById(paneId(k));
                if (!pane) return null;

                const rulesBox = document.getElementById(rulesId(k));
                const rules = Array.from(rulesBox ? rulesBox.querySelectorAll('.rule-card') : []).map((card) => {
                    const parsed = parseRule(card);
                    if (!parsed) return null;
                    const subject = (card.querySelector('input[name$="[subject]"]')?.value || '').trim();
                    if (!subject) return null;
                    return {
                        subject,
                        weekdays: parsed.weekdays,
                        period_from: parsed.periodFrom,
                        period_to: parsed.periodTo,
                        start_date: parsed.startDate,
                        end_date: parsed.endDate,
                    };
                }).filter(Boolean);

                const eventCardsToPayload = (listEl) => Array.from(listEl ? listEl.querySelectorAll('.event-card') : [])
                    .map((card) => {
                        const parsed = parseEvent(card);
                        if (!parsed) return null;
                        return {
                            title: parsed.title,
                            start_date: parsed.startDate,
                            end_date: parsed.endDate,
                            period_from: parsed.periodFrom,
                            period_to: parsed.periodTo,
                        };
                    }).filter(Boolean);

                const cb = checkboxes.find((item) => item.value === String(k));
                const importFile = pane.querySelector('input[type="file"]')?.files?.[0] || null;

                return {
                    title: `Xem trước lịch - ${labelOf(k)}`,
                    startDate: planStartInput?.value || '',
                    endDate: planEndInput?.value || '',
                    rules,
                    classEvents: eventCardsToPayload(document.getElementById(classEventsId(k))),
                    globalEvents: eventCardsToPayload(globalHolidayEventList),
                    classCode: cb?.dataset.code || '',
                    importFile,
                };
            };

            const eventMeta = (type, list) => list.find((item) => String(item.value) === String(type));
            const eventLabel = (type, list) => eventMeta(type, list)?.label || 'Sự kiện';
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
                const fieldKeyBase = safe(namePrefix);

                return `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong>${esc(defaultLabel || 'Sự kiện')} #${index + 1}</strong>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-event">Xóa</button>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Loại sự kiện</label>
                            <select class="form-control form-control-sm" name="${namePrefix}[event_type]">
                                ${buildEventOptions(types, eventType)}
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tên sự kiện</label>
                            <input type="text" class="form-control form-control-sm" name="${namePrefix}[title]" value="${esc(label)}" placeholder="Nhập tên sự kiện">
                        </div>
                        <div class="col-md-3">${renderDateField('Từ ngày', `${namePrefix}[start_date]`, data.start_date || planStartInput?.value || '', `${fieldKeyBase}_start_date`)}</div>
                        <div class="col-md-3">${renderDateField('Đến ngày', `${namePrefix}[end_date]`, data.end_date || planEndInput?.value || '', `${fieldKeyBase}_end_date`)}</div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-2">
                            <label class="form-label">Tiết bắt đầu</label>
                            <input type="number" min="1" max="9" class="form-control form-control-sm" name="${namePrefix}[period_from]" value="${esc(data.period_from || 1)}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tiết kết thúc</label>
                            <input type="number" min="1" max="9" class="form-control form-control-sm" name="${namePrefix}[period_to]" value="${esc(data.period_to || 9)}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ghi chú</label>
                            <input type="text" class="form-control form-control-sm" name="${namePrefix}[note]" value="${esc(data.note || '')}">
                        </div>
                    </div>
                    <input type="hidden" name="${namePrefix}[color]" value="${esc(color)}">
                    <div class="mt-2 small text-muted">Màu hiển thị: <span class="badge badge-light" style="background:${esc(color)};">${esc(eventLabel(eventType, types))}</span></div>
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
                    'Nghỉ lễ'
                );
                globalHolidayEventList.appendChild(card);
                window.ScheduleDatePicker?.init(card);
                updateEventEmpty(globalHolidayEventList, 'Chưa có sự kiện nghỉ lễ nào.');
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
                    'Sự kiện lớp'
                );
                listEl.appendChild(card);
                window.ScheduleDatePicker?.init(card);
                updateEventEmpty(listEl, 'Chưa có sự kiện nào trong lớp này.');
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
                        appendEventError(card, 'Vui lòng điền đầy đủ thông tin sự kiện.');
                        return;
                    }

                    if (parsed.periodFrom > parsed.periodTo) {
                        valid = false;
                        appendEventError(card, 'Tiết kết thúc phải lớn hơn hoặc bằng tiết bắt đầu.');
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
                            const conflictMessage = `Bị trùng ngày/tiết tại ${slotKey.replace('|', ', tiết ')}.`;
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
                                    `Sự kiện chung '${globalEvent.title}' bị trùng ngày/tiết với sự kiện lớp '${classEvent.title}'.`;
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
                                <small class="text-muted">Thêm rule tay hoặc upload Excel/CSV riêng cho lớp này.</small>
                            </div>
                                <div class="pane-actions">
                                    <button type="button" class="btn btn-sm btn-primary add-rule" data-key="${esc(k)}">Thêm rule</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary add-class-event" data-key="${esc(k)}">Thêm sự kiện</button>
                                    <button type="button" class="btn btn-sm btn-outline-info preview-schedule" data-key="${esc(k)}">Xem trước</button>
                                </div>
                            </div>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Sự kiện của lớp</strong>
                            </div>
                            <small class="text-muted d-block mb-2">Dùng cho ôn thi, thi hoặc sự kiện riêng của lớp này.</small>
                            <div id="${classEventsId(k)}"></div>
                        </div>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>Import Excel/CSV</strong>
                                <a href="${esc(importUrl)}" class="btn btn-sm btn-outline-secondary">Tải template</a>
                            </div>
                            <input type="file" name="import_file[${esc(k)}]" class="form-control form-control-sm" accept=".xlsx,.csv,.txt">
                            <small class="text-muted">Cột chính: row_type, class_code, start_date, end_date, period_from, period_to. Dòng rule dùng thêm subject/content/weekdays; dòng event dùng event_type/title/note.</small>
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

                updateEventEmpty(globalHolidayEventList, 'Chưa có sự kiện nghỉ lễ nào.');
                Object.keys(eventCounters.classes).forEach((classKey) => {
                    updateEventEmpty(document.getElementById(classEventsId(classKey)), 'Chưa có sự kiện nào trong lớp này.');
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
                        classBatchHint.textContent = 'Chọn khóa đào tạo để hiển thị danh sách lớp.';
                    } else if (batchClassCount === 0) {
                        classBatchHint.className = 'alert alert-warning border mb-3';
                        classBatchHint.textContent = 'Khóa đào tạo này chưa có lớp nào.';
                    } else if (visibleClassCount === 0) {
                        classBatchHint.textContent = 'Không có lớp nào khớp với từ khóa tìm kiếm.';
                    } else {
                        classBatchHint.textContent =
                            `Đã tự động chọn tất cả ${batchClassCount} lớp thuộc khóa đào tạo. Đang hiển thị ${visibleClassCount} lớp.`;
                    }
                }

                syncSelectedClassInputs();
                updateEmpty();
                validateRuleConflicts();
            };

            if (filter) {
                filter.addEventListener('input', applyClassFilters);
            }

            const refreshSubjectSuggestions = () => {
                if (!subjectSuggestionsList) {
                    return;
                }

                const batchId = batchSelect ? String(batchSelect.value || '') : '';
                const programId = batchId ? batchProgramMap[batchId] : null;
                const allowedCodes = programId !== null && programId !== undefined ?
                    programSubjectCodes[String(programId)] : null;

                const suggestions = (allowedCodes && allowedCodes.length > 0) ?
                    allSubjectSuggestions.filter((s) => allowedCodes.includes(s.code)) :
                    allSubjectSuggestions;

                currentSubjectList = suggestions;

                subjectSuggestionsList.innerHTML = suggestions
                    .map((s) => `<option value="${s.code}">${s.name}</option>`)
                    .join('');
            };

            if (batchSelect) {
                batchSelect.addEventListener('change', function() {
                    applyClassFilters();
                    syncChecks();
                    validateRuleConflicts();
                    refreshSubjectSuggestions();
                });
            }

            refreshSubjectSuggestions();

            const renderSubjectPickerList = (query) => {
                const q = query.trim().toLowerCase();
                const options = q === '' ? currentSubjectList : currentSubjectList.filter((s) =>
                    s.code.toLowerCase().includes(q) || s.name.toLowerCase().includes(q));

                if (options.length === 0) {
                    subjectPickerList.innerHTML = '<div class="subject-picker-empty">Không tìm thấy môn học phù hợp.</div>';
                    return;
                }

                subjectPickerList.innerHTML = options.map((s) => `
                    <div class="subject-picker-item" data-code="${esc(s.code)}">
                        <span class="subject-picker-code">${esc(s.code)}</span>${esc(s.name)}
                    </div>
                `).join('');
            };

            const openSubjectPicker = (inputEl) => {
                subjectPickerTargetInput = inputEl;
                subjectPickerSearch.value = '';
                renderSubjectPickerList('');
                subjectPickerModal.style.display = 'flex';
                subjectPickerSearch.focus();
            };

            const closeSubjectPicker = () => {
                subjectPickerModal.style.display = 'none';
                subjectPickerTargetInput = null;
            };

            if (subjectPickerSearch) {
                subjectPickerSearch.addEventListener('input', () => renderSubjectPickerList(subjectPickerSearch.value));
            }

            if (subjectPickerList) {
                subjectPickerList.addEventListener('click', (event) => {
                    const item = event.target.closest('.subject-picker-item');
                    if (!item || !subjectPickerTargetInput) return;
                    subjectPickerTargetInput.value = item.dataset.code;
                    subjectPickerTargetInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                    closeSubjectPicker();
                });
            }

            if (subjectPickerClose) {
                subjectPickerClose.addEventListener('click', closeSubjectPicker);
            }

            if (subjectPickerModal) {
                subjectPickerModal.addEventListener('click', (event) => {
                    if (event.target === subjectPickerModal) closeSubjectPicker();
                });
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && subjectPickerModal && subjectPickerModal.style.display !== 'none') {
                    closeSubjectPicker();
                }
            });

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('.subject-picker-input, .subject-picker-trigger');
                if (!trigger) return;
                const wrapper = trigger.closest('.subject-picker-group');
                const input = wrapper ? wrapper.querySelector('.subject-picker-input') : null;
                if (input) openSubjectPicker(input);
            });

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
                const previewBtn = e.target.closest('.preview-schedule');
                if (previewBtn) {
                    const payload = collectPreviewPayload(previewBtn.dataset.key);
                    if (payload) window.ScheduleSemesterPreview?.open(payload);
                    return;
                }
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
                            ? 'Chưa có sự kiện nghỉ lễ nào.'
                            : 'Chưa có sự kiện nào trong lớp này.';
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
                    updateEventEmpty(listEl, 'Chưa có sự kiện nào trong lớp này.');
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
                updateEventEmpty(listEl, 'Chưa có sự kiện nghỉ lễ nào.');
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
            quickPreviewBtn?.addEventListener('click', () => {
                if (!activeClassKey) return;
                const payload = collectPreviewPayload(activeClassKey);
                if (payload) window.ScheduleSemesterPreview?.open(payload);
            });
            quickScrollTopBtn?.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            quickActionsToggleBtn?.addEventListener('click', () => {
                const collapsed = quickActionsBar?.classList.contains('is-collapsed') ?? false;
                syncQuickActionsCollapsedState(!collapsed);
            });
            document.querySelectorAll('[data-date-picker]').forEach((button) => {
                button.addEventListener('click', () => {
                    openNativeDatePicker(button.dataset.datePicker);
                });
            });
            nativeDateInputs.forEach((input) => {
                input.addEventListener('input', () => {
                    syncDateDisplay(input.dataset.dateNative);
                    validatePlanDates();
                });
                input.addEventListener('change', () => {
                    syncDateDisplay(input.dataset.dateNative);
                    validatePlanDates();
                });
            });
            form.addEventListener('submit', function(e) {
                if (!validateRuleConflicts() || !validateAllSemesterEvents() || !validatePlanDates()) {
                    e.preventDefault();
                }
            });

            adminBackfillToggle?.addEventListener('change', applyAdminBackfillDateBounds);
            applyAdminBackfillDateBounds();

            syncAllDateDisplays();
            validatePlanDates();
            const initialCollapsed = (() => {
                try {
                    return localStorage.getItem(quickActionsStorageKey) === '1';
                } catch (error) {
                    return false;
                }
            })();
            syncQuickActionsCollapsedState(initialCollapsed);
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
