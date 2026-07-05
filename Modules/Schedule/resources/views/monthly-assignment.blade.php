@extends('layouts.dashboard')

@section('title', 'Phân công lịch giảng dạy theo tháng')

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
        $batchCanSubmit = in_array($currentBatchStatus, ['draft', 'returned'], true) || !$currentBatch;
        $currentAssignedSlotCount = $aggregateSlots->filter(fn($slot) => !empty($slot->teacher_id))->count();
        $currentUnassignedSlotCount = max($subjectSlotCount - $currentAssignedSlotCount, 0);
        $currentActiveMergeGroupCount = $aggregateSlots
            ->filter(
                fn($slot) => !empty($slot->schedule_slot_group_id) && $slot->scheduleSlotGroup?->status === 'active',
            )
            ->pluck('schedule_slot_group_id')
            ->unique()
            ->count();
        $supportRequestSummary = $supportRequestSummary ?? [];
        $supportSlotMeta = $supportSlotMeta ?? [];
        $supportDepartments = $supportDepartments ?? collect();
        $canCreateSupportRequest = (bool) ($canCreateSupportRequest ?? false);
        $supportWorkloadRows = $supportWorkloadRows ?? collect();
        $supportTeacherAvailabilityMap = $supportTeacherAvailabilityMap ?? [];
        $supportWorkloadSummary = $supportWorkloadSummary ?? [];
        $currentDepartmentId = (int) ($currentDepartmentId ?? 0);
        $supportCanManageAssignments = auth()->check()
            && auth()->user()->isDepartmentStaff()
            && (int) auth()->user()->department_id === $currentDepartmentId;
        $supportRequestStoreUrl = route('teaching-support-requests.store', $monthlySchedule->id);
        $supportRequestIndexUrl = route('teaching-support-requests.index');
        $supportRequestQueueUrl = auth()->check() && auth()->user()->isDepartmentStaff()
            ? route('teaching-support-requests.inbox')
            : route('teaching-support-requests.index');
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

                        @if ($canCreateSupportRequest)
                            <button type="button" class="btn btn-warning btn-sm mr-2 mb-2" id="openSupportRequestModal">
                                <i class="fas fa-hands-helping mr-1"></i>Đề nghị khoa hỗ trợ
                            </button>
                        @endif

                        @if ($supportCanManageAssignments || $canCreateSupportRequest)
                            <button type="button" class="btn btn-outline-info btn-sm mr-2 mb-2" id="openSupportRequestListModal">
                                <i class="fas fa-list mr-1"></i>Xem slot đã gửi yêu cầu
                            </button>
                        @endif

                        <a href="{{ $supportRequestQueueUrl }}" class="btn btn-outline-warning btn-sm mr-2 mb-2">
                            <i class="fas fa-list mr-1"></i>Xem yêu cầu hỗ trợ
                        </a>

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
                                    <span
                                        class="badge {{ $currentBatchStatusClass }} ml-2">{{ $currentBatchStatusLabel }}</span>
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

    @if ($canCreateSupportRequest || !empty($supportRequestSummary))
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-left-warning shadow-sm">
                    <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h6 class="mb-1 text-warning font-weight-bold">
                                <i class="fas fa-hands-helping mr-1"></i>Điều phối hỗ trợ giảng dạy liên khoa
                            </h6>
                            <div class="text-muted small">
                                Chờ PĐT: <strong>{{ $supportRequestSummary['pending_pdt'] ?? 0 }}</strong>
                                <span class="mx-2">|</span>
                                PĐT giao khoa: <strong>{{ $supportRequestSummary['assigned_to_department'] ?? 0 }}</strong>
                                <span class="mx-2">|</span>
                                Khoa đang phân công: <strong>{{ $supportRequestSummary['department_assigning'] ?? 0 }}</strong>
                                <span class="mx-2">|</span>
                                GV hỗ trợ: <strong>{{ $supportRequestSummary['completed'] ?? 0 }}</strong>
                            </div>
                        </div>
                        @if ($canCreateSupportRequest)
                            <span class="badge badge-warning px-3 py-2 mt-2 mt-md-0">Có thể tạo đề nghị hỗ trợ</span>
                        @endif
                        <a href="{{ $supportRequestQueueUrl }}" class="btn btn-outline-warning btn-sm mt-2 mt-md-0">
                            Xem danh sách xử lý
                        </a>
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

    <div id="assignmentSaveAlert" class="alert d-none" role="alert"></div>

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
                            <span class="badge badge-light border px-2 py-1 mr-1"><span class="event-dot"></span> S&#7921;
                                ki&#7879;n/read-only &#273;&#227; lo&#7841;i</span>
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
                            <small class="slot-overview-note">C&#243; th&#7875; ph&#226;n c&#244;ng gi&#7843;ng
                                vi&#234;n</small>
                        </div>
                        <div class="slot-overview-card slot-overview-card-event">
                            <span class="slot-overview-label">K&#7871; ho&#7841;ch</span>
                            <strong class="slot-overview-value">{{ $aggregateMonthlyScheduleCount }}</strong>
                            <small class="slot-overview-note">T&#7893;ng h&#7907;p t&#7915; {{ $aggregatePlanCount }}
                                k&#7871; ho&#7841;ch</small>
                        </div>
                        <div class="slot-overview-card slot-overview-card-event">
                            <span class="slot-overview-label">S&#7921; ki&#7879;n / read-only</span>
                            <strong class="slot-overview-value">{{ $eventSlotCount }}</strong>
                            <small class="slot-overview-note">&#272;&#227; lo&#7841;i kh&#7887;i m&#224;n ph&#226;n
                                c&#244;ng</small>
                        </div>
                        <div class="slot-overview-card slot-overview-card-muted">
                            <span class="slot-overview-label">L&#432;u &#253;</span>
                            <strong class="slot-overview-value">T&#7893;ng h&#7907;p theo khoa</strong>
                            <small class="slot-overview-note">Ch&#7881; hi&#7875;n th&#7883; c&#225;c ti&#7871;t m&#244;n
                                h&#7885;c c&#7911;a khoa</small>
                        </div>
                        @if (($supportWorkloadSummary['total'] ?? 0) > 0)
                            <div class="slot-overview-card slot-overview-card-event">
                                <span class="slot-overview-label">Nhiệm vụ hỗ trợ</span>
                                <strong class="slot-overview-value">{{ $supportWorkloadSummary['total'] ?? 0 }}</strong>
                                <small class="slot-overview-note">Đã xác nhận: {{ $supportWorkloadSummary['confirmed'] ?? 0 }}</small>
                            </div>
                        @endif
                    </div>

                    @php
                        $slotsByDate = $aggregateSlots->groupBy(fn($slot) => optional($slot->date)->format('Y-m-d'));
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
                                    <select id="filterClass" class="form-control form-control-sm"
                                        style="min-width:120px;">
                                        <option value="">Tất cả lớp</option>
                                    </select>
                                </div>
                                {{-- Filter: Buổi --}}
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">Lọc buổi</label>
                                    <select id="filterSession" class="form-control form-control-sm"
                                        style="min-width:120px;">
                                        <option value="">Tất cả</option>
                                        <option value="morning">&#9728; Sáng (T1-5)</option>
                                        <option value="afternoon">&#9789; Chiều (T6-9)</option>
                                    </select>
                                </div>
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">Lo&#7841;i slot</label>
                                    <select id="filterSlotType" class="form-control form-control-sm"
                                        style="min-width:140px;">
                                        <option value="">T&#7845;t c&#7843;</option>
                                        <option value="subject">M&#244;n h&#7885;c</option>
                                        <option value="event">S&#7921; ki&#7879;n</option>
                                    </select>
                                </div>
                                {{-- Filter count --}}
                                <div class="col-auto mb-2 align-self-end">
                                    <span id="filterCount" class="badge badge-secondary py-1 px-2"
                                        style="font-size:.8rem;"></span>
                                </div>
                                {{-- Divider --}}
                                <div class="col-auto mb-2 border-left ml-1 pl-3">
                                    <label class="small font-weight-bold text-muted mb-1">Gán nhanh GV</label>
                                    <div class="d-flex align-items-start flex-wrap">
                                        <div class="bulk-search-picker-mount mr-2 mb-1" id="bulkTeacherPickerMount"></div>
                                        <select id="bulkTeacher" class="d-none" @disabled($batchReadOnly)>
                                            <option value="">-- Chọn GV --</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}"
                                                    data-search="{{ trim(($teacher->name ?? '') . ' ' . ($teacher->teacher_code ?? '') . ' ' . ($teacher->employee_code ?? '')) }}">
                                                    {{ $teacher->name }}@if ($teacher->teacher_code)
                                                        ({{ $teacher->teacher_code }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-info btn-sm mb-1" id="bulkApplyBtn"
                                            title="Gán GV cho các tiết đã chọn" @disabled($batchReadOnly)>
                                            <i class="fas fa-user-check mr-1"></i>Gán
                                        </button>
                                    </div>
                                </div>
                                <div class="col-auto mb-2 border-left ml-1 pl-3">
                                    <label class="small font-weight-bold text-muted mb-1">Gán nhanh phòng</label>
                                    <div class="d-flex align-items-start flex-wrap">
                                        <div class="bulk-search-picker-mount mr-2 mb-1" id="bulkRoomPickerMount"></div>
                                        <select id="bulkRoom" class="d-none" @disabled($batchReadOnly)>
                                            <option value="">-- Chọn phòng --</option>
                                            @foreach ($rooms as $room)
                                                <option value="{{ $room->id }}"
                                                    data-search="{{ trim(($room->code ?? '') . ' ' . ($room->name ?? '')) }}">
                                                    {{ $room->code }}@if (!empty($room->name))
                                                        - {{ $room->name }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-info btn-sm mb-1" id="bulkRoomApplyBtn"
                                            title="Gán phòng cho các tiết đã chọn" @disabled($batchReadOnly)>
                                            <i class="fas fa-door-open mr-1"></i>Gán
                                        </button>
                                    </div>
                                </div>
                                {{-- Auto-fill --}}
                                <div class="col-auto mb-2">
                                    <label class="small font-weight-bold text-muted mb-1">&nbsp;</label><br>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="autoFillBtn"
                                        title="Tự động gợi ý GV theo cùng môn + lớp" @disabled($batchReadOnly)>
                                        <i class="fas fa-magic mr-1"></i>Auto-fill
                                    </button>
                                </div>
                                {{-- Select all visible --}}
                                <div class="col-auto mb-2 align-self-end">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="selectAllSlots"
                                            @disabled($batchReadOnly)>
                                        <label class="custom-control-label small" for="selectAllSlots">Chọn tất cả</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                                                
                    <form method="POST" action="{{ route('monthly-schedule.assignment.save', $monthlySchedule->id) }}"
                        id="monthlyAssignmentForm" novalidate>
                        @csrf

                        @if ($dateChunks->isEmpty())
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">Không có tiết học nào để phân công.</p>
                            </div>
                        @else
                            {{-- Tab navigation --}}
                            <ul class="nav nav-tabs flex-nowrap" id="scheduleTabs" role="tablist"
                                style="overflow-x: auto;">
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
                                                                <th style="width: 35px;"><input type="checkbox"
                                                                        class="select-all-date"
                                                                        title="Chọn tất cả ngày này"
                                                                        @disabled($batchReadOnly)></th>
                                                                <th style="width: 55px;">Tiết</th>
                                                                <th style="min-width: 90px;">Lớp</th>
                                                                <th style="min-width: 170px;">Giảng viên</th>
                                                                <th style="min-width: 140px;">Môn học</th>
                                                                <th style="min-width: 150px;">Bài học</th>
                                                                <th style="min-width: 100px;">Phòng</th>
                                                                <th style="min-width: 150px;">Nội dung</th>
                                                                <th style="min-width: 130px;">Ghi chú</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($dateSlots as $slot)
                                                                @php
                                                                    $isEvent =
                                                                        ($slot->slot_type ?? 'subject') === 'event';
                                                                    $supportMeta = $supportSlotMeta[$slot->id] ?? null;
                                                                    $supportLocked = (bool) ($supportMeta['locked'] ?? false);
                                                                    $idx = $flatIndex++;
                                                                    $oldSlot = $oldSlots[$idx] ?? null;
                                                                    $hasTeacher =
                                                                        !$isEvent &&
                                                                        !empty(
                                                                            $oldSlot['teacher_id'] ?? $slot->teacher_id
                                                                        );
                                                                    $rowClass = $isEvent
                                                                        ? 'slot-event'
                                                                        : ($hasTeacher
                                                                            ? 'slot-assigned'
                                                                            : 'slot-unassigned');
                                                                    $mergeGroupId =
                                                                        $slot->schedule_slot_group_id ?? null;
                                                                    $mergeGroupStatus =
                                                                        $slot->scheduleSlotGroup?->status ?? '';
                                                                    $isMerged =
                                                                        !$isEvent &&
                                                                        !empty($mergeGroupId) &&
                                                                        $mergeGroupStatus === 'active';
                                                                    $hasClearSubjectLesson =
                                                                        !$isEvent &&
                                                                        $slot->subject_lesson_id !== null &&
                                                                        trim((string) ($slot->subjectLesson?->title ?? '')) !== '';
                                                                @endphp
                                                                    <tr class="{{ $rowClass }}"
                                                                    data-slot-id="{{ $slot->id }}"
                                                                    data-source-monthly-schedule-id="{{ $slot->monthly_schedule_id }}"
                                                                    data-source-plan-id="{{ $slot->monthlySchedule?->plan_id ?? '' }}"
                                                                    data-source-cohort-id="{{ $slot->monthlySchedule?->plan?->training_batch_id ?? '' }}"
                                                                    data-merge-group-id="{{ $mergeGroupId ?? '' }}"
                                                                    data-merge-group-status="{{ $mergeGroupStatus }}"
                                                                    data-support-requestable="{{ $hasClearSubjectLesson ? '1' : '0' }}"
                                                                    data-class="{{ $slot->trainingClass?->code ?? '' }}"
                                                                    data-class-id="{{ $slot->class_id }}"
                                                                    data-slot-type="{{ $isEvent ? 'event' : 'subject' }}"
                                                                    data-session="{{ $slot->period_number <= 5 ? 'morning' : 'afternoon' }}"
                                                                    data-subject-id="{{ $slot->subject_id }}"
                                                                    data-date="{{ optional($slot->date)->format('Y-m-d') }}"
                                                                    data-period="{{ $slot->period_number }}">
                                                                    <td class="text-center">
                                                                        <input type="checkbox" class="slot-check"
                                                                            @disabled($isEvent || $batchReadOnly || $supportLocked)>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span
                                                                            class="period-badge {{ $slot->period_number <= 5 ? 'period-morning' : 'period-afternoon' }}">
                                                                            {{ $slot->period_number }}
                                                                        </span>
                                                                            <input type="hidden" data-field="slot_id"
                                                                                value="{{ $slot->id }}">
                                                                        </td>
                                                                    <td class="text-center">
                                                                        @if ($isMerged && !$supportLocked)
                                                                            <span class="badge badge-success ml-1"
                                                                                style="font-size: 10px;">Đã ghép</span>
                                                                            @if (!$batchReadOnly && !$supportLocked)
                                                                                <button type="button"
                                                                                    class="btn btn-link btn-sm p-0 ml-1 split-merge-group-btn"
                                                                                    data-slot-id="{{ $slot->id }}"
                                                                                    data-group-id="{{ $mergeGroupId }}"
                                                                                    style="font-size: 10px; vertical-align: baseline;">
                                                                                    Tách ghép
                                                                                </button>
                                                                            @endif
                                                                        @elseif (!$isEvent && !$batchReadOnly && !$supportLocked)
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
                                                                            Kế hoạch:
                                                                            {{ $slot->monthlySchedule?->plan?->name ?? '-' }}
                                                                        </span>
                                                                        @if ($supportMeta)
                                                                            <div class="mt-1">
                                                                                <span class="badge badge-warning support-status-pill">{{ $supportMeta['label'] ?? 'Hỗ trợ' }}</span>
                                                                                <span class="small text-muted d-block">
                                                                                    {{ $supportMeta['support_department_name'] ?? '-' }}
                                                                                </span>
                                                                            </div>
                                                                        @endif
                                                                        @if ($isEvent)
                                                                            <span class="event-readonly-pill"><i
                                                                                    class="fas fa-lock mr-1"></i>Không phân
                                                                                công</span>
                                                                        @elseif ($supportLocked)
                                                                            <span class="event-readonly-pill"><i
                                                                                    class="fas fa-lock mr-1"></i>Khóa bởi hỗ trợ liên khoa</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if ($isEvent)
                                                                            <span class="text-muted small">-</span>
                                                                        @elseif ($supportLocked)
                                                                            <div class="support-teacher-readonly">
                                                                                <div class="font-weight-bold">
                                                                                    {{ $slot->teacher?->name ?? $supportMeta['teacher_name'] ?? 'Chưa có GV hỗ trợ' }}
                                                                                </div>
                                                                                <div class="small text-muted">
                                                                                    {{ $supportMeta['status'] === 'pending_pdt' ? 'Đang chờ PĐT duyệt' : ($supportMeta['status'] === 'assigned_to_department' ? 'PĐT đã giao khoa hỗ trợ' : ($supportMeta['status'] === 'department_assigning' ? 'Khoa hỗ trợ đang phân công' : 'GV hỗ trợ đã xác nhận')) }}
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <select data-field="teacher_id"
                                                                                class="form-control form-control-sm"
                                                                                data-initial-value="{{ (string) ($slot->teacher_id ?? '') }}"
                                                                                @disabled($batchReadOnly)>
                                                                                <option value="">-- Chọn GV --
                                                                                </option>
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
                                                                                    str_replace(
                                                                                        '_',
                                                                                        ' ',
                                                                                        (string) ($slot->event_type ??
                                                                                            'event'),
                                                                                    ),
                                                                                );
                                                                            @endphp
                                                                            <div class="event-subject-cell">
                                                                                <span
                                                                                    class="event-chip">{{ $eventTypeLabel }}</span>
                                                                                <div class="event-title">
                                                                                    {{ $slot->subject ?? '--' }}</div>
                                                                                <div class="event-meta">S&#7921; ki&#7879;n
                                                                                    h&#7885;c k&#7923; hi&#7875;n th&#7883;
                                                                                    c&#249;ng l&#7883;ch th&#225;ng</div>
                                                                            </div>
                                                                        @else
                                                                            <input type="hidden" data-field="subject_id"
                                                                                value="{{ $selectedSubjectId }}">
                                                                            <div class="font-weight-bold"
                                                                                style="font-size: .82rem;">
                                                                                {{ $subjectLabel }}
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
                                                                            <select data-field="subject_lesson_id"
                                                                                class="form-control form-control-sm"
                                                                                data-initial-value="{{ (string) ($slot->subject_lesson_id ?? '') }}"
                                                                                @disabled($batchReadOnly || $supportLocked)>
                                                                                <option value="">-- Chọn bài --
                                                                                </option>
                                                                                @foreach ($lessonOptions as $lesson)
                                                                                    <option value="{{ $lesson->id }}"
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
                                                                            <select data-field="room_id"
                                                                                class="form-control form-control-sm"
                                                                                data-initial-value="{{ (string) ($slot->room_id ?? '') }}"
                                                                                @disabled($batchReadOnly || $supportLocked)>
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
                                                                                data-field="content"
                                                                                value="{{ $oldSlot['content'] ?? $slot->content }}"
                                                                                data-initial-value="{{ $slot->content ?? '' }}"
                                                                                maxlength="500" placeholder="Nội dung"
                                                                                @disabled($batchReadOnly || $supportLocked)>
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
                                                                                data-field="note"
                                                                                value="{{ $oldSlot['note'] ?? $slot->note }}"
                                                                                data-initial-value="{{ $slot->note ?? '' }}"
                                                                                maxlength="500" placeholder="Ghi chú"
                                                                                @disabled($batchReadOnly || $supportLocked)>
                                                                        @endif
                                                                    </td>
                                                                    @if (!$isEvent)
                                                                        <td class="d-none"></td>
                                                                    @endif
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
                                <button type="submit" class="btn btn-success mr-2 mb-2"
                                    id="assignmentSaveBtn">
                                    <i class="fas fa-save mr-1"></i>Lưu phân công
                                </button>
                            @endif
                            @if (!$isAggregateAssignment)
                                <small class="text-muted mb-2">Workflow phê duyệt theo kế hoạch tháng đã ngừng sử dụng.
                                    Vui lòng dùng workflow Phân công theo Khoa + Tháng.</small>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if ($supportWorkloadRows->isNotEmpty())
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-left-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                            <div>
                                <h5 class="card-title mb-1 text-warning font-weight-bold">
                                    <i class="fas fa-hands-helping mr-2"></i>Nhiệm vụ hỗ trợ liên khoa
                                </h5>
                                <p class="mb-0 text-muted">
                                    Hiển thị các tiết Khoa B đang hỗ trợ theo đúng workflow. Không tạo bản sao schedule_slot, chỉ cập nhật trực tiếp trên dòng nhiệm vụ.
                                </p>
                            </div>
                            <div class="mt-2 mt-lg-0">
                                <span class="badge badge-warning px-3 py-2">
                                    Tổng: {{ $supportWorkloadSummary['total'] ?? 0 }}
                                </span>
                                <span class="badge badge-success px-3 py-2 ml-1">
                                    Đã xác nhận: {{ $supportWorkloadSummary['confirmed'] ?? 0 }}
                                </span>
                            </div>
                        </div>

                        <div class="btn-group btn-group-sm mt-3 flex-wrap" role="group" aria-label="Bộ lọc nhiệm vụ hỗ trợ">
                            <button type="button" class="btn btn-warning active" data-support-filter="all">Tất cả</button>
                            <button type="button" class="btn btn-outline-warning" data-support-filter="assigned_to_department">PĐT giao khoa</button>
                            <button type="button" class="btn btn-outline-warning" data-support-filter="department_assigning">Khoa đang phân công</button>
                            <button type="button" class="btn btn-outline-warning" data-support-filter="completed">Đã xác nhận</button>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-hover table-sm mb-0" id="supportWorkloadTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Badge</th>
                                        <th>Khoa yêu cầu</th>
                                        <th>Lớp</th>
                                        <th>Môn</th>
                                        <th>Ngày</th>
                                        <th>Tiết</th>
                                        <th>Phòng</th>
                                        <th>GV được cử</th>
                                        <th>Ghi chú yêu cầu</th>
                                        <th>Trạng thái</th>
                                        @if ($supportCanManageAssignments)
                                            <th style="width: 170px;">Thao tác</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supportWorkloadRows as $supportRow)
                                        @php
                                            $requestItemId = (int) ($supportRow['request_item_id'] ?? 0);
                                            $rowAvailabilityMap = $supportTeacherAvailabilityMap[$requestItemId] ?? [];
                                            $isEditableSupportRow = (bool) ($supportRow['can_edit'] ?? false) && $supportCanManageAssignments;
                                            $selectedSupportTeacherId = (int) ($supportRow['assigned_teacher_id'] ?? 0);
                                        @endphp
                                        <tr data-support-status="{{ $supportRow['request_status'] ?? '' }}"
                                            data-support-item-id="{{ $requestItemId }}"
                                            data-support-row-editable="{{ $isEditableSupportRow ? '1' : '0' }}">
                                            <td>
                                                <span class="badge badge-warning">{{ $supportRow['support_badge_label'] ?? 'Hỗ trợ liên khoa' }}</span>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">Khoa yêu cầu: {{ $supportRow['requesting_department_name'] ?? '-' }}</div>
                                                <div class="small text-muted">Khoa hỗ trợ: {{ $supportRow['assigned_department_name'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">{{ $supportRow['class_code'] ?? '-' }}</div>
                                                <div class="small text-muted">{{ $supportRow['class_name'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                <div class="font-weight-bold">{{ $supportRow['subject_code'] ?? '-' }}</div>
                                                <div class="small text-muted">{{ $supportRow['subject_name'] ?? '-' }}</div>
                                            </td>
                                            <td class="text-nowrap">{{ $supportRow['date_label'] ?? '-' }}</td>
                                            <td>{{ $supportRow['period_number'] ?? '-' }}</td>
                                            <td>
                                                <div class="font-weight-bold">{{ $supportRow['room_code'] ?? '-' }}</div>
                                                <div class="small text-muted">{{ $supportRow['room_name'] ?? '-' }}</div>
                                            </td>
                                            <td>
                                                @if ($isEditableSupportRow)
                                                    <div class="support-assignment-field">
                                                        <select class="form-control form-control-sm support-teacher-select"
                                                            data-support-row-id="{{ $requestItemId }}"
                                                            name="teacher_id"
                                                            form="support-assignment-form-{{ $requestItemId }}"
                                                            data-current-value="{{ $selectedSupportTeacherId > 0 ? $selectedSupportTeacherId : '' }}">
                                                            <option value="">-- Chọn GV khoa này --</option>
                                                            @foreach ($teachers as $teacher)
                                                                @php
                                                                    $teacherId = (int) $teacher->id;
                                                                    $teacherAvailable = $rowAvailabilityMap[$teacherId] ?? true;
                                                                @endphp
                                                                <option value="{{ $teacher->id }}"
                                                                    @selected($selectedSupportTeacherId === $teacherId)
                                                                    @disabled($teacherAvailable === false)
                                                                    data-server-disabled="{{ $teacherAvailable === false ? '1' : '0' }}"
                                                                    data-teacher-name="{{ $teacher->name }}">
                                                                    {{ $teacher->name }}@if ($teacher->teacher_code)
                                                                        ({{ $teacher->teacher_code }})
                                                                    @endif
                                                                    @if ($teacherAvailable === false)
                                                                        - Bận lịch
                                                                    @endif
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="small text-muted mt-1 support-availability-note">
                                                            JS sẽ tự khóa GV đã chọn ở các dòng khác. Backend vẫn kiểm tra trùng lịch toàn hệ thống.
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="font-weight-bold">{{ $supportRow['teacher_name'] ?? '-' }}</div>
                                                    @if (!empty($supportRow['assignment_source']) && $supportRow['assignment_source'] === 'department_support')
                                                        <div class="small text-muted">Đã cập nhật lên lịch khoa B</div>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                <div class="small text-muted">
                                                    {{ $supportRow['request_note'] ?? 'Không có ghi chú' }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $supportRow['request_status_label'] ?? '-' }}</span>
                                                <div class="small text-muted mt-1">{{ $supportRow['item_status_label'] ?? '-' }}</div>
                                            </td>
                                            @if ($supportCanManageAssignments)
                                                <td class="text-nowrap">
                                                    @if ($isEditableSupportRow)
                                                        <form method="POST"
                                                            action="{{ $supportRow['confirm_url'] ?? '#' }}"
                                                            class="support-assignment-form"
                                                            data-support-row-form="{{ $requestItemId }}"
                                                            id="support-assignment-form-{{ $requestItemId }}">
                                                            @csrf
                                                            <input type="text" name="note"
                                                                class="form-control form-control-sm mb-2"
                                                                maxlength="500"
                                                                value="{{ old('support_note_' . $requestItemId, '') }}"
                                                                placeholder="Ghi chú phân công (tuỳ chọn)">
                                                            <button type="submit" class="btn btn-success btn-sm support-save-btn"
                                                                data-support-save="{{ $requestItemId }}">
                                                                <i class="fas fa-check mr-1"></i>Lưu
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="badge badge-secondary">Chỉ xem</span>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($supportCanManageAssignments || $canCreateSupportRequest)
        <div class="modal fade" id="supportRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-hands-helping mr-1"></i>Đề nghị khoa hỗ trợ
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Chọn các tiết chưa có giảng viên và đã có bài học rõ ràng để gửi đề nghị. Hệ thống sẽ tự gom các nhóm ghép active thành một đơn vị.
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Khoa hỗ trợ</label>
                            <select class="form-control" id="supportDepartmentSelect">
                                <option value="">-- Chọn khoa --</option>
                                @foreach ($supportDepartments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Lý do / ghi chú</label>
                            <textarea class="form-control" id="supportRequestNote" rows="3" maxlength="2000"
                                placeholder="Mô tả ngắn gọn lý do cần hỗ trợ"></textarea>
                        </div>

                        <div class="alert alert-warning d-none" id="supportRequestModalError"></div>
                        <div class="small text-muted" id="supportRequestSelectionInfo">Chưa chọn tiết nào.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                        <button type="button" class="btn btn-warning" id="submitSupportRequestBtn">
                            <i class="fas fa-paper-plane mr-1"></i>Gửi đề nghị
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($supportCanManageAssignments || $canCreateSupportRequest)
        <div class="modal fade" id="supportRequestListModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-clipboard-list mr-1"></i>Danh sách slot đã gửi yêu cầu hỗ trợ
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="supportRequestListModalBody">
                        <div class="text-center py-5 text-muted">
                            <div class="spinner-border text-info mb-3" role="status" aria-hidden="true"></div>
                            <div>Đang tải danh sách...</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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

        .assignment-save-error-row td {
            background: rgba(231, 74, 59, .08) !important;
        }

        .assignment-field-error {
            margin-top: .25rem;
        }

        .bulk-search-picker-mount {
            min-width: 220px;
        }

        .bulk-search-picker {
            position: relative;
            min-width: 220px;
        }

        .bulk-search-trigger {
            min-width: 220px;
            justify-content: space-between;
        }

        .bulk-search-trigger-label {
            display: inline-block;
            max-width: 176px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: middle;
        }

        .bulk-search-panel {
            position: absolute;
            top: calc(100% + .35rem);
            left: 0;
            z-index: 1080;
            width: 320px;
            max-width: min(320px, calc(100vw - 2rem));
            padding: .5rem;
            background: #fff;
            border: 1px solid rgba(133, 135, 150, .25);
            border-radius: .35rem;
            box-shadow: 0 .5rem 1.25rem rgba(58, 59, 69, .18);
        }

        .bulk-search-picker.is-open .bulk-search-panel {
            display: block;
        }

        .bulk-search-results {
            max-height: 240px;
            overflow: auto;
            padding-right: .1rem;
        }

        .bulk-search-item {
            display: block;
            width: 100%;
            padding: .45rem .55rem;
            margin-bottom: .25rem;
            text-align: left;
            white-space: normal;
            border: 0;
            border-radius: .25rem;
            background: #f8f9fc;
            color: #3a3b45;
        }

        .bulk-search-item:hover,
        .bulk-search-item:focus {
            background: #eaecf4;
            outline: none;
        }

        .bulk-search-empty {
            padding: .5rem .55rem;
            color: #858796;
            font-size: .875rem;
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

        .event-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
            background: #17a2b8;
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

        .support-status-pill {
            display: inline-flex;
            align-items: center;
            padding: .25rem .55rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            margin-top: .2rem;
        }

        .support-teacher-readonly {
            display: flex;
            flex-direction: column;
            gap: .15rem;
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

        /* Form controls inside table */
        .slot-table .form-control,
        .slot-table select,
        .slot-table input[type="text"] {
            background: #ffffff !important;
            color: #2d3748 !important;
            border: 1px solid #d1d3e2;
            border-radius: .25rem;
            font-size: .82rem;
            transition: border-color .15s;
        }

        .slot-table select option {
            color: #2d3748;
            background: #ffffff;
        }

        .slot-table .form-control:disabled,
        .slot-table select:disabled,
        .slot-table input[type="text"]:disabled {
            background: #eef2f7 !important;
            color: #5f6b7a !important;
            opacity: 1;
            cursor: not-allowed;
        }

        .slot-table .form-control:focus,
        .slot-table select:focus,
        .slot-table input[type="text"]:focus {
            background: #ffffff !important;
            color: #2d3748 !important;
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
        #assignToolbar {
            border: 1px solid #e3e6f0;
            border-radius: .35rem;
        }

        #assignToolbar label {
            display: block;
            margin-bottom: 2px;
        }

        /* Checkbox in table */
        .slot-check {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .select-all-date {
            width: 15px;
            height: 15px;
            cursor: pointer;
        }

        /* Highlight checked rows */
        .slot-check:checked {
            accent-color: #4e73df;
        }

        .slot-table tbody tr.checked-row {
            background: #dbeafe !important;
        }

        /* Support request list modal */
        #supportRequestListModal .modal-dialog {
            max-width: calc(100vw - 2rem);
            width: calc(100vw - 2rem);
        }

        #supportRequestListModal .modal-content {
            border: 0;
            border-radius: .65rem;
            overflow: hidden;
            max-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
        }

        #supportRequestListModal .modal-body {
            padding: 1rem;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        #supportRequestListModal [data-support-request-modal-table] {
            max-width: 100%;
            min-height: 0;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        #supportRequestListModal [data-support-request-modal-table] .table-responsive {
            max-height: calc(100vh - 320px);
            overflow: auto;
            min-height: 0;
            flex: 1 1 auto;
        }

        #supportRequestListModal table {
            min-width: 1120px;
            margin-bottom: 0;
            font-size: .84rem;
        }

        #supportRequestListModal thead th {
            white-space: nowrap;
            vertical-align: middle;
        }

        #supportRequestListModal tbody td {
            vertical-align: top;
        }

        #supportRequestListModal tbody td:nth-child(10) {
            min-width: 240px;
            max-width: 280px;
            white-space: normal;
            word-break: break-word;
        }

        #supportRequestListModal tbody td:nth-child(11) {
            min-width: 190px;
            white-space: nowrap;
        }

        #supportRequestListModal tbody td:nth-child(6) .font-weight-bold,
        #supportRequestListModal tbody td:nth-child(9) .font-weight-bold {
            line-height: 1.15;
        }

        #supportRequestListModal tbody td:nth-child(6) .small,
        #supportRequestListModal tbody td:nth-child(9) .small {
            line-height: 1.1;
        }

        #supportRequestListModal [data-support-request-modal-pagination] {
            gap: .75rem;
        }

        #supportRequestListModal [data-support-request-modal-pagination] .pagination {
            margin-bottom: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        #supportRequestListModal [data-support-request-modal-pagination] .page-item {
            margin-bottom: .35rem;
        }

        #supportRequestListModal [data-support-request-modal-pagination] .page-link {
            padding: .45rem .8rem;
        }

        @media (max-width: 768px) {
            #supportRequestListModal .modal-dialog {
                max-width: calc(100vw - 1rem);
                width: calc(100vw - 1rem);
                margin: .5rem auto;
            }

            #supportRequestListModal .modal-body {
                padding: .75rem;
            }

            #supportRequestListModal table {
                min-width: 980px;
            }

            #supportRequestListModal [data-support-request-modal-table] .table-responsive {
                max-height: calc(100vh - 300px);
            }

            #supportRequestListModal [data-support-request-modal-pagination] {
                flex-direction: column;
                align-items: stretch;
            }

            #supportRequestListModal [data-support-request-modal-pagination] .pagination {
                justify-content: center;
            }
        }
    </style>

    <div class="modal fade" id="mergeSlotModal" tabindex="-1" role="dialog" aria-labelledby="mergeSlotModalLabel"
        aria-hidden="true">
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
        document.addEventListener('DOMContentLoaded', function() {
            var allRows = Array.prototype.slice.call(document.querySelectorAll('.slot-table tbody tr'));
            var filterType = document.getElementById('filterSlotType');
            var filterClass = document.getElementById('filterClass');
            var filterSess = document.getElementById('filterSession');
            var filterCount = document.getElementById('filterCount');
            var bulkTeacher = document.getElementById('bulkTeacher');
            var bulkRoom = document.getElementById('bulkRoom');
            var bulkApply = document.getElementById('bulkApplyBtn');
            var bulkRoomApply = document.getElementById('bulkRoomApplyBtn');
            var bulkTeacherPickerMount = document.getElementById('bulkTeacherPickerMount');
            var bulkRoomPickerMount = document.getElementById('bulkRoomPickerMount');
            var selectAll = document.getElementById('selectAllSlots');
            var autoFillBtn = document.getElementById('autoFillBtn');
            var assignmentForm = document.getElementById('monthlyAssignmentForm');
            var assignmentSaveBtn = document.getElementById('assignmentSaveBtn');
            var assignmentSaveAlert = document.getElementById('assignmentSaveAlert');
            var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
            var currentMonthlyScheduleId = @json($monthlySchedule->id);
            var mergeCandidatesUrlTemplate = @json(route('monthly-schedule.assignment.merge-candidates', ['id' => '__MONTHLY__', 'slotId' => '__SLOT__']));
            var mergeSlotUrlTemplate = @json(route('monthly-schedule.assignment.merge', ['id' => '__MONTHLY__', 'slotId' => '__SLOT__']));
            var splitMergeUrlTemplate = @json(route('monthly-schedule.assignment.split', ['id' => '__MONTHLY__', 'groupId' => '__GROUP__']));
            var supportRequestStoreUrl = @json($supportRequestStoreUrl);
            var currentDepartmentId = @json($currentDepartmentId);
            var supportRequestModalEl = document.getElementById('supportRequestModal');
            var supportRequestModalError = document.getElementById('supportRequestModalError');
            var supportRequestSelectionInfo = document.getElementById('supportRequestSelectionInfo');
            var supportDepartmentSelect = document.getElementById('supportDepartmentSelect');
            var supportRequestNote = document.getElementById('supportRequestNote');
            var submitSupportRequestBtn = document.getElementById('submitSupportRequestBtn');
            var openSupportRequestModalBtn = document.getElementById('openSupportRequestModal');
            var supportRequestListModalUrl = @json(route('monthly-schedule.teaching-support-requests.modal', $monthlySchedule->id));
            var supportRequestListModalEl = document.getElementById('supportRequestListModal');
            var supportRequestListModalBody = document.getElementById('supportRequestListModalBody');
            var openSupportRequestListModalBtn = document.getElementById('openSupportRequestListModal');
            var supportAssignmentForms = Array.prototype.slice.call(document.querySelectorAll('.support-assignment-form'));
            var supportTeacherSelects = Array.prototype.slice.call(document.querySelectorAll('.support-teacher-select'));
            var supportAssignmentRows = Array.prototype.slice.call(document.querySelectorAll('tr[data-support-item-id]'));
            var supportTeacherAvailabilityMap = @json($supportTeacherAvailabilityMap);
            var mergeModalEl = document.getElementById('mergeSlotModal');
            var mergeModalError = document.getElementById('mergeSlotModalError');
            var mergeModalInfo = document.getElementById('mergeSlotModalInfo');
            var mergeCandidatesWrap = document.getElementById('mergeSlotCandidatesWrap');
            var currentMergeBaseSlotId = null;
            var currentMergeBaseMonthlyScheduleId = null;
            var currentMergeCandidates = [];
            var supportRequestInFlight = false;
            var dirtyRows = new Map();
            var isSavingAssignments = false;
            var supportFilterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-support-filter]'));
            var supportRows = Array.prototype.slice.call(document.querySelectorAll('#supportWorkloadTable tbody tr'));
            var currentSupportFilter = 'all';

            function applySupportFilter(filterValue) {
                currentSupportFilter = filterValue || 'all';

                supportFilterButtons.forEach(function(button) {
                    var isActive = button.getAttribute('data-support-filter') === currentSupportFilter;
                    button.classList.toggle('btn-warning', isActive);
                    button.classList.toggle('btn-outline-warning', !isActive);
                    button.classList.toggle('active', isActive);
                });

                supportRows.forEach(function(row) {
                    var rowStatus = row.getAttribute('data-support-status') || '';
                    var visible = currentSupportFilter === 'all' || rowStatus === currentSupportFilter;
                    row.style.display = visible ? '' : 'none';
                });
            }

            if (supportFilterButtons.length > 0) {
                supportFilterButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        applySupportFilter(button.getAttribute('data-support-filter') || 'all');
                    });
                });

                applySupportFilter('all');
            }

            function getSupportRowByItemId(itemId) {
                if (!itemId) {
                    return null;
                }

                return document.querySelector('tr[data-support-item-id="' + itemId + '"]');
            }

            function getSupportTeacherAvailability(rowId, teacherId) {
                return !!(supportTeacherAvailabilityMap && supportTeacherAvailabilityMap[String(rowId)] && supportTeacherAvailabilityMap[String(rowId)][String(teacherId)] !== false);
            }

            function clearSupportRowError(row) {
                if (!row) {
                    return;
                }

                row.classList.remove('assignment-save-error-row');
                row.querySelectorAll('.support-row-error').forEach(function(node) {
                    node.remove();
                });
                row.querySelectorAll('.support-teacher-select.is-invalid').forEach(function(field) {
                    field.classList.remove('is-invalid');
                });
            }

            function markSupportRowError(row, message) {
                if (!row) {
                    return;
                }

                row.classList.add('assignment-save-error-row');
                var target = row.querySelector('.support-teacher-select');
                if (target) {
                    target.classList.add('is-invalid');
                }

                var cell = row.querySelector('td');
                if (cell) {
                    var errorNode = document.createElement('div');
                    errorNode.className = 'text-danger small mt-1 support-row-error';
                    errorNode.textContent = message;
                    cell.appendChild(errorNode);
                }
            }

            function refreshSupportTeacherAvailability() {
                if (!supportTeacherSelects.length) {
                    return;
                }

                var selectedByTeacher = {};
                supportTeacherSelects.forEach(function(select) {
                    var value = select.value || '';
                    if (!value) {
                        return;
                    }

                    if (!selectedByTeacher[value]) {
                        selectedByTeacher[value] = [];
                    }
                    selectedByTeacher[value].push(select.getAttribute('data-support-row-id') || '');
                });

                supportTeacherSelects.forEach(function(select) {
                    var rowId = select.getAttribute('data-support-row-id') || '';
                    Array.prototype.slice.call(select.options).forEach(function(option) {
                        if (!option.value) {
                            option.disabled = false;
                            return;
                        }

                        var serverDisabled = option.getAttribute('data-server-disabled') === '1';
                        var selectedElsewhere = selectedByTeacher[option.value] && selectedByTeacher[option.value].some(function(selectedRowId) {
                            return selectedRowId && selectedRowId !== rowId;
                        });

                        option.disabled = serverDisabled || selectedElsewhere;
                    });
                });
            }

            async function submitSupportAssignmentForm(form) {
                if (!form) {
                    return;
                }

                var rowId = form.getAttribute('data-support-row-form') || '';
                var row = getSupportRowByItemId(rowId);
                clearSupportRowError(row);

                var formData = new FormData(form);
                var selectedTeacher = row ? row.querySelector('.support-teacher-select') : null;
                if (selectedTeacher && !formData.has('teacher_id')) {
                    formData.append('teacher_id', selectedTeacher.value || '');
                }

                var response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                var payload = null;
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = {};
                }

                if (!response.ok) {
                    var message = getJsonErrorMessage(payload, 'Không thể lưu phân công hỗ trợ.');
                    if (payload && payload.errors) {
                        var firstErrorKey = Object.keys(payload.errors)[0];
                        var firstErrorMessage = firstErrorKey && Array.isArray(payload.errors[firstErrorKey]) && payload.errors[firstErrorKey].length
                            ? payload.errors[firstErrorKey][0]
                            : message;
                        markSupportRowError(row, firstErrorMessage);
                    } else {
                        markSupportRowError(row, message);
                    }

                    showToast(message);
                    return;
                }

                showToast(payload && payload.message ? payload.message : 'Đã lưu phân công hỗ trợ.');
                window.location.reload();
            }

            supportTeacherSelects.forEach(function(select) {
                select.addEventListener('change', function() {
                    var rowId = select.getAttribute('data-support-row-id') || '';
                    var row = getSupportRowByItemId(rowId);
                    clearSupportRowError(row);
                    refreshSupportTeacherAvailability();
                });
            });

            supportAssignmentForms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    submitSupportAssignmentForm(form);
                });
            });

            refreshSupportTeacherAvailability();

            function getFieldElement(row, field) {
                if (!row) {
                    return null;
                }

                return row.querySelector('[data-field="' + field + '"]');
            }

            function getTeacherSelect(row) {
                return getFieldElement(row, 'teacher_id');
            }

            function getRoomSelect(row) {
                return getFieldElement(row, 'room_id');
            }

            function getSubjectLessonSelect(row) {
                return getFieldElement(row, 'subject_lesson_id');
            }

            function getContentInput(row) {
                return getFieldElement(row, 'content');
            }

            function getNoteInput(row) {
                return getFieldElement(row, 'note');
            }

            function getSlotId(row) {
                return row ? String(row.getAttribute('data-slot-id') || '') : '';
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

            function getActiveMergeGroupRows(row) {
                if (!row) {
                    return [];
                }

                var groupId = getRowMergeGroupId(row);
                var groupStatus = getRowMergeGroupStatus(row);
                if (!groupId || groupStatus !== 'active') {
                    return [];
                }

                return Array.prototype.slice.call(
                    document.querySelectorAll(
                        'tr[data-merge-group-id="' + escapeHtml(groupId) + '"][data-merge-group-status="active"]'
                    )
                );
            }

            function syncActiveMergeGroupField(row, selector, value, options) {
                var config = options || {};
                var rows = getActiveMergeGroupRows(row);
                if (!rows.length) {
                    return;
                }

                rows.forEach(function(groupRow) {
                    if (groupRow === row) {
                        return;
                    }

                    var field = groupRow.querySelector(selector);
                    if (!field) {
                        return;
                    }

                    if (field.tagName === 'SELECT') {
                        setSelectValue(field, value, !!config.dispatchChange);
                        syncRowDirtyState(groupRow);
                        return;
                    }

                    setInputValue(field, value);
                    syncRowDirtyState(groupRow);
                });
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

            function normalizeComparableValue(value) {
                if (value === null || value === undefined || value === '') {
                    return '';
                }

                return String(value);
            }

            function getRowEditableFields(row) {
                if (!row || row.dataset.slotType === 'event') {
                    return [];
                }

                return Array.prototype.slice.call(
                    row.querySelectorAll(
                        '[data-field="teacher_id"], [data-field="subject_lesson_id"], [data-field="room_id"], [data-field="content"], [data-field="note"]'
                    )
                );
            }

            function rowHasAssignmentChanges(row) {
                return getRowEditableFields(row).some(function(field) {
                    return normalizeComparableValue(field.value) !== normalizeComparableValue(field.dataset.initialValue);
                });
            }

            function syncRowDirtyState(row) {
                if (!row) {
                    return;
                }

                var slotId = getSlotId(row);
                if (!slotId) {
                    return;
                }

                if (rowHasAssignmentChanges(row)) {
                    dirtyRows.set(slotId, row);
                } else {
                    dirtyRows.delete(slotId);
                }
            }

            function syncRowsDirtyState(rows) {
                (rows || []).forEach(function(row) {
                    syncRowDirtyState(row);
                });
            }

            function updateRowInitialValues(row) {
                getRowEditableFields(row).forEach(function(field) {
                    field.dataset.initialValue = normalizeComparableValue(field.value);
                });
                syncRowDirtyState(row);
            }

            function buildChangePayload(row) {
                var teacherSelect = getTeacherSelect(row);
                var lessonSelect = getSubjectLessonSelect(row);
                var roomSelect = getRoomSelect(row);
                var contentInput = getContentInput(row);
                var noteInput = getNoteInput(row);

                return {
                    slot_id: toNullableInt(getSlotId(row)),
                    teacher_id: teacherSelect && teacherSelect.value !== '' ? toNullableInt(teacherSelect.value) : null,
                    subject_lesson_id: lessonSelect && lessonSelect.value !== '' ? toNullableInt(lessonSelect.value) : null,
                    room_id: roomSelect && roomSelect.value !== '' ? toNullableInt(roomSelect.value) : null,
                    content: contentInput && contentInput.value !== '' ? contentInput.value : null,
                    note: noteInput && noteInput.value !== '' ? noteInput.value : null
                };
            }

            function collectDirtyChanges() {
                return Array.from(dirtyRows.values())
                    .map(function(row) {
                        return buildChangePayload(row);
                    })
                    .filter(function(change) {
                        return !!change.slot_id;
                    });
            }

            function setAssignmentSaveState(isSaving) {
                if (assignmentSaveBtn) {
                    assignmentSaveBtn.disabled = !!isSaving;
                    assignmentSaveBtn.innerHTML = isSaving ?
                        '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Đang lưu...' :
                        '<i class="fas fa-save mr-1"></i>Lưu phân công';
                }
            }

            function setAssignmentSaveAlert(message, type) {
                if (!assignmentSaveAlert) {
                    return;
                }

                if (!message) {
                    assignmentSaveAlert.className = 'alert d-none';
                    assignmentSaveAlert.textContent = '';
                    return;
                }

                assignmentSaveAlert.className = 'alert alert-' + (type || 'danger');
                assignmentSaveAlert.textContent = message;
            }

            function clearAssignmentRowErrors() {
                document.querySelectorAll('.assignment-save-error-row').forEach(function(row) {
                    row.classList.remove('assignment-save-error-row');
                });

                document.querySelectorAll('.assignment-field-error').forEach(function(node) {
                    node.remove();
                });

                document.querySelectorAll('.is-invalid[data-field]').forEach(function(field) {
                    field.classList.remove('is-invalid');
                });
            }

            function markRowSaveError(row, fieldName, message) {
                if (!row) {
                    return;
                }

                row.classList.add('assignment-save-error-row');

                var targetField = fieldName ? getFieldElement(row, fieldName) : null;
                if (targetField && targetField.type !== 'hidden') {
                    targetField.classList.add('is-invalid');
                    var existing = targetField.parentNode ? targetField.parentNode.querySelector('.assignment-field-error') : null;
                    if (existing) {
                        existing.remove();
                    }

                    var fieldError = document.createElement('div');
                    fieldError.className = 'invalid-feedback d-block assignment-field-error';
                    fieldError.textContent = message;
                    targetField.insertAdjacentElement('afterend', fieldError);
                    return;
                }

                var firstCell = row.querySelector('td');
                if (firstCell) {
                    var rowError = document.createElement('div');
                    rowError.className = 'text-danger small mt-1 assignment-field-error';
                    rowError.textContent = message;
                    firstCell.appendChild(rowError);
                }
            }

            function findRowByIndex(index, changes) {
                var change = changes[index];
                if (!change || !change.slot_id) {
                    return null;
                }

                return document.querySelector('tr[data-slot-id="' + change.slot_id + '"]');
            }

            function applySaveErrors(errorBag, changes) {
                clearAssignmentRowErrors();

                if (!errorBag) {
                    return;
                }

                Object.keys(errorBag).forEach(function(key) {
                    var messages = Array.isArray(errorBag[key]) ? errorBag[key] : [String(errorBag[key])];
                    if (!messages.length) {
                        return;
                    }

                    var match = key.match(/^changes\.(\d+)(?:\.(.+))?$/);
                    if (!match) {
                        return;
                    }

                    var changeIndex = parseInt(match[1], 10);
                    var fieldName = match[2] || 'slot_id';
                    var row = findRowByIndex(changeIndex, changes);
                    if (!row) {
                        return;
                    }

                    markRowSaveError(row, fieldName, messages[0]);
                });

                var firstErrorRow = document.querySelector('.assignment-save-error-row');
                if (firstErrorRow && firstErrorRow.scrollIntoView) {
                    firstErrorRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            async function saveAssignmentChanges(options) {
                var config = options || {};
                if (isSavingAssignments) {
                    return false;
                }

                setAssignmentSaveAlert('');
                clearAssignmentRowErrors();

                var changes = collectDirtyChanges();
                if (!changes.length) {
                    if (!config.silentIfClean) {
                        showToast('Không có thay đổi mới để lưu.');
                    }
                    return true;
                }

                isSavingAssignments = true;
                setAssignmentSaveState(true);

                try {
                    var response = await fetch(assignmentForm.action, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            changes: changes
                        })
                    });

                    var payload = null;
                    var contentType = response.headers.get('content-type') || '';
                    if (contentType.indexOf('application/json') !== -1) {
                        try {
                            payload = await response.json();
                        } catch (parseError) {
                            payload = null;
                        }
                    } else {
                        var responseText = await response.text();
                        payload = {
                            message: responseText
                        };
                    }

                    if (!response.ok) {
                        if (response.status === 422 && payload && payload.errors) {
                            setAssignmentSaveAlert(payload.message || 'Dữ liệu phân công chưa hợp lệ.', 'danger');
                            applySaveErrors(payload.errors, changes);
                            return false;
                        }

                        var errorMessage = payload && payload.message ? payload.message :
                            (response.status === 419 ? 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.' :
                                response.status === 403 ? 'Bạn không có quyền lưu phân công hoặc màn này đang bị khóa.' :
                                'Không thể lưu phân công.');
                        setAssignmentSaveAlert(errorMessage, 'danger');
                        showToast(errorMessage);
                        return false;
                    }

                    var updatedSlotIds = Array.isArray(payload && payload.updated_slot_ids) ? payload.updated_slot_ids : [];
                    var changedRows = updatedSlotIds.length ?
                        updatedSlotIds.map(function(slotId) {
                            return document.querySelector('tr[data-slot-id="' + slotId + '"]');
                        }).filter(Boolean) :
                        Array.from(dirtyRows.values());

                    changedRows.forEach(function(row) {
                        updateRowInitialValues(row);
                    });

                    dirtyRows.clear();
                    clearAssignmentRowErrors();
                    setAssignmentSaveAlert('');
                    showToast(payload && payload.message ? payload.message : 'Đã lưu phân công thành công.');
                    return true;
                } catch (error) {
                    var fallbackMessage = error && error.message ? error.message : 'Không thể lưu phân công.';
                    setAssignmentSaveAlert(fallbackMessage, 'danger');
                    showToast(fallbackMessage);
                    return false;
                } finally {
                    isSavingAssignments = false;
                    setAssignmentSaveState(false);
                }
            }

            function normalizeSearchText(value) {
                return String(value === null || value === undefined ? '' : value)
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/đ/g, 'd');
            }

            function getBulkSelectedRows() {
                return allRows.filter(function(row) {
                    var cb = row.querySelector('.slot-check');
                    return cb && cb.checked && row.style.display !== 'none';
                });
            }

            function getSelectDisplayLabel(select, placeholder) {
                if (!select) {
                    return placeholder || '';
                }

                var option = select.options[select.selectedIndex];
                if (option && option.value) {
                    return option.textContent.trim();
                }

                return placeholder || '';
            }

            function setupSearchableBulkPicker(config) {
                var mount = config.mount;
                var select = config.select;
                if (!mount || !select) {
                    return null;
                }

                var wrapper = document.createElement('div');
                wrapper.className = 'bulk-search-picker';

                var trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = 'btn btn-outline-secondary btn-sm bulk-search-trigger';
                trigger.disabled = !!select.disabled;
                trigger.setAttribute('aria-haspopup', 'listbox');
                trigger.setAttribute('aria-expanded', 'false');

                var triggerLabel = document.createElement('span');
                triggerLabel.className = 'bulk-search-trigger-label';

                var triggerIcon = document.createElement('i');
                triggerIcon.className = 'fas fa-chevron-down ml-2';

                trigger.appendChild(triggerLabel);
                trigger.appendChild(triggerIcon);

                var panel = document.createElement('div');
                panel.className = 'bulk-search-panel';
                panel.hidden = true;

                var search = document.createElement('input');
                search.type = 'text';
                search.className = 'form-control form-control-sm';
                search.placeholder = config.searchPlaceholder || 'Tìm kiếm...';
                search.disabled = !!select.disabled;

                var results = document.createElement('div');
                results.className = 'bulk-search-results mt-2';
                results.setAttribute('role', 'listbox');

                panel.appendChild(search);
                panel.appendChild(results);
                wrapper.appendChild(trigger);
                wrapper.appendChild(panel);
                mount.appendChild(wrapper);

                var items = Array.prototype.slice.call(select.options)
                    .filter(function(option) {
                        return !!option.value;
                    })
                    .map(function(option) {
                        return {
                            value: option.value,
                            label: option.textContent.trim(),
                            search: normalizeSearchText(option.dataset.search || option.textContent || option
                                .value)
                        };
                    });

                function syncTriggerLabel() {
                    triggerLabel.textContent = getSelectDisplayLabel(select, config.placeholder || '');
                }

                function closePicker() {
                    wrapper.classList.remove('is-open');
                    panel.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                }

                function chooseValue(value) {
                    if (!value) {
                        return;
                    }

                    if (select.value !== value) {
                        select.value = value;
                        select.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }

                    closePicker();
                }

                function renderResults(query) {
                    var normalizedQuery = normalizeSearchText(query);
                    var filtered = items.filter(function(item) {
                        return normalizedQuery === '' || item.search.indexOf(normalizedQuery) !== -1;
                    });

                    results.innerHTML = '';

                    if (filtered.length === 0) {
                        var emptyState = document.createElement('div');
                        emptyState.className = 'bulk-search-empty';
                        emptyState.textContent = config.emptyText || 'Không có kết quả phù hợp.';
                        results.appendChild(emptyState);
                        return;
                    }

                    filtered.forEach(function(item) {
                        var optionButton = document.createElement('button');
                        optionButton.type = 'button';
                        optionButton.className = 'bulk-search-item';
                        optionButton.setAttribute('role', 'option');
                        optionButton.dataset.value = item.value;
                        optionButton.textContent = item.label;
                        optionButton.addEventListener('click', function() {
                            chooseValue(item.value);
                        });
                        results.appendChild(optionButton);
                    });
                }

                function openPicker() {
                    if (trigger.disabled) {
                        return;
                    }

                    wrapper.classList.add('is-open');
                    panel.hidden = false;
                    trigger.setAttribute('aria-expanded', 'true');
                    renderResults(search.value || '');
                    window.requestAnimationFrame(function() {
                        search.focus();
                        search.select();
                    });
                }

                trigger.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (panel.hidden) {
                        openPicker();
                    } else {
                        closePicker();
                    }
                });

                search.addEventListener('input', function() {
                    renderResults(search.value || '');
                });

                search.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        closePicker();
                        trigger.focus();
                        return;
                    }

                    if (event.key === 'Enter') {
                        event.preventDefault();
                        var firstOption = results.querySelector('.bulk-search-item');
                        if (firstOption) {
                            firstOption.click();
                        }
                    }
                });

                document.addEventListener('click', function(event) {
                    if (!wrapper.contains(event.target)) {
                        closePicker();
                    }
                });

                select.addEventListener('change', syncTriggerLabel);
                syncTriggerLabel();
                renderResults('');

                return {
                    refresh: syncTriggerLabel,
                    close: closePicker
                };
            }

            function setSelectValue(select, value, dispatchChange = true) {
                if (!select) {
                    return;
                }

                var normalized = value === null || value === undefined || value === '' ? '' : String(value);
                if (select.value !== normalized) {
                    select.value = normalized;
                }

                if (dispatchChange) {
                    select.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            }

            function setInputValue(input, value) {
                if (!input) {
                    return;
                }

                input.value = value === null || value === undefined ? '' : String(value);
            }
            function getMergeBasePayload(slotId) {
                var row = getMergeBaseRow(slotId);
                if (!row) {
                    return {
                        teacher_id: null,
                        room_id: null,
                        subject_id: null,
                        subject_lesson_id: null,
                        content: null,
                        note: null
                    };
                }

                var teacherSelect = getTeacherSelect(row);
                var roomSelect = getRoomSelect(row);
                var subjectInput = getFieldElement(row, 'subject_id');
                var lessonSelect = getSubjectLessonSelect(row);
                var contentInput = getContentInput(row);
                var noteInput = getNoteInput(row);

                return {
                    teacher_id: teacherSelect ? toNullableInt(teacherSelect.value) : null,
                    room_id: roomSelect ? toNullableInt(roomSelect.value) : null,
                    subject_id: subjectInput ? toNullableInt(subjectInput.value) : null,
                    subject_lesson_id: lessonSelect ? toNullableInt(lessonSelect.value) : null,
                    content: contentInput ? contentInput.value : null,
                    note: noteInput ? noteInput.value : null
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

            function getRoomUsageBucket(map, key, roomId) {
                if (!map[key]) {
                    map[key] = {};
                }

                if (!map[key][roomId]) {
                    map[key][roomId] = {
                        hasUngrouped: false,
                        activeGroups: {}
                    };
                }

                return map[key][roomId];
            }

            function registerRoomUsage(map, row, roomId) {
                var key = getSlotKey(row);
                var bucket = getRoomUsageBucket(map, key, String(roomId));
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
                allRows.forEach(function(row) {
                    var sel = getTeacherSelect(row);
                    if (!sel || !sel.value) {
                        return;
                    }

                    registerTeacherUsage(map, row, sel.value);
                });
                return map;
            }

            function buildUsedRoomMap() {
                var map = {};
                allRows.forEach(function(row) {
                    var sel = getRoomSelect(row);
                    if (!sel || !sel.value) {
                        return;
                    }

                    registerRoomUsage(map, row, sel.value);
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

            function canAssignRoomToRow(row, roomId, usedMap) {
                if (!roomId) {
                    return true;
                }

                var sel = getRoomSelect(row);
                if (!sel) {
                    return false;
                }

                var key = getSlotKey(row);
                var roomKey = String(roomId);
                var used = usedMap[key] && usedMap[key][roomKey] ? usedMap[key][roomKey] : null;
                var currentValue = sel.value ? String(sel.value) : '';
                var rowMergeGroupId = getRowMergeGroupId(row);
                var rowMergeGroupStatus = getRowMergeGroupStatus(row);

                if (currentValue === roomKey) {
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

                allRows.forEach(function(row) {
                    var sel = getTeacherSelect(row);
                    if (!sel) {
                        return;
                    }

                    var currentValue = sel.value ? String(sel.value) : '';

                    Array.prototype.slice.call(sel.options).forEach(function(opt) {
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

            function refreshRoomOptions() {
                var usedMap = buildUsedRoomMap();

                allRows.forEach(function(row) {
                    var sel = getRoomSelect(row);
                    if (!sel) {
                        return;
                    }

                    var currentValue = sel.value ? String(sel.value) : '';

                    Array.prototype.slice.call(sel.options).forEach(function(opt) {
                        if (!opt.value) {
                            opt.disabled = false;
                            opt.hidden = false;
                            return;
                        }

                        var allowed = canAssignRoomToRow(row, opt.value, usedMap);
                        var blocked = !allowed && String(opt.value) !== currentValue;
                        opt.disabled = blocked;
                        opt.hidden = blocked;
                    });
                });
            }

            function syncFilterViewState() {
                allRows.forEach(function(row) {
                    if (row.style.display !== 'none') {
                        markRowAssignedState(row);
                    }
                });

                refreshTeacherOptions();
                refreshRoomOptions();
            }

            function normalizeFilterValue(value) {
                return String(value === null || value === undefined ? '' : value).trim();
            }

            /* ══════════ POPULATE CLASS FILTER ══════════ */
            var classSet = {};
            allRows.forEach(function(r) {
                if (r.dataset.class) classSet[r.dataset.class] = true;
            });
            Object.keys(classSet).sort().forEach(function(cls) {
                var o = document.createElement('option');
                o.value = cls;
                o.textContent = cls;
                filterClass.appendChild(o);
            });

            /* ══════════ FILTER LOGIC ══════════ */
            function applyFilters() {
                var tVal = normalizeFilterValue(filterType.value);
                var cVal = normalizeFilterValue(filterClass.value);
                var sVal = normalizeFilterValue(filterSess.value);
                var shown = 0,
                    total = allRows.length;

                allRows.forEach(function(row) {
                    var matchC = !cVal || normalizeFilterValue(row.dataset.class) === cVal;
                    var matchS = !sVal || normalizeFilterValue(row.dataset.session) === sVal;
                    var matchT = !tVal || normalizeFilterValue(row.dataset.slotType) === tVal;
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
                    visibleRows.forEach(function(r) {
                        if (r.style.display !== 'none') anyVisible = true;
                    });
                    var dateBlock = table.closest('.mb-4');
                    if (dateBlock) dateBlock.style.display = anyVisible ? '' : 'none';
                });

                syncFilterViewState();

                filterCount.textContent = (cVal || sVal) ?
                    'Hiện ' + shown + ' / ' + total + ' tiết' :
                    total + ' tiết';
            }

            filterType.addEventListener('change', applyFilters);
            filterClass.addEventListener('change', applyFilters);
            filterClass.addEventListener('input', applyFilters);
            filterSess.addEventListener('change', applyFilters);
            applyFilters();
            allRows.forEach(markRowAssignedState);
            refreshTeacherOptions();
            refreshRoomOptions();

            setupSearchableBulkPicker({
                mount: bulkTeacherPickerMount,
                select: bulkTeacher,
                placeholder: '-- Chọn GV --',
                emptyText: 'Không tìm thấy giáo viên phù hợp.',
                searchPlaceholder: 'Tìm theo tên hoặc mã giáo viên...'
            });

            setupSearchableBulkPicker({
                mount: bulkRoomPickerMount,
                select: bulkRoom,
                placeholder: '-- Chọn phòng --',
                emptyText: 'Không tìm thấy phòng phù hợp.',
                searchPlaceholder: 'Tìm theo tên hoặc mã phòng...'
            });

            if (assignmentForm) {
                assignmentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    saveAssignmentChanges();
                });
            }

            /* ══════════ CHECKBOX ROW HIGHLIGHT ══════════ */
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('slot-check')) {
                    var tr = e.target.closest('tr');
                    if (tr) tr.classList.toggle('checked-row', e.target.checked);
                    updateSupportRequestSelectionInfo();
                }

                if (e.target.matches('[data-field="teacher_id"]')) {
                    var row = e.target.closest('tr');
                    if (row) {
                        syncActiveMergeGroupField(row, '[data-field="teacher_id"]', e.target.value, {
                            dispatchChange: false
                        });
                        markRowAssignedState(row);
                        getActiveMergeGroupRows(row).forEach(markRowAssignedState);
                        syncRowDirtyState(row);
                        syncRowsDirtyState(getActiveMergeGroupRows(row));
                    }
                    refreshTeacherOptions();
                }

                if (e.target.matches('[data-field="room_id"]')) {
                    var roomRow = e.target.closest('tr');
                    if (roomRow) {
                        syncActiveMergeGroupField(roomRow, '[data-field="room_id"]', e.target.value, {
                            dispatchChange: false
                        });
                        syncRowDirtyState(roomRow);
                        syncRowsDirtyState(getActiveMergeGroupRows(roomRow));
                    }
                    refreshRoomOptions();
                }

                if (e.target.matches('[data-field="subject_lesson_id"]')) {
                    var lessonRow = e.target.closest('tr');
                    if (lessonRow) {
                        syncActiveMergeGroupField(lessonRow, '[data-field="subject_lesson_id"]', e.target.value, {
                            dispatchChange: false
                        });
                        lessonRow.setAttribute('data-support-requestable', e.target.value ? '1' : '0');
                        syncRowDirtyState(lessonRow);
                        syncRowsDirtyState(getActiveMergeGroupRows(lessonRow));
                    }
                }
            });

            document.addEventListener('input', function(e) {
                if (e.target.matches('[data-field="content"]')) {
                    var contentRow = e.target.closest('tr');
                    if (contentRow) {
                        syncActiveMergeGroupField(contentRow, '[data-field="content"]', e.target.value);
                        syncRowDirtyState(contentRow);
                        syncRowsDirtyState(getActiveMergeGroupRows(contentRow));
                    }
                }

                if (e.target.matches('[data-field="note"]')) {
                    var noteRow = e.target.closest('tr');
                    if (noteRow) {
                        syncActiveMergeGroupField(noteRow, '[data-field="note"]', e.target.value);
                        syncRowDirtyState(noteRow);
                        syncRowsDirtyState(getActiveMergeGroupRows(noteRow));
                    }
                }
            });

            /* ══════════ SELECT ALL (global) ══════════ */
            selectAll.addEventListener('change', function() {
                var checked = this.checked;
                allRows.forEach(function(row) {
                    if (row.style.display !== 'none') {
                        var cb = row.querySelector('.slot-check');
                        if (cb && !cb.disabled) {
                            cb.checked = checked;
                            row.classList.toggle('checked-row', checked);
                        }
                    }
                });
                updateSupportRequestSelectionInfo();
            });

            /* ══════════ SELECT ALL PER DATE ══════════ */
            document.querySelectorAll('.select-all-date').forEach(function(masterCb) {
                masterCb.addEventListener('change', function() {
                    var checked = this.checked;
                    var table = this.closest('table');
                    table.querySelectorAll('.slot-check').forEach(function(cb) {
                        if (!cb.disabled && cb.closest('tr').style.display !== 'none') {
                            cb.checked = checked;
                            cb.closest('tr').classList.toggle('checked-row', checked);
                        }
                    });
                    updateSupportRequestSelectionInfo();
                });
            });

            /* ══════════ BULK ASSIGN ══════════ */
            bulkApply.addEventListener('click', function() {
                var teacherId = bulkTeacher.value;
                if (!teacherId) {
                    alert('Vui lòng chọn giảng viên trước!');
                    return;
                }

                var usedMap = buildUsedTeacherMap();
                var assigned = 0;
                var skipped = 0;

                getBulkSelectedRows().forEach(function(row) {
                    var sel = getTeacherSelect(row);
                    if (!sel) {
                        skipped++;
                        return;
                    }

                    if (!canAssignTeacherToRow(row, teacherId, usedMap)) {
                        skipped++;
                        return;
                    }

                    setSelectValue(sel, teacherId);
                    syncRowDirtyState(row);
                    var key = getSlotKey(row);
                    if (!usedMap[key]) {
                        usedMap[key] = {};
                    }
                    usedMap[key][String(teacherId)] = true;
                    markRowAssignedState(row);
                    assigned++;
                });

                refreshTeacherOptions();

                if (assigned > 0) {
                    selectAll.checked = false;
                    document.querySelectorAll('.slot-check, .select-all-date').forEach(function(c) {
                        c.checked = false;
                    });
                    allRows.forEach(function(r) {
                        r.classList.remove('checked-row');
                    });

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

            bulkRoomApply.addEventListener('click', function() {
                var roomId = bulkRoom ? bulkRoom.value : '';
                if (!roomId) {
                    alert('Vui lòng chọn phòng trước!');
                    return;
                }

                var usedMap = buildUsedRoomMap();
                var assigned = 0;
                var skipped = 0;

                getBulkSelectedRows().forEach(function(row) {
                    var sel = getRoomSelect(row);
                    if (!sel) {
                        skipped++;
                        return;
                    }

                    if (!canAssignRoomToRow(row, roomId, usedMap)) {
                        skipped++;
                        return;
                    }

                    setSelectValue(sel, roomId);
                    syncRowDirtyState(row);
                    registerRoomUsage(usedMap, row, roomId);
                    assigned++;
                });

                refreshRoomOptions();

                if (assigned > 0) {
                    selectAll.checked = false;
                    document.querySelectorAll('.slot-check, .select-all-date').forEach(function(c) {
                        c.checked = false;
                    });
                    allRows.forEach(function(r) {
                        r.classList.remove('checked-row');
                    });

                    var roomMessage = 'Đã gán phòng cho ' + assigned + ' tiết.';
                    if (skipped > 0) {
                        roomMessage += ' Bỏ qua ' + skipped + ' tiết do trùng ngày-tiết.';
                    }
                    showToast(roomMessage);
                } else if (skipped > 0) {
                    alert('Không thể gán vì các tiết đã có phòng trùng trong cùng ngày-tiết.');
                } else {
                    alert('Chưa chọn tiết nào! Hãy tick checkbox ở các dòng muốn gán.');
                }
            });

            /* ══════════ AUTO-FILL ══════════ */
            autoFillBtn.addEventListener('click', function() {
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
                                setSelectValue(sel, teacherMap[key]);
                                syncRowDirtyState(row);
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
                    var autoFillMessage = 'Auto-fill: Đã gợi ý ' + filled +
                    ' tiết dựa trên cùng môn + lớp.';
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

            function getMergeActionCell(row) {
                if (!row) {
                    return null;
                }

                var actionButton = row.querySelector('.merge-slot-btn, .split-merge-group-btn');
                if (actionButton) {
                    return actionButton.closest('td');
                }

                return row.children.length > 2 ? row.children[2] : null;
            }

            function clearMergeControls(actionCell) {
                if (!actionCell) {
                    return;
                }

                actionCell.querySelectorAll('.merge-slot-btn, .split-merge-group-btn, .badge.badge-success.ml-1')
                    .forEach(function(node) {
                        node.remove();
                    });
            }

            function renderMergedActionCell(row, groupId) {
                var actionCell = getMergeActionCell(row);
                if (!actionCell) {
                    return;
                }

                clearMergeControls(actionCell);

                var slotId = row.getAttribute('data-slot-id') || '';
                actionCell.insertAdjacentHTML('afterbegin',
                    '<span class="badge badge-success ml-1" style="font-size: 10px;">Đã ghép</span>' +
                    '<button type="button" class="btn btn-link btn-sm p-0 ml-1 split-merge-group-btn"' +
                    ' data-slot-id="' + escapeHtml(slotId) + '"' +
                    ' data-group-id="' + escapeHtml(groupId || '') + '"' +
                    ' style="font-size: 10px; vertical-align: baseline;">' +
                    'Tách ghép' +
                    '</button>'
                );
            }

            function renderUnmergedActionCell(row) {
                var actionCell = getMergeActionCell(row);
                if (!actionCell) {
                    return;
                }

                clearMergeControls(actionCell);

                var slotId = row.getAttribute('data-slot-id') || '';
                actionCell.insertAdjacentHTML('afterbegin',
                    '<button type="button" class="btn btn-link btn-sm p-0 ml-1 merge-slot-btn"' +
                    ' data-slot-id="' + escapeHtml(slotId) + '"' +
                    ' style="font-size: 10px; vertical-align: baseline;">' +
                    'Ghép lớp' +
                    '</button>'
                );
            }

            function applyMergeGroupToRows(slotIds, groupId, teacherId, roomId, subjectLessonId, subjectId, content,
                note) {
                (slotIds || []).forEach(function(slotId) {
                    var row = document.querySelector('tr[data-slot-id="' + escapeHtml(slotId) + '"]');
                    if (!row) {
                        return;
                    }

                    row.setAttribute('data-merge-group-id', groupId || '');
                    row.setAttribute('data-merge-group-status', 'active');
                    setInputValue(getFieldElement(row, 'subject_id'), subjectId);
                    setSelectValue(getTeacherSelect(row), teacherId, false);
                    setSelectValue(getRoomSelect(row), roomId, false);
                    setSelectValue(getSubjectLessonSelect(row), subjectLessonId, false);
                    setInputValue(getContentInput(row), content);
                    setInputValue(getNoteInput(row), note);
                    markRowAssignedState(row);
                    renderMergedActionCell(row, groupId);
                    syncRowDirtyState(row);
                });

                refreshTeacherOptions();
                refreshRoomOptions();
            }

            function clearMergeGroupFromRows(groupId) {
                if (!groupId) {
                    return;
                }

                document.querySelectorAll('tr[data-merge-group-id="' + escapeHtml(groupId) +
                    '"][data-merge-group-status="active"]').forEach(function(row) {
                    row.setAttribute('data-merge-group-id', '');
                    row.setAttribute('data-merge-group-status', '');
                    renderUnmergedActionCell(row);
                });

                refreshTeacherOptions();
                refreshRoomOptions();
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
                    mergeCandidatesWrap.innerHTML = isLoading ?
                        '<div class="text-muted small">Đang tải...</div>' :
                        mergeCandidatesWrap.innerHTML;
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
                candidates.forEach(function(candidate) {
                    var classLabel = candidate.class_code || candidate.class_name || '-';
                    var subjectLabel = candidate.subject_code || candidate.subject_name || '-';
                    var lessonLabel = candidate.lesson_label || '-';
                    var teacherLabel = candidate.teacher_name || '-';
                    var roomLabel = candidate.room_code || '-';
                    html += '<label class="list-group-item d-block mb-2">';
                    html += '<div class="d-flex align-items-start">';
                    html +=
                        '<div class="mr-2 pt-1"><input type="checkbox" class="merge-candidate-check" value="' +
                        escapeHtml(candidate.id) + '"></div>';
                    html += '<div class="flex-fill">';
                    html += '<div class="font-weight-bold">' + escapeHtml(classLabel) + ' - ' + escapeHtml(
                            candidate.date || '') + ' - Tiết ' + escapeHtml(candidate.period_number || '') +
                        '</div>';
                    html += '<div class="small text-muted">Môn: ' + escapeHtml(subjectLabel) +
                        ' | Bài học: ' + escapeHtml(lessonLabel) + ' | GV: ' + escapeHtml(teacherLabel) +
                        ' | Phòng: ' + escapeHtml(roomLabel) + '</div>';
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
                    Object.keys(payload.errors).forEach(function(key) {
                        var value = payload.errors[key];
                        if (Array.isArray(value)) {
                            value.forEach(function(item) {
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
                var payload = await response.json().catch(function() {
                    return {};
                });
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

                var checkedIds = Array.prototype.slice.call(document.querySelectorAll(
                    '.merge-candidate-check:checked')).map(function(cb) {
                    return parseInt(cb.value, 10);
                }).filter(function(id) {
                    return !Number.isNaN(id);
                });

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
                            subject_lesson_id: basePayload.subject_lesson_id,
                            content: basePayload.content,
                            note: basePayload.note
                        })
                    });
                    var payload = await response.json().catch(function() {
                        return {};
                    });
                    if (!response.ok || !payload.success) {
                        throw new Error(getJsonErrorMessage(payload, 'Ghép lớp thất bại.'));
                    }
                    applyMergeGroupToRows(
                        payload.slot_ids || [],
                        payload.group_id || '',
                        payload.teacher_id,
                        payload.room_id,
                        payload.subject_lesson_id,
                        basePayload.subject_id,
                        basePayload.content,
                        basePayload.note
                    );
                    showToast(payload.message || 'Đã ghép lớp thành công.');
                    hideMergeModal();
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
                    var response = await fetch(buildMergeUrl(splitMergeUrlTemplate, monthlyScheduleId,
                    groupId), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    var payload = await response.json().catch(function() {
                        return {};
                    });
                    if (!response.ok || !payload.success) {
                        throw new Error(getJsonErrorMessage(payload, 'Tách ghép thất bại.'));
                    }
                    clearMergeGroupFromRows(groupId);
                    showToast(payload.message || 'Đã tách ghép thành công.');
                } catch (error) {
                    alert(error.message || 'Tách ghép thất bại.');
                }
            }

            document.addEventListener('click', function(e) {
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
                    var sourceMonthlyScheduleId = mergeRow ? getRowSourceMonthlyScheduleId(mergeRow) :
                        currentMonthlyScheduleId;
                    currentMergeBaseSlotId = slotId;
                    currentMergeBaseMonthlyScheduleId = sourceMonthlyScheduleId;
                    currentMergeCandidates = [];
                    setMergeModalError('');
                    if (mergeModalInfo) {
                        mergeModalInfo.textContent = '';
                    }
                    mergeCandidatesWrap.innerHTML =
                        '<div class="text-muted small">Đang tải danh sách tiết có thể ghép...</div>';
                    showMergeModal();
                    setMergeModalLoading(true, 'Đang tải...');

                    loadMergeCandidates(slotId, sourceMonthlyScheduleId).then(function(candidates) {
                        currentMergeCandidates = candidates || [];
                        renderMergeCandidates(currentMergeCandidates);
                        if (mergeModalInfo) {
                            mergeModalInfo.textContent = currentMergeCandidates.length ?
                                'Chọn 1 hoặc nhiều tiết để ghép chung với tiết gốc.' :
                                'Không có tiết phù hợp để ghép.';
                        }
                    }).catch(function(error) {
                        setMergeModalError(error.message ||
                            'Không tải được danh sách tiết có thể ghép.');
                        mergeCandidatesWrap.innerHTML =
                            '<div class="text-muted small">Không có tiết phù hợp để ghép.</div>';
                    }).finally(function() {
                        setMergeModalLoading(false);
                    });
                    return;
                }

                var splitBtn = e.target.closest('.split-merge-group-btn');
                if (splitBtn) {
                    var groupId = splitBtn.getAttribute('data-group-id');
                    var splitRow = splitBtn.closest('tr');
                    var sourceMonthlyScheduleId = splitRow ? getRowSourceMonthlyScheduleId(splitRow) :
                        currentMonthlyScheduleId;
                    splitMergeGroup(groupId, sourceMonthlyScheduleId);
                }
            });

            if (mergeModalEl) {
                mergeModalEl.addEventListener('hidden.bs.modal', function() {
                    currentMergeBaseSlotId = null;
                    currentMergeBaseMonthlyScheduleId = null;
                    currentMergeCandidates = [];
                    setMergeModalError('');
                    if (mergeModalInfo) {
                        mergeModalInfo.textContent = '';
                    }
                });
            }

            function getSupportSelectedRows() {
                return Array.prototype.slice.call(document.querySelectorAll('.slot-check:checked'))
                    .map(function(checkbox) {
                        return checkbox.closest('tr');
                    })
                    .filter(Boolean)
                    .filter(function(row) {
                        var lessonSelect = row.querySelector('[data-field="subject_lesson_id"]');
                        var teacherSelect = row.querySelector('[data-field="teacher_id"]');
                        var lessonReady = lessonSelect ? lessonSelect.value !== '' : row.getAttribute('data-support-requestable') === '1';
                        return row.getAttribute('data-slot-type') === 'subject' &&
                            row.querySelector('.slot-check') &&
                            !row.querySelector('.slot-check').disabled &&
                            lessonReady &&
                            (!teacherSelect || teacherSelect.value === '');
                    });
            }

            function getSupportSelectedSlotIds() {
                return getSupportSelectedRows()
                    .map(function(row) {
                        var slotId = row.getAttribute('data-slot-id');
                        return slotId ? parseInt(slotId, 10) : null;
                    })
                    .filter(function(slotId) {
                        return Number.isInteger(slotId) && slotId > 0;
                    });
            }

            function updateSupportRequestSelectionInfo() {
                if (!supportRequestSelectionInfo) {
                    return;
                }

                var rows = getSupportSelectedRows();
                if (!rows.length) {
                    supportRequestSelectionInfo.textContent = 'Chưa chọn tiết nào.';
                    return;
                }

                supportRequestSelectionInfo.textContent = 'Đã chọn ' + rows.length +
                    ' tiết hợp lệ để gửi đề nghị hỗ trợ.';
            }

            function setSupportRequestModalError(message) {
                if (!supportRequestModalError) {
                    return;
                }

                if (!message) {
                    supportRequestModalError.classList.add('d-none');
                    supportRequestModalError.textContent = '';
                    return;
                }

                supportRequestModalError.textContent = message;
                supportRequestModalError.classList.remove('d-none');
            }

            function showSupportRequestModal() {
                setSupportRequestModalError('');
                updateSupportRequestSelectionInfo();

                if (window.jQuery && supportRequestModalEl) {
                    window.jQuery(supportRequestModalEl).modal('show');
                    return;
                }

                if (supportRequestModalEl) {
                    supportRequestModalEl.classList.add('show');
                    supportRequestModalEl.style.display = 'block';
                }
            }

            function hideSupportRequestModal() {
                if (window.jQuery && supportRequestModalEl) {
                    window.jQuery(supportRequestModalEl).modal('hide');
                    return;
                }

                if (supportRequestModalEl) {
                    supportRequestModalEl.classList.remove('show');
                    supportRequestModalEl.style.display = 'none';
                }
            }

            function setSupportRequestListModalLoading() {
                if (!supportRequestListModalBody) {
                    return;
                }

                supportRequestListModalBody.innerHTML = '' +
                    '<div class="text-center py-5 text-muted">' +
                    '<div class="spinner-border text-info mb-3" role="status" aria-hidden="true"></div>' +
                    '<div>Đang tải danh sách...</div>' +
                    '</div>';
            }

            function refreshSupportRequestListModalListeners() {
                if (!supportRequestListModalBody) {
                    return;
                }

                var filterForm = supportRequestListModalBody.querySelector('[data-support-request-modal-filter-form]');
                if (filterForm && !filterForm._supportRequestBound) {
                    filterForm._supportRequestBound = true;
                    filterForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        loadSupportRequestListModal(filterForm.action + '?' + new URLSearchParams(new FormData(filterForm)).toString());
                    });
                }

                var paginationWrap = supportRequestListModalBody.querySelector('[data-support-request-modal-pagination]');
                if (paginationWrap && !paginationWrap._supportRequestBound) {
                    paginationWrap._supportRequestBound = true;
                    paginationWrap.addEventListener('click', function(event) {
                        var link = event.target.closest('a');
                        if (!link || !link.getAttribute('href')) {
                            return;
                        }

                        if (!link.closest('[data-support-request-modal-pagination]')) {
                            return;
                        }

                        if (link.getAttribute('target') === '_blank') {
                            return;
                        }

                        event.preventDefault();
                        loadSupportRequestListModal(link.href);
                    });
                }
            }

            async function loadSupportRequestListModal(url) {
                if (!supportRequestListModalBody) {
                    return;
                }

                var fetchUrl = url || supportRequestListModalUrl;
                setSupportRequestListModalLoading();

                try {
                    var response = await fetch(fetchUrl, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    if (!response.ok) {
                        throw new Error('Không tải được danh sách yêu cầu hỗ trợ.');
                    }

                    supportRequestListModalBody.innerHTML = await response.text();
                    refreshSupportRequestListModalListeners();
                } catch (error) {
                    supportRequestListModalBody.innerHTML = '<div class="alert alert-danger mb-0">' +
                        (error.message || 'Không tải được danh sách yêu cầu hỗ trợ.') +
                        '</div>';
                }
            }

            function showSupportRequestListModal() {
                if (window.jQuery && supportRequestListModalEl) {
                    window.jQuery(supportRequestListModalEl).modal('show');
                } else if (supportRequestListModalEl) {
                    supportRequestListModalEl.classList.add('show');
                    supportRequestListModalEl.style.display = 'block';
                }

                loadSupportRequestListModal();
            }

            function hideSupportRequestListModal() {
                if (window.jQuery && supportRequestListModalEl) {
                    window.jQuery(supportRequestListModalEl).modal('hide');
                    return;
                }

                if (supportRequestListModalEl) {
                    supportRequestListModalEl.classList.remove('show');
                    supportRequestListModalEl.style.display = 'none';
                }
            }

            async function submitSupportRequest() {
                if (supportRequestInFlight) {
                    return;
                }

                if (dirtyRows && dirtyRows.size > 0) {
                    var savedBeforeSubmit = await saveAssignmentChanges({
                        silentIfClean: true
                    });
                    if (!savedBeforeSubmit) {
                        return;
                    }
                }

                var slotIds = getSupportSelectedSlotIds();
                var departmentId = supportDepartmentSelect ? supportDepartmentSelect.value : '';
                var note = supportRequestNote ? supportRequestNote.value.trim() : '';

                if (!slotIds.length) {
                    setSupportRequestModalError('Vui lòng chọn ít nhất 1 tiết chưa có giảng viên.');
                    return;
                }

                if (!departmentId) {
                    setSupportRequestModalError('Vui lòng chọn khoa hỗ trợ.');
                    return;
                }

                supportRequestInFlight = true;
                setSupportRequestModalError('');
                if (submitSupportRequestBtn) {
                    submitSupportRequestBtn.disabled = true;
                }

                try {
                    var response = await fetch(supportRequestStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            requesting_department_id: currentDepartmentId,
                            supporting_department_id: parseInt(departmentId, 10),
                            request_note: note,
                            slot_ids: slotIds
                        })
                    });
                    var payload = await response.json().catch(function() {
                        return {};
                    });

                    if (!response.ok || !payload.success) {
                        throw new Error(getJsonErrorMessage(payload, 'Không gửi được đề nghị hỗ trợ.'));
                    }

                    hideSupportRequestModal();
                    showToast(payload.message || 'Đã gửi đề nghị hỗ trợ.');
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } catch (error) {
                    setSupportRequestModalError(error.message || 'Không gửi được đề nghị hỗ trợ.');
                } finally {
                    supportRequestInFlight = false;
                    if (submitSupportRequestBtn) {
                        submitSupportRequestBtn.disabled = false;
                    }
                }
            }

            if (openSupportRequestModalBtn) {
                openSupportRequestModalBtn.addEventListener('click', function() {
                    showSupportRequestModal();
                });
            }

            if (openSupportRequestListModalBtn) {
                openSupportRequestListModalBtn.addEventListener('click', function() {
                    showSupportRequestListModal();
                });
            }

            if (submitSupportRequestBtn) {
                submitSupportRequestBtn.addEventListener('click', function() {
                    submitSupportRequest();
                });
            }

            if (supportRequestListModalEl) {
                supportRequestListModalEl.addEventListener('hidden.bs.modal', function() {
                    hideSupportRequestListModal();
                });
            }

            function showToast(msg) {
                var toast = document.getElementById('assignToast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.id = 'assignToast';
                    toast.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;padding:12px 20px;' +
                        'background:#1cc88a;color:#fff;border-radius:6px;font-size:.9rem;' +
                        'box-shadow:0 4px 12px rgba(0,0,0,.2);transition:opacity .4s;';
                    document.body.appendChild(toast);
                }
                toast.textContent = msg;
                toast.style.opacity = '1';
                clearTimeout(toast._timer);
                toast._timer = setTimeout(function() {
                    toast.style.opacity = '0';
                }, 3000);
            }
        });
    </script>
@endsection
