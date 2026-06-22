@extends('layouts.dashboard')

@section('title', 'Phan cong lich giang day theo thang')

@section('content')
    @php
        $subjectSlotCount = $aggregateSlots->count();
        $eventSlotCount = $aggregateEventSlotCount ?? 0;
        $aggregatePlanCount = $aggregatePlanCount ?? 0;
        $aggregateMonthlyScheduleCount = $aggregateMonthlyScheduleCount ?? 0;
        $departmentName = $departmentName ?? ($subjects->first()?->department?->name ?? '-');
        $oldSlots = old('slots', []);
        $currentBatchStatus = $currentBatch?->status ?? 'draft';
        $currentBatchStatusLabels = [
            'draft' => 'Nháp',
            'submitted' => 'Đã gửi PDT duyệt',
            'approved' => 'Đã phê duyệt',
            'returned' => 'Đã trả về',
        ];
        $currentBatchStatusClasses = [
            'draft' => 'badge-secondary',
            'submitted' => 'badge-info',
            'approved' => 'badge-success',
            'returned' => 'badge-warning',
        ];
        $currentBatchStatusLabel = $currentBatchStatusLabels[$currentBatchStatus] ?? strtoupper($currentBatchStatus);
        $currentBatchStatusClass = $currentBatchStatusClasses[$currentBatchStatus] ?? 'badge-secondary';
        $batchReadOnly = in_array($currentBatchStatus, ['submitted', 'approved'], true);
        $batchCanSubmit = in_array($currentBatchStatus, ['draft', 'returned'], true) || ! $currentBatch;
        $currentAssignedSlotCount = $aggregateSlots->filter(fn ($slot) => ! empty($slot->teacher_id))->count();
        $currentUnassignedSlotCount = max($subjectSlotCount - $currentAssignedSlotCount, 0);
        $currentActiveMergeGroupCount = $aggregateSlots
            ->filter(fn ($slot) => ! empty($slot->schedule_slot_group_id) && $slot->scheduleSlotGroup?->status === 'active')
            ->pluck('schedule_slot_group_id')
            ->unique()
            ->count();
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1 text-primary font-weight-bold">
                                <i class="fas fa-chalkboard-teacher mr-2"></i>Phân công giảng dạy tháng
                                {{ $monthlySchedule->month }}/{{ $monthlySchedule->year }} — {{ $departmentName }}
                            </h4>
                            <p class="mb-0" style="color: #858796;">
                                Tổng hợp từ <strong class="text-dark">{{ $aggregatePlanCount }}</strong> kế hoạch
                                <span class="mx-1">/</span>
                                <strong class="text-dark">{{ $aggregateMonthlyScheduleCount }}</strong> lịch tháng
                            </p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="badge badge-info px-3 py-2" style="font-size: .85rem;">Tổng hợp</span>
                        </div>
                    </div>

                    <div class="mt-3 d-flex flex-wrap">
                        <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                            <i class="fas fa-arrow-left mr-1"></i>Quay lại
                        </a>

                        @if ($isAggregateAssignment)
                            @if ($currentBatch)
                                <a href="{{ route('department-monthly-assignment-batches.show', $currentBatch->id) }}"
                                    class="btn btn-outline-info btn-sm mr-2 mb-2">
                                    <i class="fas fa-clipboard-list mr-1"></i>Xem batch
                                </a>
                            @endif

                            @if ($batchCanSubmit)
                                <form method="POST"
                                    action="{{ route('department-monthly-assignment-batches.submit', $monthlySchedule->id) }}"
                                    class="mr-2 mb-2"
                                    onsubmit="return confirm('Kiểm tra và gửi batch tổng hợp lên PDT duyệt?');">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-paper-plane mr-1"></i>Kiểm tra và gửi PDT duyệt
                                    </button>
                                </form>
                            @else
                                <span class="badge {{ $currentBatchStatusClass }} px-3 py-2 mr-2 mb-2"
                                    style="font-size: .85rem;">{{ $currentBatchStatusLabel }}</span>
                            @endif
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isAggregateAssignment)
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-left-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                            <div>
                                <h6 class="mb-1 text-info font-weight-bold">
                                    <i class="fas fa-layer-group mr-1"></i>Trạng thái batch tổng hợp
                                    <span class="badge {{ $currentBatchStatusClass }} ml-2">{{ $currentBatchStatusLabel }}</span>
                                </h6>
                                <div class="text-muted small">
                                    Số plan nguồn: <strong>{{ $aggregatePlanCount }}</strong>
                                    <span class="mx-2">|</span>
                                    Số lịch tháng nguồn: <strong>{{ $aggregateMonthlyScheduleCount }}</strong>
                                    <span class="mx-2">|</span>
                                    Tổng slot: <strong>{{ $subjectSlotCount }}</strong>
                                    <span class="mx-2">|</span>
                                    Đã phân công: <strong>{{ $currentAssignedSlotCount }}</strong>
                                    <span class="mx-2">|</span>
                                    Chưa phân công: <strong>{{ $currentUnassignedSlotCount }}</strong>
                                    <span class="mx-2">|</span>
                                    Nhóm ghép active: <strong>{{ $currentActiveMergeGroupCount }}</strong>
                                </div>
                            </div>
                            @if ($currentBatch?->review_note && $currentBatchStatus === 'returned')
                                <div class="alert alert-warning mb-0 mt-3 mt-md-0 py-2 px-3" style="max-width: 48rem;">
                                    <strong>Ghi chú trả về:</strong> {{ $currentBatch->review_note }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                                <span class="badge badge-light border px-2 py-1 mr-1"><span
                                        class="event-dot"></span> S&#7921; ki&#7879;n/read-only &#273;&#227; lo&#7841;i</span>
                                <span class="badge badge-primary px-2 py-1">{{ $subjectSlotCount }}
                                    ti&#7871;t</span>
                                <span class="badge badge-secondary px-2 py-1">{{ $aggregateMonthlyScheduleCount }}
                                    l&#7883;ch th&aacute;ng</span>
                            </div>
                        </div>

                    <div class="slot-overview mb-3">
                        <div class="slot-overview-card">
                            <span class="slot-overview-label">M&#244;n h&#7885;c</span>
                            <strong class="slot-overview-value">{{ $subjectSlotCount }}</strong>
                            <small class="slot-overview-note">C&#243; th&#7875; ph&#226;n c&#244;ng gi&#7843;ng vi&#234;n</small>
                        </div>
                        <div class="slot-overview-card slot-overview-card-event">
                            <span class="slot-overview-label">K&#7871; ho&#7841;ch</span>
                            <strong class="slot-overview-value">{{ $aggregateMonthlyScheduleCount }}</strong>
                            <small class="slot-overview-note">T&#7893;ng h&#7907;p t&#7915; {{ $aggregatePlanCount }} k&#7871; ho&#7841;ch</small>
                        </div>
                        <div class="slot-overview-card slot-overview-card-event">
                            <span class="slot-overview-label">S&#7921; ki&#7879;n / read-only</span>
                            <strong class="slot-overview-value">{{ $eventSlotCount }}</strong>
                            <small class="slot-overview-note">&#272;&#227; lo&#7841;i kh&#7887;i m&#224;n ph&#226;n c&#244;ng</small>
                        </div>
                        <div class="slot-overview-card slot-overview-card-muted">
                            <span class="slot-overview-label">L&#432;u &#253;</span>
                            <strong class="slot-overview-value">T&#7893;ng h&#7907;p theo khoa</strong>
                            <small class="slot-overview-note">Ch&#7881; hi&#7875;n th&#7883; c&#225;c ti&#7871;t m&#244;n h&#7885;c c&#7911;a khoa</small>
                        </div>
                    </div>

                    @php
                        $slotsByDate = $aggregateSlots->groupBy(
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
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">Lo&#7841;i slot</label>
                                    <select id="filterSlotType" class="form-control form-control-sm" style="min-width:140px;">
                                        <option value="">T&#7845;t c&#7843;</option>
                                        <option value="subject">M&#244;n h&#7885;c</option>
                                        <option value="event">S&#7921; ki&#7879;n</option>
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
                                        <select id="bulkTeacher" class="form-control" style="min-width:180px;" @disabled($batchReadOnly)>
                                            <option value="">-- Chọn GV --</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">{{ $teacher->name }}@if($teacher->teacher_code) ({{ $teacher->teacher_code }})@endif</option>
                                            @endforeach
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" id="bulkApplyBtn" title="Gán GV cho các tiết đã chọn" @disabled($batchReadOnly)>
                                                <i class="fas fa-user-check mr-1"></i>Gán
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                {{-- Auto-fill --}}
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">&nbsp;</label><br>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="autoFillBtn" title="Tự động gợi ý GV theo cùng môn + lớp" @disabled($batchReadOnly)>
                                        <i class="fas fa-magic mr-1"></i>Auto-fill
                                    </button>
                                </div>
                                {{-- Select all visible --}}
                                <div class="col-auto mb-2 align-self-end">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAllSlots" @disabled($batchReadOnly)>
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
                                                                <th style="width: 35px;"><input type="checkbox" class="select-all-date" title="Chọn tất cả ngày này" @disabled($batchReadOnly)></th>
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
                                                                    $isEvent = ($slot->slot_type ?? 'subject') === 'event';
                                                                    $idx = $flatIndex++;
                                                                    $oldSlot = $oldSlots[$idx] ?? null;
                                                                    $hasTeacher = !$isEvent && !empty(
                                                                        $oldSlot['teacher_id'] ?? $slot->teacher_id
                                                                    );
                                                                    $rowClass = $isEvent
                                                                        ? 'slot-event'
                                                                        : ($hasTeacher ? 'slot-assigned' : 'slot-unassigned');
                                                                    $mergeGroupId = $slot->schedule_slot_group_id ?? null;
                                                                    $mergeGroupStatus = $slot->scheduleSlotGroup?->status ?? '';
                                                                    $isMerged = !$isEvent && !empty($mergeGroupId) && $mergeGroupStatus === 'active';
                                                                @endphp
                                                                <tr class="{{ $rowClass }}"
                                                                    data-slot-id="{{ $slot->id }}"
                                                                    data-source-monthly-schedule-id="{{ $slot->monthly_schedule_id }}"
                                                                    data-source-plan-id="{{ $slot->monthlySchedule?->plan_id ?? '' }}"
                                                                    data-source-cohort-id="{{ $slot->monthlySchedule?->plan?->training_batch_id ?? '' }}"
                                                                    data-merge-group-id="{{ $mergeGroupId ?? '' }}"
                                                                    data-merge-group-status="{{ $mergeGroupStatus }}"
                                                                    data-class="{{ $slot->trainingClass?->code ?? '' }}"
                                                                    data-class-id="{{ $slot->class_id }}"
                                                                    data-slot-type="{{ $isEvent ? 'event' : 'subject' }}"
                                                                    data-session="{{ $slot->period_number <= 5 ? 'morning' : 'afternoon' }}"
                                                                    data-subject-id="{{ $slot->subject_id }}"
                                                                    data-date="{{ optional($slot->date)->format('Y-m-d') }}"
                                                                    data-period="{{ $slot->period_number }}">
                                                                    <td class="text-center">
                                                                        <input type="checkbox" class="slot-check" @disabled($isEvent || $batchReadOnly)>
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
                                                                        @if ($isMerged)
                                                                            <span class="badge badge-success ml-1"
                                                                                style="font-size: 10px;">Đã ghép</span>
                                                                            @if (! $batchReadOnly)
                                                                                <button type="button"
                                                                                    class="btn btn-link btn-sm p-0 ml-1 split-merge-group-btn"
                                                                                    data-slot-id="{{ $slot->id }}"
                                                                                    data-group-id="{{ $mergeGroupId }}"
                                                                                    style="font-size: 10px; vertical-align: baseline;">
                                                                                    Tách ghép
                                                                                </button>
                                                                            @endif
                                                                        @elseif (!$isEvent && ! $batchReadOnly)
                                                                            <button type="button"
                                                                                class="btn btn-link btn-sm p-0 ml-1 merge-slot-btn"
                                                                                data-slot-id="{{ $slot->id }}"
                                                                                style="font-size: 10px; vertical-align: baseline;">
                                                                                Ghép lớp
                                                                            </button>
                                                                        @endif
                                                                        <span
                                                                            class="class-pill">{{ $slot->trainingClass?->code ?? '-' }}</span>
                                                                        <span class="badge badge-light border ml-1"
                                                                            style="font-size: 10px; vertical-align: baseline;">
                                                                            Kế hoạch: {{ $slot->monthlySchedule?->plan?->name ?? '-' }}
                                                                        </span>
                                                                        @if ($isEvent)
                                                                            <span class="event-readonly-pill"><i class="fas fa-lock mr-1"></i>Không phân công</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if ($isEvent)
                                                                            <span class="text-muted small">-</span>
                                                                        @else
                                                                            <select
                                                                                name="slots[{{ $idx }}][teacher_id]"
                                                                                class="form-control form-control-sm"
                                                                                @disabled($batchReadOnly)>
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
                                                                        @endif
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
                                                                        @if ($isEvent)
                                                                            @php
                                                                                $eventTypeLabel = \Illuminate\Support\Str::headline(
                                                                                    str_replace('_', ' ', (string) ($slot->event_type ?? 'event')),
                                                                                );
                                                                            @endphp
                                                                            <div class="event-subject-cell">
                                                                                <span class="event-chip">{{ $eventTypeLabel }}</span>
                                                                                <div class="event-title">{{ $slot->subject ?? '--' }}</div>
                                                                                <div class="event-meta">S&#7921; ki&#7879;n h&#7885;c k&#7923; hi&#7875;n th&#7883; c&#249;ng l&#7883;ch th&#225;ng</div>
                                                                            </div>
                                                                        @else
                                                                            <input type="hidden"
                                                                                name="slots[{{ $idx }}][subject_id]"
                                                                                value="{{ $selectedSubjectId }}">
                                                                            <div class="font-weight-bold"
                                                                                style="font-size: .82rem;">{{ $subjectLabel }}
                                                                            </div>
                                                                        @endif
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
                                                                        @if ($isEvent)
                                                                            <span class="text-muted small">-</span>
                                                                        @else
                                                                            <select
                                                                                name="slots[{{ $idx }}][subject_lesson_id]"
                                                                                class="form-control form-control-sm"
                                                                                @disabled($batchReadOnly)>
                                                                                <option value="">-- Chọn bài --</option>
                                                                                @foreach ($lessonOptions as $lesson)
                                                                                    <option
                                                                                        value="{{ $lesson->id }}"
                                                                                        @selected((int) $selectedLesson === (int) $lesson->id)>
                                                                                        B{{ $lesson->lesson_no }}:
                                                                                        {{ $lesson->title }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if ($isEvent)
                                                                            <span class="text-muted small">-</span>
                                                                        @else
                                                                            <select name="slots[{{ $idx }}][room_id]"
                                                                                class="form-control form-control-sm"
                                                                                @disabled($batchReadOnly)>
                                                                                <option value="">-- Phòng --</option>
                                                                                @foreach ($rooms as $room)
                                                                                    @php $selectedRoom = $oldSlot['room_id'] ?? $slot->room_id; @endphp
                                                                                    <option value="{{ $room->id }}"
                                                                                        @selected((int) $selectedRoom === (int) $room->id)>
                                                                                        {{ $room->code }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if ($isEvent)
                                                                            <div class="event-content">
                                                                                {{ $slot->content ?: 'Sự kiện học kỳ' }}
                                                                            </div>
                                                                        @else
                                                                            <input type="text"
                                                                                class="form-control form-control-sm"
                                                                                name="slots[{{ $idx }}][content]"
                                                                                value="{{ $oldSlot['content'] ?? $slot->content }}"
                                                                                maxlength="500" placeholder="Nội dung"
                                                                                @disabled($batchReadOnly)>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if ($isEvent)
                                                                            <div class="event-note">
                                                                                {{ $slot->note ?: 'Không có ghi chú' }}
                                                                            </div>
                                                                        @else
                                                                            <input type="text"
                                                                                class="form-control form-control-sm"
                                                                                name="slots[{{ $idx }}][note]"
                                                                                value="{{ $oldSlot['note'] ?? $slot->note }}"
                                                                                maxlength="500" placeholder="Ghi chú"
                                                                                @disabled($batchReadOnly)>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $selectedStatus =
                                                                                $oldSlot['slot_status'] ??
                                                                                ($slot->slot_status ?? 'planned');
                                                                            $eventStatusLabels = [
                                                                                'planned' => 'Dự kiến',
                                                                                'updated' => 'Đã cập nhật',
                                                                                'cancelled' => 'Đã hủy',
                                                                            ];
                                                                            $eventStatusClasses = [
                                                                                'planned' => 'badge-info',
                                                                                'updated' => 'badge-success',
                                                                                'cancelled' => 'badge-danger',
                                                                            ];
                                                                        @endphp
                                                                        @if ($isEvent)
                                                                            <span
                                                                                class="badge {{ $eventStatusClasses[$selectedStatus] ?? 'badge-secondary' }}">{{ $eventStatusLabels[$selectedStatus] ?? strtoupper($selectedStatus) }}</span>
                                                                        @else
                                                                            <select
                                                                                name="slots[{{ $idx }}][slot_status]"
                                                                                class="form-control form-control-sm"
                                                                                @disabled($batchReadOnly)>
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
                                                                        @endif
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
                            @if ($batchReadOnly)
                                <button type="button" class="btn btn-secondary mr-2 mb-2" disabled>
                                    <i class="fas fa-lock mr-1"></i>Màn này đang khóa do batch đã gửi/đã phê duyệt
                                </button>
                            @else
                                <button type="submit" class="btn btn-success mr-2 mb-2">
                                    <i class="fas fa-save mr-1"></i>Lưu phân công
                                </button>
                            @endif
                            @if (! $isAggregateAssignment)
                                <small class="text-muted mb-2">Workflow phê duyệt theo kế hoạch tháng đã ngừng sử dụng.
                                    Vui lòng dùng workflow Phân công theo Khoa + Tháng.</small>
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
        .event-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
            background: #17a2b8;
        }

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

        .slot-event {
            background: #eefbff !important;
            box-shadow: inset 4px 0 0 #17a2b8;
        }

        .slot-event:hover {
            background: #def6fb !important;
        }

        .slot-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .75rem;
        }

        .slot-overview-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
            border: 1px solid #e3e6f0;
            border-radius: .75rem;
            padding: .9rem 1rem;
            display: flex;
            flex-direction: column;
            min-height: 104px;
        }

        .slot-overview-card-event {
            background: linear-gradient(135deg, #e8fbff 0%, #f3fdff 100%);
            border-color: #8fe3ee;
        }

        .slot-overview-card-muted {
            background: linear-gradient(135deg, #f7f9fc 0%, #edf2f9 100%);
        }

        .slot-overview-label {
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #858796;
            margin-bottom: .4rem;
            font-weight: 700;
        }

        .slot-overview-value {
            font-size: 1.15rem;
            color: #2e3a59;
            line-height: 1.25;
        }

        .slot-overview-note {
            font-size: .78rem;
            color: #6c757d;
            margin-top: .35rem;
            line-height: 1.4;
        }

        .event-readonly-pill {
            display: inline-flex;
            align-items: center;
            padding: .3rem .65rem;
            border-radius: 999px;
            background: #d9f7fb;
            color: #0f6f7d;
            font-size: .75rem;
            font-weight: 700;
        }

        .event-subject-cell {
            display: flex;
            flex-direction: column;
            gap: .3rem;
        }

        .event-chip {
            display: inline-flex;
            align-self: flex-start;
            padding: .2rem .55rem;
            border-radius: 999px;
            background: #17a2b8;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .event-title {
            font-size: .86rem;
            font-weight: 700;
            color: #114b5f;
            line-height: 1.35;
        }

        .event-meta {
            font-size: .74rem;
            color: #5c6b73;
            line-height: 1.35;
        }

        .event-content,
        .event-note {
            font-size: .78rem;
            line-height: 1.45;
            color: #35515c;
            background: rgba(23, 162, 184, .08);
            border: 1px solid rgba(23, 162, 184, .14);
            border-radius: .5rem;
            padding: .45rem .55rem;
        }

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

    <div class="modal fade" id="mergeSlotModal" tabindex="-1" role="dialog" aria-labelledby="mergeSlotModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Đóng</button>
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="mergeSlotModalError"></div>
                    <div class="mb-2 text-muted small" id="mergeSlotModalInfo"></div>
                    <div class="alert alert-info py-2 mb-2 small">
                        Giảng viên và phòng của các lớp được ghép sẽ lấy theo dòng bạn bấm Ghép lớp.
                    </div>
                    <div id="mergeSlotCandidatesWrap">
                        <div class="text-muted small">Chưa tải danh sách tiết có thể ghép.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary btn-sm" id="confirmMergeSlotBtn">Xác nhận ghép</button>
                </div>
            </div>
        </div>
    </div>
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var allRows     = Array.prototype.slice.call(document.querySelectorAll('.slot-table tbody tr'));
        var filterType  = document.getElementById('filterSlotType');
        var filterClass = document.getElementById('filterClass');
        var filterSess  = document.getElementById('filterSession');
        var filterCount = document.getElementById('filterCount');
        var bulkTeacher = document.getElementById('bulkTeacher');
        var bulkApply   = document.getElementById('bulkApplyBtn');
        var selectAll   = document.getElementById('selectAllSlots');
                var autoFillBtn = document.getElementById('autoFillBtn');
        var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
        var currentMonthlyScheduleId = @json($monthlySchedule->id);
        var mergeCandidatesUrlTemplate = @json(route('monthly-schedule.assignment.merge-candidates', ['id' => '__MONTHLY__', 'slotId' => '__SLOT__']));
        var mergeSlotUrlTemplate = @json(route('monthly-schedule.assignment.merge', ['id' => '__MONTHLY__', 'slotId' => '__SLOT__']));
        var splitMergeUrlTemplate = @json(route('monthly-schedule.assignment.split', ['id' => '__MONTHLY__', 'groupId' => '__GROUP__']));
        var mergeModalEl = document.getElementById('mergeSlotModal');
        var mergeModalError = document.getElementById('mergeSlotModalError');
        var mergeModalInfo = document.getElementById('mergeSlotModalInfo');
        var mergeCandidatesWrap = document.getElementById('mergeSlotCandidatesWrap');
        var currentMergeBaseSlotId = null;
        var currentMergeBaseMonthlyScheduleId = null;
        var currentMergeCandidates = [];

        function getTeacherSelect(row) {
            return row.querySelector('select[name*="[teacher_id]"]');
        }

        function getSlotKey(row) {
            var date = row.getAttribute('data-date') || '';
            var period = row.getAttribute('data-period') || '';
            return date + '|' + period;
        }

        function markRowAssignedState(row) {
            if (row.dataset.slotType === 'event') {
                row.classList.add('slot-event');
                row.classList.remove('slot-assigned', 'slot-unassigned');
                return;
            }

            var sel = getTeacherSelect(row);
            var hasTeacher = !!(sel && sel.value);
            row.classList.toggle('slot-assigned', hasTeacher);
            row.classList.toggle('slot-unassigned', !hasTeacher);
        }

        function getRowMergeGroupId(row) {
            return row.getAttribute('data-merge-group-id') || '';
        }

        function getRowMergeGroupStatus(row) {
            return row.getAttribute('data-merge-group-status') || '';
        }

        function getRowSourceMonthlyScheduleId(row) {
            return row.getAttribute('data-source-monthly-schedule-id') || currentMonthlyScheduleId;
        }

        function getMergeBaseRow(slotId) {
            if (!slotId) {
                return null;
            }

            return document.querySelector('tr[data-slot-id="' + slotId + '"]');
        }

        function toNullableInt(value) {
            var parsed = parseInt(value, 10);
            return Number.isNaN(parsed) ? null : parsed;
        }

        function getMergeBasePayload(slotId) {
            var row = getMergeBaseRow(slotId);
            if (!row) {
                return {
                    teacher_id: null,
                    room_id: null,
                    subject_lesson_id: null
                };
            }

            var teacherSelect = row.querySelector('select[name*="[teacher_id]"]');
            var roomSelect = row.querySelector('select[name*="[room_id]"]');
            var lessonSelect = row.querySelector('select[name*="[subject_lesson_id]"]');

            return {
                teacher_id: teacherSelect ? toNullableInt(teacherSelect.value) : null,
                room_id: roomSelect ? toNullableInt(roomSelect.value) : null,
                subject_lesson_id: lessonSelect ? toNullableInt(lessonSelect.value) : null
            };
        }

        function getTeacherUsageBucket(map, key, teacherId) {
            if (!map[key]) {
                map[key] = {};
            }

            if (!map[key][teacherId]) {
                map[key][teacherId] = {
                    hasUngrouped: false,
                    activeGroups: {}
                };
            }

            return map[key][teacherId];
        }

        function registerTeacherUsage(map, row, teacherId) {
            var key = getSlotKey(row);
            var bucket = getTeacherUsageBucket(map, key, String(teacherId));
            var mergeGroupId = getRowMergeGroupId(row);
            var mergeGroupStatus = getRowMergeGroupStatus(row);

            if (mergeGroupId && mergeGroupStatus === 'active') {
                bucket.activeGroups[mergeGroupId] = true;
            } else {
                bucket.hasUngrouped = true;
            }
        }

        function buildUsedTeacherMap() {
            var map = {};
            allRows.forEach(function (row) {
                var sel = getTeacherSelect(row);
                if (!sel || !sel.value) {
                    return;
                }

                registerTeacherUsage(map, row, sel.value);
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
            var teacherKey = String(teacherId);
            var used = usedMap[key] && usedMap[key][teacherKey] ? usedMap[key][teacherKey] : null;
            var currentValue = sel.value ? String(sel.value) : '';
            var rowMergeGroupId = getRowMergeGroupId(row);
            var rowMergeGroupStatus = getRowMergeGroupStatus(row);

            if (currentValue === teacherKey) {
                return true;
            }

            if (!used) {
                return true;
            }

            if (!rowMergeGroupId || rowMergeGroupStatus !== 'active') {
                return false;
            }

            if (used.hasUngrouped) {
                return false;
            }

            var activeGroups = Object.keys(used.activeGroups || {});
            if (activeGroups.length !== 1) {
                return false;
            }

            return activeGroups[0] === rowMergeGroupId;
        }

        function refreshTeacherOptions() {
            var usedMap = buildUsedTeacherMap();

            allRows.forEach(function (row) {
                var sel = getTeacherSelect(row);
                if (!sel) {
                    return;
                }

                var currentValue = sel.value ? String(sel.value) : '';

                Array.prototype.slice.call(sel.options).forEach(function (opt) {
                    if (!opt.value) {
                        opt.disabled = false;
                        opt.hidden = false;
                        return;
                    }

                    var allowed = canAssignTeacherToRow(row, opt.value, usedMap);
                    var blocked = !allowed && String(opt.value) !== currentValue;
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
            var tVal = filterType.value;
            var cVal = filterClass.value;
            var sVal = filterSess.value;
            var shown = 0, total = allRows.length;

            allRows.forEach(function(row) {
                var matchC = !cVal || row.dataset.class === cVal;
                var matchS = !sVal || row.dataset.session === sVal;
                var matchT = !tVal || row.dataset.slotType === tVal;
                var visible = matchC && matchS && matchT;
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

        filterType.addEventListener('change', applyFilters);
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
                    if (cb && !cb.disabled) { cb.checked = checked; row.classList.toggle('checked-row', checked); }
                }
            });
        });

        /* ══════════ SELECT ALL PER DATE ══════════ */
        document.querySelectorAll('.select-all-date').forEach(function(masterCb) {
            masterCb.addEventListener('change', function () {
                var checked = this.checked;
                var table = this.closest('table');
                table.querySelectorAll('.slot-check').forEach(function(cb) {
                    if (!cb.disabled && cb.closest('tr').style.display !== 'none') {
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

        /* TOAST HELPER */
                function escapeHtml(value) {
            return String(value === null || value === undefined ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function buildMergeUrl(template, monthlyScheduleId, value) {
            return template
                .replace('__MONTHLY__', encodeURIComponent(monthlyScheduleId || currentMonthlyScheduleId || ''))
                .replace('__SLOT__', encodeURIComponent(value || ''))
                .replace('__GROUP__', encodeURIComponent(value || ''));
        }

        function setMergeModalError(message) {
            if (!mergeModalError) {
                return;
            }

            if (!message) {
                mergeModalError.classList.add('d-none');
                mergeModalError.textContent = '';
                return;
            }

            mergeModalError.textContent = message;
            mergeModalError.classList.remove('d-none');
        }

        function setMergeModalLoading(isLoading, text) {
            if (mergeCandidatesWrap) {
                mergeCandidatesWrap.innerHTML = isLoading
                    ? '<div class="text-muted small">Đang tải...</div>'
                    : mergeCandidatesWrap.innerHTML;
            }
        }

        function showMergeModal() {
            if (window.jQuery && mergeModalEl) {
                window.jQuery(mergeModalEl).modal('show');
                return;
            }

            if (mergeModalEl) {
                mergeModalEl.classList.add('show');
                mergeModalEl.style.display = 'block';
            }
        }

        function hideMergeModal() {
            if (window.jQuery && mergeModalEl) {
                window.jQuery(mergeModalEl).modal('hide');
                return;
            }

            if (mergeModalEl) {
                mergeModalEl.classList.remove('show');
                mergeModalEl.style.display = 'none';
            }
        }

        function renderMergeCandidates(candidates) {
            if (!mergeCandidatesWrap) {
                return;
            }

            if (!candidates || !candidates.length) {
                alert('Không tìm thấy gợi ý phù hợp hoặc các gợi ý bị trùng ngày-tiết.');
                return;
            }

            var html = '<div class="list-group">';
            candidates.forEach(function (candidate) {
                var classLabel = candidate.class_code || candidate.class_name || '-';
                var subjectLabel = candidate.subject_code || candidate.subject_name || '-';
                var lessonLabel = candidate.lesson_label || '-';
                var teacherLabel = candidate.teacher_name || '-';
                var roomLabel = candidate.room_code || '-';
                html += '<label class="list-group-item d-block mb-2">';
                html += '<div class="d-flex align-items-start">';
                html += '<div class="mr-2 pt-1"><input type="checkbox" class="merge-candidate-check" value="' + escapeHtml(candidate.id) + '"></div>';
                html += '<div class="flex-fill">';
                html += '<div class="font-weight-bold">' + escapeHtml(classLabel) + ' - ' + escapeHtml(candidate.date || '') + ' - Tiết ' + escapeHtml(candidate.period_number || '') + '</div>';
                html += '<div class="small text-muted">Môn: ' + escapeHtml(subjectLabel) + ' | Bài học: ' + escapeHtml(lessonLabel) + ' | GV: ' + escapeHtml(teacherLabel) + ' | Phòng: ' + escapeHtml(roomLabel) + '</div>';
                html += '</div></div></label>';
            });
            html += '</div>';
            mergeCandidatesWrap.innerHTML = html;
        }

        function getJsonErrorMessage(payload, fallback) {
            if (!payload) {
                return fallback;
            }

            if (payload.message) {
                return payload.message;
            }

            if (payload.errors) {
                var messages = [];
                Object.keys(payload.errors).forEach(function (key) {
                    var value = payload.errors[key];
                    if (Array.isArray(value)) {
                        value.forEach(function (item) {
                            if (item) {
                                messages.push(item);
                            }
                        });
                    } else if (value) {
                        messages.push(value);
                    }
                });
                if (messages.length) {
                    return messages.join('\n');
                }
            }

            return fallback;
        }

        async function loadMergeCandidates(slotId, monthlyScheduleId) {
            var url = buildMergeUrl(mergeCandidatesUrlTemplate, monthlyScheduleId, slotId);
            var response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var payload = await response.json().catch(function () { return {}; });
            if (!response.ok || !payload.success) {
                throw new Error(getJsonErrorMessage(payload, 'Không tải được danh sách tiết có thể ghép.'));
            }
            return payload.data || [];
        }

        async function submitMergeGroup(slotId, monthlyScheduleId) {
            if (!currentMergeCandidates.length) {
                alert('Chưa có tiết nào được chọn để ghép.');
                return;
            }

            var checkedIds = Array.prototype.slice.call(document.querySelectorAll('.merge-candidate-check:checked')).map(function (cb) {
                return parseInt(cb.value, 10);
            }).filter(function (id) { return !Number.isNaN(id); });

            if (!checkedIds.length) {
                alert('Vui lòng chọn ít nhất 1 tiết để ghép.');
                return;
            }

            setMergeModalError('');
            setMergeModalLoading(true, 'Đang ghép...');

            try {
                var basePayload = getMergeBasePayload(slotId);
                var response = await fetch(buildMergeUrl(mergeSlotUrlTemplate, monthlyScheduleId, slotId), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        candidate_slot_ids: checkedIds,
                        teacher_id: basePayload.teacher_id,
                        room_id: basePayload.room_id,
                        subject_lesson_id: basePayload.subject_lesson_id
                    })
                });
                var payload = await response.json().catch(function () { return {}; });
                if (!response.ok || !payload.success) {
                    throw new Error(getJsonErrorMessage(payload, 'Ghép lớp thất bại.'));
                }
                showToast(payload.message || 'Đã ghép lớp thành công.');
                hideMergeModal();
                location.reload();
            } catch (error) {
                setMergeModalError(error.message || 'Ghép lớp thất bại.');
            } finally {
                setMergeModalLoading(false);
            }
        }

        async function splitMergeGroup(groupId, monthlyScheduleId) {
            if (!groupId) {
                return;
            }

            if (!confirm('Bạn có chắc muốn tách ghép nhóm này không?')) {
                return;
            }

            try {
                var response = await fetch(buildMergeUrl(splitMergeUrlTemplate, monthlyScheduleId, groupId), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                var payload = await response.json().catch(function () { return {}; });
                if (!response.ok || !payload.success) {
                    throw new Error(getJsonErrorMessage(payload, 'Tách ghép thất bại.'));
                }
                showToast(payload.message || 'Đã tách ghép thành công.');
                location.reload();
            } catch (error) {
                alert(error.message || 'Tách ghép thất bại.');
            }
        }

        document.addEventListener('click', function (e) {
            var confirmMergeBtn = e.target.closest('#confirmMergeSlotBtn');
            if (confirmMergeBtn) {
                if (!currentMergeBaseSlotId) {
                    return;
                }
                submitMergeGroup(currentMergeBaseSlotId, currentMergeBaseMonthlyScheduleId);
                return;
            }

            var mergeBtn = e.target.closest('.merge-slot-btn');
            if (mergeBtn) {
                var slotId = mergeBtn.getAttribute('data-slot-id');
                if (!slotId) {
                    return;
                }

                var mergeRow = mergeBtn.closest('tr');
                var sourceMonthlyScheduleId = mergeRow ? getRowSourceMonthlyScheduleId(mergeRow) : currentMonthlyScheduleId;
                currentMergeBaseSlotId = slotId;
                currentMergeBaseMonthlyScheduleId = sourceMonthlyScheduleId;
                currentMergeCandidates = [];
                setMergeModalError('');
                if (mergeModalInfo) {
                    mergeModalInfo.textContent = '';
                }
                mergeCandidatesWrap.innerHTML = '<div class="text-muted small">Đang tải danh sách tiết có thể ghép...</div>';
                showMergeModal();
                setMergeModalLoading(true, 'Đang tải...');

                loadMergeCandidates(slotId, sourceMonthlyScheduleId).then(function (candidates) {
                    currentMergeCandidates = candidates || [];
                    renderMergeCandidates(currentMergeCandidates);
                    if (mergeModalInfo) {
                        mergeModalInfo.textContent = currentMergeCandidates.length
                            ? 'Chọn 1 hoặc nhiều tiết để ghép chung với tiết gốc.'
                            : 'Không có tiết phù hợp để ghép.';
                    }
                }).catch(function (error) {
                    setMergeModalError(error.message || 'Không tải được danh sách tiết có thể ghép.');
                    mergeCandidatesWrap.innerHTML = '<div class="text-muted small">Không có tiết phù hợp để ghép.</div>';
                }).finally(function () {
                    setMergeModalLoading(false);
                });
                return;
            }

            var splitBtn = e.target.closest('.split-merge-group-btn');
            if (splitBtn) {
                var groupId = splitBtn.getAttribute('data-group-id');
                var splitRow = splitBtn.closest('tr');
                var sourceMonthlyScheduleId = splitRow ? getRowSourceMonthlyScheduleId(splitRow) : currentMonthlyScheduleId;
                splitMergeGroup(groupId, sourceMonthlyScheduleId);
            }
        });

        if (mergeModalEl) {
            mergeModalEl.addEventListener('hidden.bs.modal', function () {
                currentMergeBaseSlotId = null;
                currentMergeBaseMonthlyScheduleId = null;
                currentMergeCandidates = [];
                setMergeModalError('');
                if (mergeModalInfo) {
                    mergeModalInfo.textContent = '';
                }
            });
        }

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
 
