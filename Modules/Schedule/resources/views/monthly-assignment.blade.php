@extends('layouts.dashboard')

@section('title', 'Phan cong lich giang day theo thang')

@section('content')
    @php
        $statusStyles = [
            'draft' => 'badge-secondary',
            'pending' => 'badge-warning',
            'processing' => 'badge-info',
            'submitted' => 'badge-primary',
            'approved' => 'badge-success',
            'rejected' => 'badge-danger',
            'returned' => 'badge-dark',
        ];

        $statusClass = $statusStyles[$monthlySchedule->status] ?? 'badge-light';
        $oldSlots = old('slots', []);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1 text-primary font-weight-bold">
                                <i class="fas fa-chalkboard-teacher mr-2"></i>Phân công giảng dạy tháng
                                {{ $monthlySchedule->month }}/{{ $monthlySchedule->year }}
                            </h4>
                            <p class="mb-0" style="color: #858796;">
                                Kế hoạch: <strong class="text-dark">{{ $monthlySchedule->plan?->name ?? '-' }}</strong>
                                <span class="mx-1">|</span>
                                Khoa: <strong class="text-dark">{{ $subjects->first()?->department?->name ?? '-' }}</strong>
                            </p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="badge {{ $statusClass }} px-3 py-2"
                                style="font-size: .85rem;">{{ ucfirst($monthlySchedule->status) }}</span>
                        </div>
                    </div>

                    <div class="mt-3 d-flex flex-wrap">
                        <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                            <i class="fas fa-arrow-left mr-1"></i>Quay lại
                        </a>

                        @if ($canSubmitToTrainingOffice)
                            <form method="POST"
                                action="{{ route('monthly-schedule.submit-training', $monthlySchedule->id) }}"
                                class="mr-2 mb-2">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="comment"
                                        placeholder="Ghi chú gửi duyệt (optional)">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane mr-1"></i>Gửi PDT duyệt
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        @if ($monthlySchedule->status === 'approved')
                            <span class="text-success align-self-center mb-2">
                                <i class="fas fa-check-circle mr-1"></i>Lịch này đã được phê duyệt.
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong><i class="fas fa-exclamation-triangle mr-1"></i>Có lỗi:</strong>
            <ul class="mb-0 pl-3 mt-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($subjects->isNotEmpty())
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-left-info shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center cursor-pointer" data-toggle="collapse"
                            data-target="#subjectListCollapse">
                            <h6 class="mb-0 text-info font-weight-bold">
                                <i class="fas fa-book mr-1"></i>Môn học khoa phụ trách ({{ $subjects->count() }} môn)
                            </h6>
                            <i class="fas fa-chevron-down ml-auto text-info"></i>
                        </div>
                        <div class="collapse mt-2" id="subjectListCollapse">
                            <div class="d-flex flex-wrap">
                                @foreach ($subjects as $subject)
                                    <span class="badge badge-light border mr-2 mb-1 px-2 py-1" style="font-size: .8rem;">
                                        <strong>{{ $subject->code }}</strong> — {{ $subject->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 text-dark font-weight-bold">
                            <i class="fas fa-calendar-alt mr-1 text-primary"></i>Danh sách tiết học
                        </h5>
                        <div>
                            <span class="badge badge-light border px-2 py-1 mr-1"><span
                                    class="period-dot period-morning-dot"></span> Sáng (T1-5)</span>
                            <span class="badge badge-light border px-2 py-1 mr-1"><span
                                    class="period-dot period-afternoon-dot"></span> Chiều (T6-9)</span>
                            <span class="badge badge-primary px-2 py-1">{{ $monthlySchedule->scheduleSlots->count() }}
                                tiết</span>
                        </div>
                    </div>

                    @php
                        $slotsByDate = $monthlySchedule->scheduleSlots->groupBy(
                            fn($slot) => optional($slot->date)->format('Y-m-d'),
                        );
                        $dates = $slotsByDate->keys()->sort()->values();
                        $dateChunks = $dates->chunk(3);
                        $flatIndex = 0;
                        $dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
                    @endphp

                    {{-- ═══ TOOLBAR: Filter + Bulk Assign + Auto-fill ═══ --}}
                    <div class="card border-0 bg-light mb-3" id="assignToolbar">
                        <div class="card-body py-2 px-3">
                            <div class="row align-items-end">
                                {{-- Filter: Lớp --}}
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">Lọc lớp</label>
                                    <select id="filterClass" class="form-control form-control-sm" style="min-width:120px;">
                                        <option value="">Tất cả lớp</option>
                                    </select>
                                </div>
                                {{-- Filter: Buổi --}}
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">Lọc buổi</label>
                                    <select id="filterSession" class="form-control form-control-sm" style="min-width:120px;">
                                        <option value="">Tất cả</option>
                                        <option value="morning">&#9728; Sáng (T1-5)</option>
                                        <option value="afternoon">&#9789; Chiều (T6-9)</option>
                                    </select>
                                </div>
                                {{-- Filter count --}}
                                <div class="col-auto mb-2 align-self-end">
                                    <span id="filterCount" class="badge badge-secondary py-1 px-2" style="font-size:.8rem;"></span>
                                </div>
                                {{-- Divider --}}
                                <div class="col-auto mb-2 border-left ml-1 pl-3">
                                    <label class="small font-weight-bold text-muted mb-1">Gán nhanh GV</label>
                                    <div class="input-group input-group-sm">
                                        <select id="bulkTeacher" class="form-control" style="min-width:180px;">
                                            <option value="">-- Chọn GV --</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">{{ $teacher->name }}@if($teacher->teacher_code) ({{ $teacher->teacher_code }})@endif</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="bulkApplyBtn" title="Gán GV cho các tiết đã chọn">
                                                <i class="fas fa-user-check mr-1"></i>Gán
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                {{-- Auto-fill --}}
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">&nbsp;</label><br>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="autoFillBtn" title="Tự động gợi ý GV theo cùng môn + lớp">
                                        <i class="fas fa-magic mr-1"></i>Auto-fill
                                    </button>
                                </div>
                                {{-- Select all visible --}}
                                <div class="col-auto mb-2 align-self-end">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAllSlots">
                                        <label class="custom-control-label small" for="selectAllSlots">Chọn tất cả</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('monthly-schedule.assignment.save', $monthlySchedule->id) }}">
                        @csrf

                        @if ($dateChunks->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Không có tiết học nào để phân công.</p>
                            </div>
                        @else
                            {{-- Tab navigation --}}
                            <ul class="nav nav-tabs flex-nowrap" id="scheduleTabs" role="tablist" style="overflow-x: auto;">
                                @foreach ($dateChunks as $chunkIdx => $chunk)
                                    @php
                                        $firstDate = \Carbon\Carbon::parse($chunk->first());
                                        $lastDate = \Carbon\Carbon::parse($chunk->last());
                                        $tabLabel = $firstDate->format('d/m') . ' → ' . $lastDate->format('d/m');
                                        $chunkSlotCount = $chunk->sum(fn($d) => $slotsByDate[$d]->count());
                                    @endphp
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link text-nowrap {{ $chunkIdx === 0 ? 'active' : '' }}"
                                            id="tab-{{ $chunkIdx }}-tab" data-toggle="tab"
                                            href="#tab-{{ $chunkIdx }}" role="tab">
                                            {{ $tabLabel }}
                                            <span
                                                class="badge badge-pill {{ $chunkIdx === 0 ? 'badge-light' : 'badge-secondary' }} ml-1">{{ $chunkSlotCount }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- Tab content --}}
                            <div class="tab-content border border-top-0 rounded-bottom p-3" id="scheduleTabContent">
                                @foreach ($dateChunks as $chunkIdx => $chunk)
                                    <div class="tab-pane fade {{ $chunkIdx === 0 ? 'show active' : '' }}"
                                        id="tab-{{ $chunkIdx }}" role="tabpanel">

                                        @foreach ($chunk as $dateKey)
                                            @php
                                                $dateSlots = $slotsByDate[$dateKey]->sortBy('period_number');
                                                $dateObj = \Carbon\Carbon::parse($dateKey);
                                                $dayName = $dayNames[$dateObj->dayOfWeek] ?? '';
                                            @endphp

                                            <div class="mb-4">
                                                <div class="date-header d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <i class="fas fa-calendar-day mr-2"></i>
                                                        <strong>{{ $dateObj->format('d/m/Y') }}</strong>
                                                        <span class="ml-1">({{ $dayName }})</span>
                                                    </span>
                                                    <span class="badge">{{ $dateSlots->count() }} tiết</span>
                                                </div>

                                                <div class="table-responsive">
                                                    <table
                                                        class="table table-bordered table-sm table-hover align-middle mb-0 slot-table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 35px;"><input type="checkbox" class="select-all-date" title="Chọn tất cả ngày này"></th>
                                                                <th style="width: 55px;">Tiết</th>
                                                                <th style="min-width: 90px;">Lớp</th>
                                                                <th style="min-width: 170px;">Giảng viên</th>
                                                                <th style="min-width: 140px;">Môn học</th>
                                                                <th style="min-width: 150px;">Bài học</th>
                                                                <th style="min-width: 100px;">Phòng</th>
                                                                <th style="min-width: 150px;">Nội dung</th>
                                                                <th style="min-width: 130px;">Ghi chú</th>
                                                                <th style="width: 105px;">Trạng thái</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($dateSlots as $slot)
                                                                @php
                                                                    $idx = $flatIndex++;
                                                                    $oldSlot = $oldSlots[$idx] ?? null;
                                                                    $hasTeacher = !empty(
                                                                        $oldSlot['teacher_id'] ?? $slot->teacher_id
                                                                    );
                                                                    $rowClass = $hasTeacher
                                                                        ? 'slot-assigned'
                                                                        : 'slot-unassigned';
                                                                @endphp
                                                                <tr class="{{ $rowClass }}"
                                                                    data-class="{{ $slot->trainingClass?->code ?? '' }}"
                                                                    data-class-id="{{ $slot->class_id }}"
                                                                    data-session="{{ $slot->period_number <= 5 ? 'morning' : 'afternoon' }}"
                                                                    data-subject-id="{{ $slot->subject_id }}"
                                                                    data-date="{{ optional($slot->date)->format('Y-m-d') }}"
                                                                    data-period="{{ $slot->period_number }}">
                                                                    <td class="text-center">
                                                                        <input type="checkbox" class="slot-check">
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span
                                                                            class="period-badge {{ $slot->period_number <= 5 ? 'period-morning' : 'period-afternoon' }}">
                                                                            {{ $slot->period_number }}
                                                                        </span>
                                                                        <input type="hidden"
                                                                            name="slots[{{ $idx }}][id]"
                                                                            value="{{ $slot->id }}">
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span
                                                                            class="class-pill">{{ $slot->trainingClass?->code ?? '-' }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <select
                                                                            name="slots[{{ $idx }}][teacher_id]"
                                                                            class="form-control form-control-sm">
                                                                            <option value="">-- Chọn GV --</option>
                                                                            @foreach ($teachers as $teacher)
                                                                                @php $selectedTeacher = $oldSlot['teacher_id'] ?? $slot->teacher_id; @endphp
                                                                                <option value="{{ $teacher->id }}"
                                                                                    @selected((int) $selectedTeacher === (int) $teacher->id)>
                                                                                    {{ $teacher->name }}@if ($teacher->employee_code)
                                                                                        ({{ $teacher->employee_code }})
                                                                                    @endif
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $selectedSubjectId =
                                                                                $oldSlot['subject_id'] ??
                                                                                $slot->subject_id;
                                                                            $selectedSubject = $subjects->firstWhere(
                                                                                'id',
                                                                                $selectedSubjectId,
                                                                            );
                                                                            $subjectLabel = $selectedSubject
                                                                                ? $selectedSubject->code .
                                                                                    ' - ' .
                                                                                    $selectedSubject->name
                                                                                : $slot->subject ?? '--';
                                                                        @endphp
                                                                        <input type="hidden"
                                                                            name="slots[{{ $idx }}][subject_id]"
                                                                            value="{{ $selectedSubjectId }}">
                                                                        <div class="font-weight-bold"
                                                                            style="font-size: .82rem;">{{ $subjectLabel }}
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $selectedLesson =
                                                                                $oldSlot['subject_lesson_id'] ??
                                                                                $slot->subject_lesson_id;
                                                                            $lessonOptions = $subjectLessons->where(
                                                                                'subject_id',
                                                                                $selectedSubjectId,
                                                                            );
                                                                        @endphp
                                                                        <select
                                                                            name="slots[{{ $idx }}][subject_lesson_id]"
                                                                            class="form-control form-control-sm">
                                                                            <option value="">-- Chọn bài --</option>
                                                                            @foreach ($lessonOptions as $lesson)
                                                                                <option value="{{ $lesson->id }}"
                                                                                    @selected((int) $selectedLesson === (int) $lesson->id)>
                                                                                    B{{ $lesson->lesson_no }}:
                                                                                    {{ $lesson->title }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <select name="slots[{{ $idx }}][room_id]"
                                                                            class="form-control form-control-sm">
                                                                            <option value="">-- Phòng --</option>
                                                                            @foreach ($rooms as $room)
                                                                                @php $selectedRoom = $oldSlot['room_id'] ?? $slot->room_id; @endphp
                                                                                <option value="{{ $room->id }}"
                                                                                    @selected((int) $selectedRoom === (int) $room->id)>
                                                                                    {{ $room->code }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            name="slots[{{ $idx }}][content]"
                                                                            value="{{ $oldSlot['content'] ?? $slot->content }}"
                                                                            maxlength="500" placeholder="Nội dung">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control form-control-sm"
                                                                            name="slots[{{ $idx }}][note]"
                                                                            value="{{ $oldSlot['note'] ?? $slot->note }}"
                                                                            maxlength="500" placeholder="Ghi chú">
                                                                    </td>
                                                                    <td>
                                                                        @php $selectedStatus = $oldSlot['slot_status'] ?? ($slot->slot_status ?? 'planned'); @endphp
                                                                        <select
                                                                            name="slots[{{ $idx }}][slot_status]"
                                                                            class="form-control form-control-sm">
                                                                            <option value="planned"
                                                                                @selected($selectedStatus === 'planned')>planned
                                                                            </option>
                                                                            <option value="updated"
                                                                                @selected($selectedStatus === 'updated')>updated
                                                                            </option>
                                                                            <option value="cancelled"
                                                                                @selected($selectedStatus === 'cancelled')>cancelled
                                                                            </option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 d-flex flex-wrap align-items-center">
                            <button type="submit" class="btn btn-success mr-2 mb-2">
                                <i class="fas fa-save mr-1"></i>Lưu phân công
                            </button>
                            @if ($canSubmitToTrainingOffice)
                                <small class="text-muted mb-2">Sau khi lưu, bấm "Gửi PDT duyệt" ở trên để trình lịch
                                    tháng.</small>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #4e73df;
        }

        .nav-tabs .nav-link {
            padding: .55rem 1rem;
            font-size: .85rem;
            color: #858796;
            border: 1px solid transparent;
            transition: all .2s;
        }

        .nav-tabs .nav-link:hover {
            color: #4e73df;
            background: #f0f3ff;
        }

        .nav-tabs .nav-link.active {
            font-weight: 700;
            color: #fff;
            background: #4e73df;
            border-color: #4e73df;
            border-radius: .35rem .35rem 0 0;
        }

        .tab-content {
            background: transparent;
        }

        /* Date header */
        .date-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: #fff;
            padding: .5rem .85rem;
            border-radius: .35rem;
            margin-bottom: .5rem;
            font-size: .9rem;
        }

        .date-header .badge {
            background: rgba(255, 255, 255, .2);
            color: #fff;
        }

        /* Table head */
        .slot-table thead th {
            background: #f8f9fc;
            color: #4e73df;
            font-weight: 600;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            border-bottom: 2px solid #4e73df;
            vertical-align: middle;
        }

        .slot-table tbody tr:hover {
            background: #eaecf4 !important;
        }

        /* Period badge (circle) */
        .period-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: 700;
            font-size: .85rem;
        }

        .period-morning {
            background: #d4edda;
            color: #155724;
        }

        .period-afternoon {
            background: #fff3cd;
            color: #856404;
        }

        /* Legend dots */
        .period-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
        }

        .period-morning-dot {
            background: #28a745;
        }

        .period-afternoon-dot {
            background: #ffc107;
        }

        /* Class code pill */
        .class-pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-weight: 600;
            font-size: .78rem;
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Row tint: assigned vs unassigned */
        .slot-assigned {
            background: #f0fff4 !important;
        }

        .slot-unassigned {
            background: #fffcf0 !important;
        }

        /* Form controls inside table */
        .slot-table select,
        .slot-table input[type="text"] {
            border: 1px solid #d1d3e2;
            border-radius: .25rem;
            font-size: .82rem;
            transition: border-color .15s;
        }

        .slot-table select:focus,
        .slot-table input[type="text"]:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 .15rem rgba(78, 115, 223, .25);
        }

        /* Card accent */
        .border-left-primary {
            border-left: .25rem solid #4e73df !important;
        }

        .border-left-info {
            border-left: .25rem solid #36b9cc !important;
        }

        /* Toolbar */
        #assignToolbar { border: 1px solid #e3e6f0; border-radius: .35rem; }
        #assignToolbar label { display: block; margin-bottom: 2px; }

        /* Checkbox in table */
        .slot-check { width: 16px; height: 16px; cursor: pointer; }
        .select-all-date { width: 15px; height: 15px; cursor: pointer; }

        /* Highlight checked rows */
        .slot-check:checked { accent-color: #4e73df; }
        .slot-table tbody tr.checked-row { background: #dbeafe !important; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var allRows     = Array.prototype.slice.call(document.querySelectorAll('.slot-table tbody tr'));
        var filterClass = document.getElementById('filterClass');
        var filterSess  = document.getElementById('filterSession');
        var filterCount = document.getElementById('filterCount');
        var bulkTeacher = document.getElementById('bulkTeacher');
        var bulkApply   = document.getElementById('bulkApplyBtn');
        var selectAll   = document.getElementById('selectAllSlots');
        var autoFillBtn = document.getElementById('autoFillBtn');

        function getTeacherSelect(row) {
            return row.querySelector('select[name*="[teacher_id]"]');
        }

        function getSlotKey(row) {
            var date = row.getAttribute('data-date') || '';
            var period = row.getAttribute('data-period') || '';
            return date + '|' + period;
        }

        function markRowAssignedState(row) {
            var sel = getTeacherSelect(row);
            var hasTeacher = !!(sel && sel.value);
            row.classList.toggle('slot-assigned', hasTeacher);
            row.classList.toggle('slot-unassigned', !hasTeacher);
        }

        function buildUsedTeacherMap() {
            var map = {};
            allRows.forEach(function (row) {
                var sel = getTeacherSelect(row);
                if (!sel || !sel.value) {
                    return;
                }

                var key = getSlotKey(row);
                if (!map[key]) {
                    map[key] = {};
                }
                map[key][String(sel.value)] = true;
            });
            return map;
        }

        function canAssignTeacherToRow(row, teacherId, usedMap) {
            if (!teacherId) {
                return true;
            }

            var sel = getTeacherSelect(row);
            if (!sel) {
                return false;
            }

            var key = getSlotKey(row);
            var used = usedMap[key] || {};
            var currentValue = sel.value ? String(sel.value) : '';
            var targetValue = String(teacherId);

            if (currentValue === targetValue) {
                return true;
            }

            return !used[targetValue];
        }

        function refreshTeacherOptions() {
            var usedMap = buildUsedTeacherMap();

            allRows.forEach(function (row) {
                var sel = getTeacherSelect(row);
                if (!sel) {
                    return;
                }

                var key = getSlotKey(row);
                var used = usedMap[key] || {};
                var currentValue = sel.value ? String(sel.value) : '';

                Array.prototype.slice.call(sel.options).forEach(function (opt) {
                    if (!opt.value) {
                        opt.disabled = false;
                        opt.hidden = false;
                        return;
                    }

                    var blocked = !!used[String(opt.value)] && String(opt.value) !== currentValue;
                    opt.disabled = blocked;
                    opt.hidden = blocked;
                });

                markRowAssignedState(row);
            });
        }

        /* ══════════ POPULATE CLASS FILTER ══════════ */
        var classSet = {};
        allRows.forEach(function(r) { if (r.dataset.class) classSet[r.dataset.class] = true; });
        Object.keys(classSet).sort().forEach(function(cls) {
            var o = document.createElement('option');
            o.value = cls; o.textContent = cls;
            filterClass.appendChild(o);
        });

        /* ══════════ FILTER LOGIC ══════════ */
        function applyFilters() {
            var cVal = filterClass.value;
            var sVal = filterSess.value;
            var shown = 0, total = allRows.length;

            allRows.forEach(function(row) {
                var matchC = !cVal || row.dataset.class === cVal;
                var matchS = !sVal || row.dataset.session === sVal;
                var visible = matchC && matchS;
                row.style.display = visible ? '' : 'none';
                if (!visible) {
                    var cb = row.querySelector('.slot-check');
                    if (cb) cb.checked = false;
                    row.classList.remove('checked-row');
                }
                if (visible) shown++;
            });

            // Hide date blocks that have zero visible rows
            document.querySelectorAll('.slot-table').forEach(function(table) {
                var visibleRows = table.querySelectorAll('tbody tr');
                var anyVisible = false;
                visibleRows.forEach(function(r) { if (r.style.display !== 'none') anyVisible = true; });
                var dateBlock = table.closest('.mb-4');
                if (dateBlock) dateBlock.style.display = anyVisible ? '' : 'none';
            });

            filterCount.textContent = (cVal || sVal)
                ? 'Hiện ' + shown + ' / ' + total + ' tiết'
                : total + ' tiết';
        }

        filterClass.addEventListener('change', applyFilters);
        filterSess.addEventListener('change', applyFilters);
        applyFilters();
        allRows.forEach(markRowAssignedState);
        refreshTeacherOptions();

        /* ══════════ CHECKBOX ROW HIGHLIGHT ══════════ */
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('slot-check')) {
                var tr = e.target.closest('tr');
                if (tr) tr.classList.toggle('checked-row', e.target.checked);
            }

            if (e.target.matches('select[name*="[teacher_id]"]')) {
                var row = e.target.closest('tr');
                if (row) {
                    markRowAssignedState(row);
                }
                refreshTeacherOptions();
            }
        });

        /* ══════════ SELECT ALL (global) ══════════ */
        selectAll.addEventListener('change', function () {
            var checked = this.checked;
            allRows.forEach(function(row) {
                if (row.style.display !== 'none') {
                    var cb = row.querySelector('.slot-check');
                    if (cb) { cb.checked = checked; row.classList.toggle('checked-row', checked); }
                }
            });
        });

        /* ══════════ SELECT ALL PER DATE ══════════ */
        document.querySelectorAll('.select-all-date').forEach(function(masterCb) {
            masterCb.addEventListener('change', function () {
                var checked = this.checked;
                var table = this.closest('table');
                table.querySelectorAll('.slot-check').forEach(function(cb) {
                    if (cb.closest('tr').style.display !== 'none') {
                        cb.checked = checked;
                        cb.closest('tr').classList.toggle('checked-row', checked);
                    }
                });
            });
        });

        /* ══════════ BULK ASSIGN ══════════ */
        bulkApply.addEventListener('click', function () {
            var teacherId = bulkTeacher.value;
            if (!teacherId) { alert('Vui lòng chọn giảng viên trước!'); return; }

            var usedMap = buildUsedTeacherMap();
            var assigned = 0;
            var skipped = 0;

            allRows.forEach(function(row) {
                var cb = row.querySelector('.slot-check');
                if (cb && cb.checked && row.style.display !== 'none') {
                    var sel = getTeacherSelect(row);
                    if (!sel) {
                        return;
                    }

                    if (!canAssignTeacherToRow(row, teacherId, usedMap)) {
                        skipped++;
                        return;
                    }

                    sel.value = teacherId;
                    var key = getSlotKey(row);
                    if (!usedMap[key]) {
                        usedMap[key] = {};
                    }
                    usedMap[key][String(teacherId)] = true;
                    markRowAssignedState(row);
                    assigned++;
                }
            });

            refreshTeacherOptions();

            if (assigned > 0) {
                selectAll.checked = false;
                document.querySelectorAll('.slot-check, .select-all-date').forEach(function(c) {
                    c.checked = false;
                });
                allRows.forEach(function(r) { r.classList.remove('checked-row'); });

                var message = 'Đã gán giảng viên cho ' + assigned + ' tiết.';
                if (skipped > 0) {
                    message += ' Bỏ qua ' + skipped + ' tiết do trùng ngày-tiết.';
                }
                showToast(message);
            } else if (skipped > 0) {
                alert('Không thể gán vì các tiết đã có giảng viên trùng trong cùng ngày-tiết.');
            } else {
                alert('Chưa chọn tiết nào! Hãy tick checkbox ở các dòng muốn gán.');
            }
        });

        /* ══════════ AUTO-FILL ══════════ */
        autoFillBtn.addEventListener('click', function () {
            // Build map: subjectId_classId => teacherId from already-assigned slots
            var teacherMap = {};
            allRows.forEach(function(row) {
                var sel = getTeacherSelect(row);
                if (sel && sel.value) {
                    var key = row.dataset.subjectId + '_' + row.dataset.classId;
                    if (!teacherMap[key]) teacherMap[key] = sel.value;
                }
            });

            // Apply to unassigned
            var usedMap = buildUsedTeacherMap();
            var filled = 0;
            var skipped = 0;
            allRows.forEach(function(row) {
                var sel = getTeacherSelect(row);
                if (sel && !sel.value) {
                    var key = row.dataset.subjectId + '_' + row.dataset.classId;
                    if (teacherMap[key]) {
                        if (!canAssignTeacherToRow(row, teacherMap[key], usedMap)) {
                            skipped++;
                            return;
                        }

                        var opt = sel.querySelector('option[value="' + teacherMap[key] + '"]');
                        if (opt) {
                            sel.value = teacherMap[key];
                            var slotKey = getSlotKey(row);
                            if (!usedMap[slotKey]) {
                                usedMap[slotKey] = {};
                            }
                            usedMap[slotKey][String(teacherMap[key])] = true;
                            markRowAssignedState(row);
                            filled++;
                        }
                    }
                }
            });

            refreshTeacherOptions();

            if (filled) {
                var autoFillMessage = 'Auto-fill: Đã gợi ý ' + filled + ' tiết dựa trên cùng môn + lớp.';
                if (skipped > 0) {
                    autoFillMessage += ' Bỏ qua ' + skipped + ' tiết do trùng ngày-tiết.';
                }
                showToast(autoFillMessage);
            } else {
                alert('Không tìm thấy gợi ý phù hợp hoặc các gợi ý bị trùng ngày-tiết.');
            }
        });

        /* ══════════ TOAST HELPER ══════════ */
        function showToast(msg) {
            var toast = document.getElementById('assignToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'assignToast';
                toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;padding:12px 20px;'
                    + 'background:#1cc88a;color:#fff;border-radius:6px;font-size:.9rem;'
                    + 'box-shadow:0 4px 12px rgba(0,0,0,.2);transition:opacity .4s;';
                document.body.appendChild(toast);
            }
            toast.textContent = msg;
            toast.style.opacity = '1';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(function() { toast.style.opacity = '0'; }, 3000);
        }
    });
    </script>
@endsection
