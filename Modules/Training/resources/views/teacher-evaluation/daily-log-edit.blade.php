@extends('layouts.dashboard')

@section('title', 'Chỉnh Sửa Nhật Ký Trực Ban Huấn Luyện')

@section('content')
<style>
    .nhatky-wrapper {
        padding: 24px;
        background: #f5f6fa;
        min-height: 100vh;
    }

    .nhatky-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .nhatky-card {
        background: #fff;
        border: 1px solid #d0d0d0;
        padding: 40px 48px;
        max-width: 1100px;
        margin: 0 auto;
        font-family: 'Times New Roman', Times, serif;
        font-size: 14px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .nhatky-header {
        text-align: center;
        margin-bottom: 18px;
    }

    .nhatky-header .title {
        font-size: 18px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .nhatky-header .subtitle {
        font-style: italic;
        font-size: 14px;
        margin-top: 4px;
    }

    .nhatky-header .duty-officer {
        text-align: left;
        margin-top: 10px;
        font-size: 14px;
    }

    .nhatky-section-title {
        font-weight: bold;
        font-size: 14px;
        margin: 18px 0 8px 0;
    }

    .nhatky-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .nhatky-table th,
    .nhatky-table td {
        border: 1px solid #333;
        padding: 6px 8px;
        vertical-align: middle;
    }

    .nhatky-table th {
        background-color: #f0f0f0;
        text-align: center;
        font-weight: bold;
    }

    .nhatky-table td.center {
        text-align: center;
    }

    .nhatky-table .readonly-cell {
        background: #fafafa;
        color: #333;
    }

    .nhatky-section2 {
        margin-top: 20px;
    }

    .nhatky-section2 .item {
        margin-bottom: 14px;
    }

    .nhatky-section2 .item-label {
        font-size: 14px;
        margin-bottom: 4px;
    }

    .nhatky-section2 .dotted-lines {
        border: 1px solid #bbb;
        padding: 6px 10px;
        min-height: 60px;
        width: 100%;
        font-family: 'Times New Roman', Times, serif;
        font-size: 13px;
        resize: vertical;
    }

    .nhatky-signatures {
        display: flex;
        justify-content: space-around;
        margin-top: 32px;
        text-align: center;
    }

    .nhatky-signatures .sig-block {
        width: 45%;
    }

    .nhatky-signatures .sig-block .sig-title {
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 60px;
    }

    .nhatky-signatures .sig-block .sig-name {
        font-style: italic;
        border-top: 1px dotted #555;
        padding-top: 4px;
    }

    .nhatky-alert-info {
        background: #e8f4f8;
        border-left: 4px solid #17a2b8;
        padding: 10px 14px;
        border-radius: 4px;
        margin-bottom: 14px;
        font-size: 13px;
        color: #0c5460;
    }

    .nhatky-alert-warning {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 10px 14px;
        border-radius: 4px;
        margin-bottom: 14px;
        font-size: 13px;
        color: #856404;
    }

    .nhatky-alert-success {
        background: #d4edda;
        border-left: 4px solid #28a745;
        padding: 10px 14px;
        border-radius: 4px;
        margin-bottom: 14px;
        font-size: 13px;
        color: #155724;
    }

    .nhatky-alert-danger {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 10px 14px;
        border-radius: 4px;
        margin-bottom: 14px;
        font-size: 13px;
        color: #721c24;
    }

    .btn-nhatky-save {
        background: #1a6fad;
        color: #fff;
        border: none;
        padding: 8px 22px;
        font-size: 14px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-nhatky-save:hover {
        background: #155a8a;
    }

    .btn-nhatky-save:disabled {
        background: #99a7b3;
        cursor: not-allowed;
    }

    .btn-nhatky-neutral {
        background: #6c757d;
        color: #fff;
        border: none;
        padding: 8px 22px;
        font-size: 14px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-nhatky-neutral:hover {
        background: #545b62;
        color: #fff;
        text-decoration: none;
    }

    .nhatky-date-picker {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 6px 12px;
        font-size: 14px;
        font-family: inherit;
        cursor: pointer;
    }

    .no-slots-notice {
        text-align: center;
        padding: 20px;
        color: #6c757d;
        font-style: italic;
        border: 1px dashed #ced4da;
        border-radius: 4px;
        margin: 10px 0;
    }

    @media print {
        .nhatky-toolbar {
            display: none !important;
        }

        .nhatky-wrapper {
            background: #fff;
            padding: 0;
        }

        .nhatky-card {
            box-shadow: none;
            border: none;
        }

        .nhatky-section2 .dotted-lines {
            border: none;
            border-bottom: 1px dotted #aaa;
        }
    }
</style>

@php
    $dayNames = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
    $dayOfWeek = $dayNames[$date->dayOfWeek];

    $periodLabels = [
        1 => 'Tiết 1', 2 => 'Tiết 2', 3 => 'Tiết 3', 4 => 'Tiết 4', 5 => 'Tiết 5',
        6 => 'Tiết 6', 7 => 'Tiết 7', 8 => 'Tiết 8', 9 => 'Tiết 9',
    ];

    $statusLabels = [
        'planned' => 'Theo kế hoạch',
        'completed' => 'Đã thực hiện',
        'cancelled' => 'Huỷ',
    ];
@endphp

<div class="nhatky-wrapper">
    <div class="nhatky-toolbar">
        <form method="GET" action="{{ route('duty-log.edit') }}" class="d-flex align-items-center gap-2" style="gap: 8px;">
            <label for="date-picker" style="font-weight: 600; margin-bottom: 0;">Chọn ngày:</label>
            <input
                type="date"
                id="date-picker"
                name="date"
                class="nhatky-date-picker"
                value="{{ $date->toDateString() }}"
                onchange="this.form.submit()"
            >
        </form>

        <a href="{{ route('duty-log.index', ['date' => $date->toDateString()]) }}" class="btn-nhatky-neutral">
            Về trang xem dữ liệu
        </a>
    </div>

    @if (session('success'))
        <div class="nhatky-alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="nhatky-alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="nhatky-alert-danger">
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $canEditDate)
        <div class="nhatky-alert-warning">
            Chỉ được chỉnh sửa và lưu phần 2 cho ngày hiện tại. Môi trường local/testing mới cho phép sửa các ngày khác.
        </div>
    @elseif ($isTestMode && ! $isToday)
        <div class="nhatky-alert-info">
            Bạn đang ở chế độ test ({{ app()->environment() }}), được phép chỉnh sửa ngày khác hôm nay.
        </div>
    @endif

    <form method="POST" action="{{ route('duty-log.submit') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">

        <div class="nhatky-card">
            <div class="nhatky-header">
                <div class="title">Nhật ký trực ban huấn luyện</div>
                <div class="subtitle">
                    {{ $dayOfWeek }}, ngày {{ $date->format('d') }} tháng {{ $date->format('m') }} năm {{ $date->format('Y') }}
                </div>
                <div class="duty-officer" style="margin-top: 10px;">
                    Trực ban HL:
                    <span style="font-weight: bold;">{{ $dutyOfficer->name }}</span>
                    <span style="font-size: 12px; color: #555; margin-left: 8px;">({{ $dutyOfficer->employee_code ?? 'N/A' }})</span>
                </div>
            </div>

            <div class="nhatky-section-title">1. Quân số, nội dung huấn luyện (chỉ xem dữ liệu)</div>

            @if ($slots->isEmpty())
                <div class="no-slots-notice">
                    Không có tiết học nào được phân công cho ngày {{ $date->format('d/m/Y') }}.
                </div>
            @else
                <table class="nhatky-table">
                    <thead>
                        <tr>
                            <th style="width: 7%;">Lớp</th>
                            <th style="width: 6%;">Tiết</th>
                            <th style="width: 8%;">Phòng học</th>
                            <th style="width: 5%;">QS</th>
                            <th style="width: 5%;">V</th>
                            <th style="width: 15%;">Môn học</th>
                            <th style="width: 14%;">Giảng viên<br>CB Phụ trách</th>
                            <th style="width: 10%;">Trạng thái</th>
                            <th style="width: 30%;">Nhận xét</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slots as $slot)
                            @php
                                $log = $logs->get($slot->id);
                                $slotEvaluation = $slotEvaluations->get($slot->id);
                                $statusValue = $log?->result_status ?? 'completed';
                                $attendanceCount = $slotEvaluation?->attendance_count ?? $log?->attendance_count ?? 0;
                                $absentCount = $slotEvaluation?->absent_count ?? $log?->absent_count ?? 0;
                                $remarks = $slotEvaluation?->comment ?? $log?->remarks;
                            @endphp
                            <tr>
                                <td class="center readonly-cell">{{ $slot->trainingClass?->code ?? '—' }}</td>
                                <td class="center readonly-cell">{{ $periodLabels[$slot->period_number] ?? ('Tiết ' . $slot->period_number) }}</td>
                                <td class="center readonly-cell">{{ $slot->room?->code ?? '—' }}</td>
                                <td class="center">{{ $attendanceCount }}</td>
                                <td class="center">{{ $absentCount }}</td>
                                <td class="readonly-cell">
                                    @if ($slot->subjectModel)
                                        <strong>{{ $slot->subjectModel->code }}</strong>
                                        @if ($slot->subjectLesson)
                                            <br><small>Bài {{ $slot->subjectLesson->lesson_no }}: {{ Str::limit($slot->subjectLesson->title, 40) }}</small>
                                        @endif
                                    @elseif ($slot->subject)
                                        {{ $slot->subject }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="readonly-cell">
                                    {{ $slot->teacher?->name ?? '—' }}
                                    @if ($slot->teacher?->employee_code)
                                        <br><small style="color: #888;">{{ $slot->teacher->employee_code }}</small>
                                    @endif
                                </td>
                                <td>{{ $statusLabels[$statusValue] ?? 'Đã thực hiện' }}</td>
                                <td style="white-space: pre-line;">{{ $remarks ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="nhatky-section-title" style="margin-top: 24px;">2. Nhận xét hoạt động huấn luyện trong ngày</div>

            <div class="nhatky-section2">
                <div class="item">
                    <div class="item-label">a) Thực hiện kế hoạch huấn luyện</div>
                    <textarea
                        name="training_plan_comment"
                        class="dotted-lines"
                        rows="3"
                        {{ $canEditDate ? '' : 'disabled' }}
                    >{{ old('training_plan_comment', $dailySummary?->training_plan_comment) }}</textarea>
                </div>

                <div class="item">
                    <div class="item-label">b) Thực hiện quy chế, quy định về GDĐT</div>
                    <textarea
                        name="regulation_comment"
                        class="dotted-lines"
                        rows="3"
                        {{ $canEditDate ? '' : 'disabled' }}
                    >{{ old('regulation_comment', $dailySummary?->regulation_comment) }}</textarea>
                </div>

                <div class="item">
                    <div class="item-label">c) Quản lý, sử dụng hội trường, vật chất, trang thiết bị đào tạo</div>
                    <textarea
                        name="facility_comment"
                        class="dotted-lines"
                        rows="3"
                        {{ $canEditDate ? '' : 'disabled' }}
                    >{{ old('facility_comment', $dailySummary?->facility_comment) }}</textarea>
                </div>

                <div class="item">
                    <div class="item-label">d) Những việc cần tiếp tục xử lý</div>
                    <textarea
                        name="followup_comment"
                        class="dotted-lines"
                        rows="3"
                        {{ $canEditDate ? '' : 'disabled' }}
                    >{{ old('followup_comment', $dailySummary?->followup_comment) }}</textarea>
                </div>
            </div>

            <div class="nhatky-signatures">
                <div class="sig-block">
                    <div class="sig-title">Chỉ huy phòng</div>
                    <div class="sig-name">.....................................</div>
                </div>
                <div class="sig-block">
                    <div class="sig-title">Trực ban huấn luyện</div>
                    <div class="sig-name">{{ $dutyOfficer->name }}</div>
                </div>
            </div>
        </div>

        <div style="max-width: 1100px; margin: 16px auto 0 auto; display: flex; gap: 12px; justify-content: flex-end;">
            <button type="submit" class="btn-nhatky-save" {{ $canEditDate ? '' : 'disabled' }}>
                Lưu nhận xét phần 2
            </button>
        </div>
    </form>
</div>
@endsection
