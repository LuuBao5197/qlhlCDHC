@extends('layouts.dashboard')

@section('title', 'Đánh Giá Tiết Học Của Giáo Viên')

@section('content')
<style>
    .slot-eval-wrapper {
        padding: 24px;
        background: #f5f6fa;
        min-height: 100vh;
    }

    .slot-eval-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .slot-eval-card {
        background: #fff;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        padding: 24px;
        max-width: 1100px;
        margin: 0 auto;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
    }

    .slot-eval-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .slot-eval-subtitle {
        color: #606975;
        margin-bottom: 16px;
    }

    .slot-eval-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .slot-eval-table th,
    .slot-eval-table td {
        border: 1px solid #d9d9d9;
        padding: 8px;
        vertical-align: top;
    }

    .slot-eval-table th {
        background: #f2f3f5;
        text-align: center;
        font-weight: 700;
    }

    .slot-eval-rating {
        width: 120px;
        text-align: center;
    }

    .slot-eval-count {
        width: 90px;
        text-align: center;
    }

    .slot-eval-comment {
        width: 100%;
        min-height: 56px;
        resize: vertical;
    }

    .slot-eval-actions {
        margin-top: 16px;
        display: flex;
        justify-content: flex-end;
    }

    .slot-eval-btn {
        background: #1565c0;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 9px 20px;
        font-weight: 600;
        cursor: pointer;
    }

    .slot-eval-alert {
        border-radius: 4px;
        padding: 10px 12px;
        margin-bottom: 12px;
    }

    .slot-eval-alert.success {
        background: #d4edda;
        border-left: 4px solid #28a745;
        color: #155724;
    }

    .slot-eval-alert.warning {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        color: #856404;
    }

    .slot-eval-alert.error {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        color: #721c24;
    }

    .slot-eval-date-picker {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 6px 10px;
    }

    .slot-eval-field-error {
        color: #dc3545;
        font-size: 12px;
        margin-top: 4px;
    }

    .slot-eval-rating.is-invalid {
        border-color: #dc3545;
    }

    .slot-eval-comment.is-invalid {
        border-color: #dc3545;
    }
</style>

@php
    $periodLabels = [
        1 => 'Tiết 1', 2 => 'Tiết 2', 3 => 'Tiết 3', 4 => 'Tiết 4', 5 => 'Tiết 5',
        6 => 'Tiết 6', 7 => 'Tiết 7', 8 => 'Tiết 8', 9 => 'Tiết 9',
    ];

    $ratingLevels = [
        'tot' => 'Tốt',
        'kha' => 'Khá',
        'trung_binh' => 'Trung bình',
        'yeu' => 'Yếu',
    ];
@endphp

