@extends('layouts.dashboard')

@section('title', 'Sửa lịch tổng quát học kỳ')

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

        .import-guide-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1300;
            padding: 16px;
        }

        .import-guide-dialog {
            background: #fff;
            border-radius: 12px;
            width: 720px;
            max-width: 100%;
            max-height: 86vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        }

        .import-guide-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #e5e9f0;
        }

        .import-guide-header strong {
            color: #0f4c81;
            font-size: 1.05rem;
        }

        .import-guide-close {
            border: none;
            background: transparent;
            font-size: 1.4rem;
            line-height: 1;
            color: #6b7785;
            cursor: pointer;
        }

        .import-guide-body {
            padding: 18px 20px;
            overflow-y: auto;
        }

        .import-guide-body h6 {
            color: #0f4c81;
            margin-top: 18px;
            margin-bottom: 8px;
        }

        .import-guide-body h6:first-child {
            margin-top: 0;
        }

        .import-guide-body table {
            width: 100%;
            font-size: .85rem;
            margin-bottom: 8px;
        }

        .import-guide-body table th,
        .import-guide-body table td {
            border: 1px solid #e5e9f0;
            padding: 6px 8px;
            vertical-align: top;
        }

        .import-guide-body pre {
            background: #f4f6f3;
            border: 1px solid #e5e9f0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: .8rem;
            overflow-x: auto;
        }

        .import-guide-body .text-muted {
            font-size: .85rem;
        }

        .monthly-event-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1300;
            padding: 16px;
        }

        .monthly-event-dialog {
            background: #fff;
            border-radius: 12px;
            width: 520px;
            max-width: 100%;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        }

        .monthly-event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            border-bottom: 1px solid #e5e9f0;
        }

        .monthly-event-header strong {
            color: #0f4c81;
        }

        .monthly-event-close {
            border: none;
            background: transparent;
            font-size: 1.4rem;
            line-height: 1;
            color: #6b7785;
            cursor: pointer;
        }

        .monthly-event-body {
            padding: 16px 20px;
            overflow-y: auto;
        }

        .monthly-event-footer {
            padding: 12px 20px;
            border-top: 1px solid #e5e9f0;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .monthly-event-preview {
            font-size: .82rem;
            color: #6b7785;
            margin-top: 4px;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="box shadow-sm">
                <div class="box-head">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h4 class="mb-2">Sửa kế hoạch và lịch tổng quát học kỳ</h4>
                            <div>Cập nhật kế hoạch và lịch cho lớp. Mỗi lớp cần có ít nhất một rule hoặc một file CSV.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light open-import-guide" title="Xem hướng dẫn tạo/cập nhật lịch bằng file import CSV/Excel">
                            <span aria-hidden="true">&#9432;</span> Hướng dẫn import CSV/Excel
                        </button>
                    </div>
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
                        @if ($plan->trainingBatch)
                            <div class="alert alert-info">
                                Khóa đào tạo: <strong>{{ $plan->trainingBatch->code }}</strong> - {{ $plan->trainingBatch->name }}
                                @if ($plan->trainingBatch->trainingProgram)
                                    ({{ $plan->trainingBatch->trainingProgram->code }} - {{ $plan->trainingBatch->trainingProgram->name }})
                                @endif
                            </div>
                        @endif
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Học kỳ</label>
                                <select name="semester" class="form-control" required>
                                    <option value="1" {{ old('semester', $plan->semester) == 1 ? 'selected' : '' }}>Học
                                        kỳ 1</option>
                                    <option value="2" {{ old('semester', $plan->semester) == 2 ? 'selected' : '' }}>Học
                                        kỳ 2</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Năm học</label>
                                <input type="number" name="year" class="form-control"
                                    value="{{ old('year', $plan->year) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngày bắt đầu</label>
                                @include('schedule::partials._date-picker-field', [
                                    'name' => 'start_date',
                                    'field' => 'start_date',
                                    'value' => old('start_date', $plan->effective_from?->toDateString()),
                                    'required' => true,
                                    'min' => $minAllowedPlanDate,
                                    'buttonLabel' => 'Lịch',
                                ])
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ngày kết thúc</label>
                                @include('schedule::partials._date-picker-field', [
                                    'name' => 'end_date',
                                    'field' => 'end_date',
                                    'value' => old('end_date', $plan->effective_to?->toDateString()),
                                    'required' => true,
                                    'min' => $minAllowedPlanDate,
                                    'buttonLabel' => 'Lịch',
                                ])
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Mô tả kế hoạch</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $plan->description) }}</textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">2. Chọn lớp áp dụng</h5>
                        <div class="row">
                            <div class="col-lg-7">
                                <input type="text" class="form-control mb-3" id="classFilterInput"
                                    placeholder="Tìm theo mã hoặc tên lớp">
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
                            <h5 class="mb-0">3. Cấu hình từng lớp</h5>
                            <a href="{{ route('schedule.import-template') }}" class="btn btn-sm btn-outline-secondary">
                                Tải file CSV mẫu
                            </a>
                        </div>
                        <div id="classTabs" class="d-flex flex-wrap mb-3"></div>
                        <div id="classTabContent">
                            <div id="noClassSelectedMsg" class="alert alert-light border">
                                Chọn ít nhất một lớp ở trên để thêm rule hoặc upload CSV.
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">4. Sự kiện học kỳ</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addGlobalHolidayBtn">
                                Thêm nghỉ lễ
                            </button>
                        </div>
                        <div class="text-muted small mb-3">
                            Nghỉ lễ áp dụng cho tất cả các lớp, còn ôn thi/thi/sự kiện khác nằm ngay trong từng tab lớp.
                        </div>
                        <div id="globalHolidayEventList" class="event-group-shell"></div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary px-4">Hủy</a>
                            <button type="submit" class="btn btn-primary px-4">Cập nhật kế hoạch</button>
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

    <div id="importGuideModal" class="import-guide-overlay" style="display:none;">
        <div class="import-guide-dialog">
            <div class="import-guide-header">
                <strong>Hướng dẫn tạo/cập nhật lịch bằng file import CSV/Excel</strong>
                <button type="button" class="import-guide-close" id="importGuideClose" aria-label="Đóng">&times;</button>
            </div>
            <div class="import-guide-body">
                <h6>Bước 1 — Chọn khóa đào tạo và lớp</h6>
                <div class="text-muted">Chọn Khóa đào tạo, Học kỳ, Năm học, ngày bắt đầu/kết thúc học kỳ, rồi chọn các lớp cần tạo/sửa lịch.</div>

                <h6>Bước 2 — Tải file mẫu</h6>
                <div class="text-muted">Ở khu vực "Import Excel/CSV" của mỗi lớp, bấm "Tải template" để tải file mẫu (.xlsx/.csv/.txt). Mỗi lớp import file riêng.</div>

                <h6>Bước 3 — Điền dữ liệu</h6>
                <div class="text-muted">Mỗi dòng là một <strong>rule</strong> (lịch lặp lại theo tuần) hoặc một <strong>event</strong> (ôn thi/thi/khác), phân biệt bằng cột <code>row_type</code>.</div>
                <table>
                    <tr><th>Cột chung</th><th>Ý nghĩa</th></tr>
                    <tr><td><code>row_type</code></td><td>rule (mặc định) hoặc event</td></tr>
                    <tr><td><code>class_code</code></td><td>Mã lớp, phải khớp lớp đang import</td></tr>
                    <tr><td><code>date</code> / <code>start_date</code></td><td>Ngày bắt đầu áp dụng</td></tr>
                    <tr><td><code>end_date</code></td><td>Ngày kết thúc (mặc định = start_date)</td></tr>
                    <tr><td><code>period_from</code>, <code>period_to</code></td><td>Tiết bắt đầu – kết thúc (1-9)</td></tr>
                </table>
                <table>
                    <tr><th>Cột riêng cho rule</th><th>Ý nghĩa</th></tr>
                    <tr><td><code>subject</code></td><td>Mã/tên môn học đã có trong hệ thống, thuộc chương trình đào tạo của khóa</td></tr>
                    <tr><td><code>weekdays</code></td><td>Các thứ áp dụng, vd 2,4,6 hoặc Mon,Wed,Fri. Bỏ trống = lấy thứ của start_date</td></tr>
                    <tr><td><code>content</code></td><td>Ghi chú (tùy chọn)</td></tr>
                </table>
                <table>
                    <tr><th>Cột riêng cho event</th><th>Ý nghĩa</th></tr>
                    <tr><td><code>event_type</code></td><td>review, exam hoặc other</td></tr>
                    <tr><td><code>title</code></td><td>Tên sự kiện (bắt buộc)</td></tr>
                    <tr><td><code>note</code> / <code>content</code></td><td>Ghi chú (tùy chọn)</td></tr>
                    <tr><td><code>recurrence</code></td><td>Bỏ trống = sự kiện 1 lần (theo start_date/end_date). Đặt <code>monthly_weekday</code> để lặp lại mỗi tháng theo đúng thứ, trong suốt khoảng start_date–end_date</td></tr>
                    <tr><td><code>weekdays</code></td><td>Bắt buộc khi dùng recurrence — chỉ nhập đúng 1 thứ, vd 2 (Thứ Hai)</td></tr>
                    <tr><td><code>occurrence</code></td><td>Bắt buộc khi dùng recurrence — tuần thứ 1-4 trong tháng, hoặc <code>last</code> cho tuần cuối cùng</td></tr>
                </table>
                <div class="text-muted mb-2">
                    Sự kiện nghỉ lễ toàn trường không khai qua file import theo lớp — khai ở mục "Sự kiện chung" bên dưới form.
                    Nếu một rule trùng ngày với ngày nghỉ lễ, hệ thống sẽ tự động bỏ qua ngày đó thay vì báo lỗi, nên không cần tách rule để né ngày nghỉ.
                </div>

                <h6>Ví dụ 1 dòng rule</h6>
                <pre>row_type,class_code,subject,weekdays,period_from,period_to,start_date,end_date,content