<div class="slot-eval-wrapper">
    <div class="slot-eval-toolbar" style="max-width:1100px; margin:0 auto 12px auto;">
        <form method="GET" action="{{ route('teacher-slot-evaluations.index') }}" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            @if ($isAdminMode ?? false)
                <input type="hidden" name="admin_backfill" value="1">
                <label for="slot-eval-teacher" style="margin:0; font-weight:600;">Giáo viên:</label>
                <select id="slot-eval-teacher" name="teacher_id" class="slot-eval-date-picker" onchange="this.form.submit()">
                    <option value="">-- Chọn giáo viên --</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t->id }}" @selected(($selectedTeacherId ?? null) === $t->id)>
                            {{ $t->name }} ({{ $t->teacher_code }})
                        </option>
                    @endforeach
                </select>
            @endif
            <label for="slot-eval-date" style="margin:0; font-weight:600;">Chọn ngày:</label>
            <input
                type="date"
                id="slot-eval-date"
                name="date"
                class="slot-eval-date-picker"
                value="{{ $date->toDateString() }}"
                onchange="this.form.submit()"
            >
        </form>
    </div>

    <div class="slot-eval-card">
        <div class="slot-eval-title">Đánh giá tiết học của giáo viên</div>
        <div class="slot-eval-subtitle">
            @if ($isAdminMode ?? false)
                Giáo viên: <strong>{{ $teacher?->name ?? 'Chưa chọn' }}</strong>
                @if ($teacher?->teacher_code)
                    ({{ $teacher->teacher_code }})
                @endif
                <span class="badge" style="background:#ffe69c; color:#664d03;">Admin bổ sung dữ liệu cũ</span>
            @else
                Giáo viên: <strong>{{ $authUser?->name ?? 'N/A' }}</strong>
                @if ($authUser?->employee_code)
                    ({{ $authUser->employee_code }})
                @endif
            @endif
            | Ngày: <strong>{{ $date->format('d/m/Y') }}</strong>
        </div>

        @include('partials._admin-backfill-badge', ['adminBackfillLog' => $evaluations->first()?->adminBackfillLog])

        @if (session('success'))
            <div class="slot-eval-alert success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="slot-eval-alert error">
                <ul style="margin:0; padding-left:16px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($teacher === null)
            <div class="slot-eval-alert warning">
                Tài khoản của bạn chưa được ánh xạ sang hồ sơ giáo viên trong danh mục giảng viên. Vui lòng liên hệ quản trị để cập nhật.
            </div>
        @elseif ($slots->isEmpty())
            <div class="slot-eval-alert warning">
                Không có tiết học nào do bạn phụ trách trong ngày {{ $date->format('d/m/Y') }}.
            </div>
        @else
            <form method="POST" action="{{ route('teacher-slot-evaluations.submit') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">

                @if ($isAdminMode ?? false)
                    <input type="hidden" name="admin_backfill" value="1">
                    <input type="hidden" name="teacher_id" value="{{ $teacher?->id }}">
                    <div class="slot-eval-alert" style="background:#fff3cd; border:1px solid #ffe69c; color:#664d03; margin-bottom:12px; padding:10px;">
                        <label style="font-weight:600;">Lý do bổ sung dữ liệu cũ <span style="color:#dc3545;">*</span></label>
                        <textarea name="admin_backfill_reason" class="slot-eval-comment" style="width:100%;" rows="2"
                            placeholder="Vi du: Nhap bu danh gia tiet hoc truoc khi he thong van hanh...">{{ old('admin_backfill_reason') }}</textarea>
                        @error('admin_backfill_reason')
                            <div class="slot-eval-field-error">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <table class="slot-eval-table">
                    <thead>
                        <tr>
                            <th style="width:80px;">Tiết</th>
                            <th style="width:110px;">Lớp</th>
                            <th style="width:220px;">Môn/Bài học</th>
                            <th style="width:100px;">Phòng</th>
                            <th style="width:100px;">Quân số</th>
                            <th style="width:100px;">Vắng</th>
                            <th style="width:130px;">Xếp loại</th>
                            <th>Nhận xét</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($slots as $slot)
                            @php
                                $evaluation = $evaluations->get($slot->id);
                                $slotKey = $slot->id;
                                $defaultAttendanceCount = $evaluation?->attendance_count ?? $slot->trainingClass?->total_students;
                            @endphp
                            <tr>
                                <td style="text-align:center;">{{ $periodLabels[$slot->period_number] ?? ('Tiết ' . $slot->period_number) }}</td>
                                <td style="text-align:center;">{{ $slot->trainingClass?->code ?? '—' }}</td>
                                <td>
                                    @if ($slot->subjectModel)
                                        <strong>{{ $slot->subjectModel->code }}</strong>
                                        @if ($slot->subjectLesson)
                                            <br>
                                            <small>Bài {{ $slot->subjectLesson->lesson_no }}: {{ Str::limit($slot->subjectLesson->title, 70) }}</small>
                                        @endif
                                    @elseif ($slot->subject)
                                        {{ $slot->subject }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="text-align:center;">{{ $slot->room?->code ?? '—' }}</td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="slot-eval-count"
                                        name="slots[{{ $slotKey }}][attendance_count]"
                                        value="{{ old("slots.{$slotKey}.attendance_count", $defaultAttendanceCount) }}"
                                        placeholder="QS"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="slot-eval-count"
                                        name="slots[{{ $slotKey }}][absent_count]"
                                        value="{{ old("slots.{$slotKey}.absent_count", $evaluation?->absent_count) }}"
                                        placeholder="Vắng"
                                    >
                                </td>
                                <td>
                                    <select
                                        class="slot-eval-rating {{ $errors->has("slots.{$slotKey}.rating_level") ? 'is-invalid' : '' }}"
                                        name="slots[{{ $slotKey }}][rating_level]"
                                    >
                                        <option value="">Chọn</option>
                                        @foreach ($ratingLevels as $ratingValue => $ratingLabel)
                                            <option
                                                value="{{ $ratingValue }}"
                                                @selected(old("slots.{$slotKey}.rating_level", $evaluation?->rating_level) === $ratingValue)
                                            >
                                                {{ $ratingLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("slots.{$slotKey}.rating_level")
                                        <div class="slot-eval-field-error">{{ $message }}</div>
                                    @enderror
                                </td>
                                <td>
                                    <textarea
                                        name="slots[{{ $slotKey }}][comment]"
                                        class="slot-eval-comment {{ $errors->has("slots.{$slotKey}.comment") ? 'is-invalid' : '' }}"
                                        placeholder="Nhận xét chất lượng tiết học..."
                                    >{{ old("slots.{$slotKey}.comment", $evaluation?->comment) }}</textarea>
                                    @error("slots.{$slotKey}.comment")
                                        <div class="slot-eval-field-error">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="slot-eval-actions">
                    <button type="submit" class="slot-eval-btn">Lưu đánh giá tiết học</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