rule,CDHC01,TIN101,"2,4",1,3,2026-09-07,2026-12-20,Học lý thuyết</pre>

                <h6>Ví dụ 1 dòng event (một lần)</h6>
                <pre>row_type,class_code,event_type,title,start_date,end_date,period_from,period_to,note
event,CDHC01,exam,Thi giữa kỳ,2026-10-20,2026-10-20,1,5,Phòng A101</pre>

                <h6>Ví dụ 1 dòng event lặp mỗi tháng (Sinh hoạt lớp)</h6>
                <pre>row_type,class_code,event_type,title,start_date,end_date,weekdays,period_from,period_to,recurrence,occurrence
event,CDHC01,other,Sinh hoạt lớp,2026-09-07,2026-12-20,2,1,2,monthly_weekday,2</pre>
                <div class="text-muted">Dòng trên tạo "Sinh hoạt lớp" vào Thứ Hai tuần thứ 2 của mỗi tháng, từ 07/09 đến 20/12/2026 — hệ thống tự tính ra ngày cụ thể cho từng tháng.</div>

                <h6>Bước 4 — Xem trước (Preview)</h6>
                <div class="text-muted">Upload file rồi bấm "Xem trước" để kiểm tra lịch dự kiến trước khi lưu thật.</div>

                <h6>Bước 5 — Cập nhật kế hoạch</h6>
                <div class="text-muted">Bấm "Cập nhật kế hoạch" để lưu. Hệ thống báo lỗi nếu ngày ngoài học kỳ, môn học không hợp lệ, thiếu cột bắt buộc, trùng lớp/ngày/tiết, hoặc có tháng nào trong học kỳ chưa được rule/event nào phủ tới.</div>
            </div>
        </div>
    </div>

    <div id="monthlyEventModal" class="monthly-event-overlay" style="display:none;">
        <div class="monthly-event-dialog">
            <div class="monthly-event-header">
                <strong>Tạo sự kiện lặp lại hàng tháng</strong>
                <button type="button" class="monthly-event-close" id="monthlyEventClose" aria-label="Đóng">&times;</button>
            </div>
            <div class="monthly-event-body">
                <div class="text-muted small mb-3">
                    Dùng cho sự kiện lặp lại theo đúng thứ mỗi tháng, ví dụ "Sinh hoạt lớp vào thứ Hai tuần thứ 2 mỗi tháng".
                    Hệ thống sẽ tự tính ngày cụ thể cho từng tháng trong khoảng đã chọn và thêm mỗi ngày như một sự kiện riêng —
                    bạn có thể xem lại và xóa từng sự kiện sau khi tạo.
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Tên sự kiện</label>
                        <input type="text" id="monthlyEventTitle" class="form-control form-control-sm" value="Sinh hoạt lớp">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Loại sự kiện</label>
                        <select id="monthlyEventType" class="form-control form-control-sm"></select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Vào thứ</label>
                        <select id="monthlyEventWeekday" class="form-control form-control-sm"></select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Tuần thứ mấy trong tháng</label>
                        <select id="monthlyEventOccurrence" class="form-control form-control-sm">
                            <option value="1">Tuần thứ 1</option>
                            <option value="2" selected>Tuần thứ 2</option>
                            <option value="3">Tuần thứ 3</option>
                            <option value="4">Tuần thứ 4</option>
                            <option value="-1">Tuần cuối cùng</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Tiết bắt đầu</label>
                        <input type="number" min="1" max="9" id="monthlyEventPeriodFrom" class="form-control form-control-sm" value="1">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Tiết kết thúc</label>
                        <input type="number" min="1" max="9" id="monthlyEventPeriodTo" class="form-control form-control-sm" value="2">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Ghi chú</label>
                        <input type="text" id="monthlyEventNote" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Áp dụng từ ngày</label>
                        <input type="date" id="monthlyEventFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Đến ngày</label>
                        <input type="date" id="monthlyEventTo" class="form-control form-control-sm">
                    </div>
                </div>
                <div id="monthlyEventPreview" class="monthly-event-preview"></div>
            </div>
            <div class="monthly-event-footer">
                <button type="button" class="btn btn-sm btn-secondary" id="monthlyEventCancel">Hủy</button>
                <button type="button" class="btn btn-sm btn-primary" id="monthlyEventConfirm">Thêm vào lớp</button>
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
            const serverErrors = @json($errors->all());
            const importUrl = @json(route('schedule.import-template'));
            const globalEventTypes = @json($globalEventTypes);
            const classEventTypes = @json($classEventTypes);
            const allSubjectSuggestions = @json($subjectSuggestions);
            const currentSubjectList = allSubjectSuggestions;
            const subjectPickerModal = document.getElementById('subjectPickerModal');
            const subjectPickerSearch = document.getElementById('subjectPickerSearch');
            const subjectPickerList = document.getElementById('subjectPickerList');
            const subjectPickerClose = document.getElementById('subjectPickerClose');
            let subjectPickerTargetInput = null;
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
            const quickAddRuleBtn = document.getElementById('quickAddRuleBtn');
            const quickAddClassEventBtn = document.getElementById('quickAddClassEventBtn');
            const quickAddHolidayBtn = document.getElementById('quickAddHolidayBtn');
            const quickPreviewBtn = document.getElementById('quickPreviewBtn');
            const quickScrollTopBtn = document.getElementById('quickScrollTopBtn');
            const quickActiveClassLabel = document.getElementById('quickActiveClassLabel');
            const quickActionsBar = document.getElementById('quickActionsBar');
            const quickActionsToggleBtn = document.getElementById('quickActionsToggleBtn');
            const quickActionsBody = document.getElementById('quickActionsBody');
            const quickActionsStorageKey = 'schedule-edit-quick-actions-collapsed';
            let activeClassKey = null;
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
                        endDisplay.setCustomValidity('Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.');
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
                activeClassKey = String(k);
                tabs.querySelectorAll('[data-key]').forEach((btn) => {
                    btn.className =
                        `btn btn-sm mr-2 mb-2 ${btn.dataset.key === String(k) ? 'btn-primary' : 'btn-outline-primary'}`;
                });
                content.querySelectorAll('.class-pane').forEach((pane) => pane.classList.toggle('active', pane
                    .dataset.key === String(k)));
                syncQuickActions();
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
                    hint.textContent = 'Chưa có rule. Bạn có thể thêm rule tay hoặc chỉ upload CSV.';
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

            const collectPreviewPayload = (k) => {
                const pane = document.getElementById(paneId(k));
                if (!pane) return null;

                const rulesBox = document.getElementById(rulesId(k));
                const rules = Array.from(rulesBox ? rulesBox.querySelectorAll('.rule-card') : []).map((card) => {
                    const shape = validateRuleShape(card);
                    if (!shape.valid) return null;
                    const subject = (card.querySelector('input[name$="[subject]"]')?.value || '').trim();
                    if (!subject) return null;
                    return {
                        subject,
                        weekdays: shape.data.weekdays,
                        period_from: shape.data.periodFrom,
                        period_to: shape.data.periodTo,
                        start_date: shape.data.startDate,
                        end_date: shape.data.endDate,
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
                    'Nghỉ lễ'
                );
                globalHolidayEventList.appendChild(card);
                window.ScheduleDatePicker?.init(card);
                updateEventEmpty(globalHolidayEventList, 'Chưa có sự kiện nghỉ lễ nào.');
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
                    'Sự kiện lớp'
                );
                listEl.appendChild(card);
                window.ScheduleDatePicker?.init(card);
                updateEventEmpty(listEl, 'Chưa có sự kiện nào trong lớp này.');
                if (!options.silent) {
                    scrollToEl(card);
                    const firstInput = card.querySelector('input, select, textarea');
                    if (firstInput) firstInput.focus({ preventScroll: true });
                }
            };

            // "Monthly recurrence" helper: given a date range and a weekday +
            // week-of-month rule (e.g. "2nd Monday" or "last Friday"), computes
            // the concrete dates matching that pattern in every month covered
            // by the range. Weekday values follow the same 2 (Monday) .. 8
            // (Sunday) convention used elsewhere (normalizeWeekdays, dayOfWeekIso + 1).
            const isoWeekdayToJsDay = (isoWeekday) => (isoWeekday === 8 ? 0 : isoWeekday - 1);

            const nthWeekdayOfMonth = (year, month, jsDay, occurrence) => {
                if (occurrence === -1) {
                    const lastDay = new Date(year, month + 1, 0);
                    const diff = (lastDay.getDay() - jsDay + 7) % 7;
                    lastDay.setDate(lastDay.getDate() - diff);
                    return lastDay;
                }

                const firstDay = new Date(year, month, 1);
                const diff = (jsDay - firstDay.getDay() + 7) % 7;
                const day = 1 + diff + (occurrence - 1) * 7;
                const date = new Date(year, month, day);
                return date.getMonth() === month ? date : null;
            };

            const computeMonthlyOccurrences = (rangeStart, rangeEnd, isoWeekday, occurrence) => {
                const dates = [];
                if (!rangeStart || !rangeEnd || rangeStart > rangeEnd) return dates;

                const jsDay = isoWeekdayToJsDay(isoWeekday);
                const cursor = new Date(rangeStart.getFullYear(), rangeStart.getMonth(), 1);
                const lastMonth = new Date(rangeEnd.getFullYear(), rangeEnd.getMonth(), 1);

                while (cursor <= lastMonth) {
                    const occurrenceDate = nthWeekdayOfMonth(cursor.getFullYear(), cursor.getMonth(), jsDay, occurrence);
                    if (occurrenceDate && occurrenceDate >= rangeStart && occurrenceDate <= rangeEnd) {
                        dates.push(occurrenceDate);
                    }
                    cursor.setMonth(cursor.getMonth() + 1);
                }

                return dates;
            };

            const monthlyEventModal = document.getElementById('monthlyEventModal');
            const monthlyEventTitle = document.getElementById('monthlyEventTitle');
            const monthlyEventType = document.getElementById('monthlyEventType');
            const monthlyEventWeekday = document.getElementById('monthlyEventWeekday');
            const monthlyEventOccurrence = document.getElementById('monthlyEventOccurrence');
            const monthlyEventPeriodFrom = document.getElementById('monthlyEventPeriodFrom');
            const monthlyEventPeriodTo = document.getElementById('monthlyEventPeriodTo');
            const monthlyEventNote = document.getElementById('monthlyEventNote');
            const monthlyEventFrom = document.getElementById('monthlyEventFrom');
            const monthlyEventTo = document.getElementById('monthlyEventTo');
            const monthlyEventPreview = document.getElementById('monthlyEventPreview');
            const monthlyEventClose = document.getElementById('monthlyEventClose');
            const monthlyEventCancel = document.getElementById('monthlyEventCancel');
            const monthlyEventConfirm = document.getElementById('monthlyEventConfirm');
            let monthlyEventTargetKey = null;

            if (monthlyEventType) {
                monthlyEventType.innerHTML = classEventTypes.map((type) =>
                    `<option value="${esc(type.value)}" ${type.value === 'other' ? 'selected' : ''}>${esc(type.label)}</option>`
                ).join('');
            }

            if (monthlyEventWeekday) {
                monthlyEventWeekday.innerHTML = weekdayOptions.map((d) =>
                    `<option value="${esc(d.value)}" ${d.value === 2 ? 'selected' : ''}>${esc(d.label)}</option>`
                ).join('');
            }

            const refreshMonthlyEventPreview = () => {
                if (!monthlyEventPreview) return;
                const start = monthlyEventFrom?.value ? new Date(`${monthlyEventFrom.value}T00:00:00`) : null;
                const end = monthlyEventTo?.value ? new Date(`${monthlyEventTo.value}T00:00:00`) : null;
                const isoWeekday = Number(monthlyEventWeekday?.value);
                const occurrence = Number(monthlyEventOccurrence?.value);
                const dates = computeMonthlyOccurrences(start, end, isoWeekday, occurrence);

                if (dates.length === 0) {
                    monthlyEventPreview.textContent = 'Không có ngày nào phù hợp trong khoảng đã chọn.';
                    return;
                }

                monthlyEventPreview.textContent = `Sẽ tạo ${dates.length} sự kiện: ${dates.map(formatDateKey).join(', ')}`;
            };

            [monthlyEventWeekday, monthlyEventOccurrence, monthlyEventFrom, monthlyEventTo].forEach((el) => {
                el?.addEventListener('change', refreshMonthlyEventPreview);
            });

            const openMonthlyEventModal = (classKey) => {
                if (!monthlyEventModal) return;
                monthlyEventTargetKey = classKey;
                if (monthlyEventFrom) monthlyEventFrom.value = planStartInput?.value || '';
                if (monthlyEventTo) monthlyEventTo.value = planEndInput?.value || '';
                refreshMonthlyEventPreview();
                monthlyEventModal.style.display = 'flex';
            };

            const closeMonthlyEventModal = () => {
                if (monthlyEventModal) monthlyEventModal.style.display = 'none';
                monthlyEventTargetKey = null;
            };

            monthlyEventClose?.addEventListener('click', closeMonthlyEventModal);
            monthlyEventCancel?.addEventListener('click', closeMonthlyEventModal);
            monthlyEventModal?.addEventListener('click', (event) => {
                if (event.target === monthlyEventModal) closeMonthlyEventModal();
            });

            monthlyEventConfirm?.addEventListener('click', () => {
                if (!monthlyEventTargetKey) return;

                const start = monthlyEventFrom?.value ? new Date(`${monthlyEventFrom.value}T00:00:00`) : null;
                const end = monthlyEventTo?.value ? new Date(`${monthlyEventTo.value}T00:00:00`) : null;
                const isoWeekday = Number(monthlyEventWeekday?.value);
                const occurrence = Number(monthlyEventOccurrence?.value);
                const periodFrom = Number(monthlyEventPeriodFrom?.value) || 1;
                const periodTo = Number(monthlyEventPeriodTo?.value) || periodFrom;
                const title = (monthlyEventTitle?.value || '').trim() || 'Sinh hoạt lớp';
                const eventType = monthlyEventType?.value || 'other';
                const note = monthlyEventNote?.value || '';

                const dates = computeMonthlyOccurrences(start, end, isoWeekday, occurrence);
                if (dates.length === 0) {
                    monthlyEventPreview.textContent = 'Không có ngày nào phù hợp trong khoảng đã chọn.';
                    return;
                }

                dates.forEach((date) => {
                    const dateKey = formatDateKey(date);
                    addClassEvent(monthlyEventTargetKey, {
                        event_type: eventType,
                        title,
                        start_date: dateKey,
                        end_date: dateKey,
                        period_from: periodFrom,
                        period_to: periodTo,
                        note,
                    }, { silent: true });
                });

                validateAllSemesterEvents();
                closeMonthlyEventModal();
            });

            const importGuideModal = document.getElementById('importGuideModal');
            const importGuideClose = document.getElementById('importGuideClose');

            const openImportGuide = () => {
                if (importGuideModal) importGuideModal.style.display = 'flex';
            };

            const closeImportGuide = () => {
                if (importGuideModal) importGuideModal.style.display = 'none';
            };

            document.addEventListener('click', (event) => {
                if (event.target.closest('.open-import-guide')) {
                    event.preventDefault();
                    openImportGuide();
                }
            });

            if (importGuideClose) {
                importGuideClose.addEventListener('click', closeImportGuide);
            }

            if (importGuideModal) {
                importGuideModal.addEventListener('click', (event) => {
                    if (event.target === importGuideModal) closeImportGuide();
                });
            }

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && importGuideModal && importGuideModal.style.display !== 'none') {
                    closeImportGuide();
                }
                if (event.key === 'Escape' && monthlyEventModal && monthlyEventModal.style.display !== 'none') {
                    closeMonthlyEventModal();
                }
            });

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
                        valid = false;
                        const message = `Trùng sự kiện với sự kiện khác tại ${slotKey}.`;
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
                                    `Sự kiện chung '${globalEvent.title}' bị trùng ngày/tiết với sự kiện lớp '${classEvent.title}'.`;
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

                if (!parsedStart) errors.push('Thiếu hoặc sai ngày bắt đầu.');
                if (!parsedEnd) errors.push('Thiếu hoặc sai ngày kết thúc.');

                if (parsedStart && parsedEnd && parsedEnd < parsedStart) {
                    errors.push('Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.');
                }

                if (!Number.isInteger(periodFrom) || periodFrom < 1 || periodFrom > 9) {
                    errors.push('Tiết bắt đầu phải nằm trong khoảng 1-9.');
                }

                if (!Number.isInteger(periodTo) || periodTo < 1 || periodTo > 9) {
                    errors.push('Tiết kết thúc phải nằm trong khoảng 1-9.');
                }

                if (Number.isInteger(periodFrom) && Number.isInteger(periodTo) && periodFrom > periodTo) {
                    errors.push('Tiết kết thúc phải lớn hơn hoặc bằng tiết bắt đầu.');
                }

                if (weekdays.length === 0) {
                    errors.push('Phải chọn ít nhất một thứ học.');
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
                    const classLabel = pane.querySelector('h6')?.textContent || 'lớp';

                    pane.querySelectorAll('.rule-card').forEach((card) => {
                        const checked = validateRuleShape(card);
                        if (!checked.valid) {
                            hasInvalidRule = true;
                            checked.errors.forEach((message) => appendRuleError(card, message));
                            summaryMessages.add(`Có rule dữ liệu chưa hợp lệ trong ${classLabel}.`);
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
                                    summaryMessages.add(`Có rule bị trùng lịch trong ${classLabel}.`);
                                    if (!firstErrorPaneKey) {
                                        firstErrorPaneKey = pane.dataset.key;
                                    }
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
                    summaryMessages.add(`Có lỗi dữ liệu ở lớp ${labelOf(classKey)}.`);
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
                                <small class="text-muted">Thêm rule tay hoặc upload CSV riêng cho lớp này.</small>
                            </div>
                            <div class="pane-actions">
                                <button type="button" class="btn btn-sm btn-primary add-rule" data-key="${esc(k)}">Thêm rule</button>
                                <button type="button" class="btn btn-sm btn-outline-primary add-class-event" data-key="${esc(k)}">Thêm sự kiện</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary add-monthly-event" data-key="${esc(k)}" title="Tạo nhanh sự kiện lặp lại mỗi tháng, ví dụ Sinh hoạt lớp">Lặp hàng tháng</button>
                                <button type="button" class="btn btn-sm btn-outline-info preview-schedule" data-key="${esc(k)}">Xem trước</button>
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
                                <strong title="Import lịch học/sự kiện cho lớp này bằng file Excel/CSV thay vì nhập tay từng rule">Import Excel/CSV</strong>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-info open-import-guide">Hướng dẫn</button>
                                    <a href="${esc(importUrl)}" class="btn btn-sm btn-outline-secondary">Tải template</a>
                                </div>
                            </div>
                            <input type="file" name="import_file[${esc(k)}]" class="form-control form-control-sm" accept=".xlsx,.csv,.txt">
                            <small class="text-muted">Cột chính: row_type, class_code, start_date, end_date, period_from, period_to. Dòng rule dùng thêm subject/content/weekdays; dòng event dùng event_type/title/note. Sự kiện lặp mỗi tháng (vd Sinh hoạt lớp): đặt recurrence=monthly_weekday, weekdays=1 thứ duy nhất, occurrence=1-4 hoặc last.</small>
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
                const addMonthlyEventBtn = e.target.closest('.add-monthly-event');
                if (addMonthlyEventBtn) return openMonthlyEventModal(addMonthlyEventBtn.dataset.key);
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
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            quickActionsToggleBtn?.addEventListener('click', () => {
                const collapsed = quickActionsBar?.classList.contains('is-collapsed') ?? false;
                syncQuickActionsCollapsedState(!collapsed);
            });
            form.addEventListener('submit', function(e) {
                if (!validateRuleConflicts() || !validateAllSemesterEvents()) {
                    e.preventDefault();
                }
            });

            const initialQuickActionsCollapsed = (() => {
                try {
                    return localStorage.getItem(quickActionsStorageKey) === '1';
                } catch (error) {
                    return false;
                }
            })();
            syncQuickActionsCollapsedState(initialQuickActionsCollapsed);
            syncQuickActions();

            syncChecks();
            updateEmpty();
            syncAllDateDisplays();
            validatePlanDates();
            validateRuleConflicts();
            if (globalHolidayEventList) {
                updateEventEmpty(globalHolidayEventList, 'Chưa có sự kiện nghỉ lễ nào.');
            }
            const globalValues = Array.isArray(oldGlobalEvents) ? oldGlobalEvents : Object.values(oldGlobalEvents || {});
            globalValues.forEach((event) => {
                if (event && typeof event === 'object') {
                    addGlobalHoliday(event, { silent: true });
                }
            });
            validateAllSemesterEvents();
            applyServerRuleErrors();
            syncQuickActions();
        });


    </script>
@endsection
