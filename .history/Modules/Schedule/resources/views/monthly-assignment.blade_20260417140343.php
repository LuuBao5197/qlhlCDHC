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
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                        <div>
                            <h4 class="card-title mb-1">Phan cong giang day theo thang</h4>
                            <p class="text-muted mb-0">
                                Ke hoach: <strong>{{ $monthlySchedule->plan?->name ?? '-' }}</strong> |
                                Khoa: <strong>{{ $subjects->first()?->department?->name ?? '-' }}</strong> |
                                Thang: <strong>{{ $monthlySchedule->month }}/{{ $monthlySchedule->year }}</strong>
                            </p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="badge {{ $statusClass }} p-2">Status: {{ $monthlySchedule->status }}</span>
                        </div>
                    </div>

                    <div class="mt-3 d-flex flex-wrap">
                        <a href="{{ route('schedule.index') }}" class="btn btn-outline-light btn-sm mr-2 mb-2">Quay lai danh sach</a>

                        @if ($canSubmitToTrainingOffice)
                            <form method="POST" action="{{ route('monthly-schedule.submit-training', $monthlySchedule->id) }}" class="mr-2 mb-2">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="comment" placeholder="Ghi chu gui duyet (optional)">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">Gui PDT duyet</button>
                                    </div>
                                </div>
                            </form>
                        @endif

                        @if ($monthlySchedule->status === 'approved')
                            <span class="text-success align-self-center mb-2">Lich nay da duoc phe duyet.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    @if ($subjects->isNotEmpty())
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Danh sách môn học của khoa phụ trách</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Mã môn</th>
                                        <th>Tên môn</th>
                                        <th>Khoa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjects as $subject)
                                        <tr>
                                            <td>{{ $subject->code }}</td>
                                            <td>{{ $subject->name }}</td>
                                            <td>{{ $subject->department?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Môn học ở bước này được lấy từ lịch tổng quát và chỉ hiển thị các môn của khoa chịu trách nhiệm.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Danh sach tiet hoc can phan cong</h5>
                        <small class="text-muted">Tong: {{ $monthlySchedule->scheduleSlots->count() }} tiet</small>
                    </div>

                    @php
                        $slotsByDate = $monthlySchedule->scheduleSlots->groupBy(fn($slot) => optional($slot->date)->format('Y-m-d'));
                        $dates = $slotsByDate->keys()->sort()->values();
                        $dateChunks = $dates->chunk(3);
                        $flatIndex = 0;
                        $dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
                    @endphp

                    <form method="POST" action="{{ route('monthly-schedule.assignment.save', $monthlySchedule->id) }}">
                        @csrf

                        @if ($dateChunks->isEmpty())
                            <div class="text-center text-muted py-4">Khong co tiet hoc nao de phan cong.</div>
                        @else
                            {{-- Tab navigation --}}
                            <ul class="nav nav-tabs flex-nowrap" id="scheduleTabs" role="tablist" style="overflow-x: auto;">
                                @foreach ($dateChunks as $chunkIdx => $chunk)
                                    @php
                                        $firstDate = \Carbon\Carbon::parse($chunk->first());
                                        $lastDate = \Carbon\Carbon::parse($chunk->last());
                                        $tabLabel = $firstDate->format('d/m') . ' - ' . $lastDate->format('d/m');
                                    @endphp
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link text-nowrap {{ $chunkIdx === 0 ? 'active' : '' }}"
                                           id="tab-{{ $chunkIdx }}-tab"
                                           data-toggle="tab"
                                           href="#tab-{{ $chunkIdx }}"
                                           role="tab">
                                            {{ $tabLabel }}
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
                                                <h6 class="bg-light p-2 rounded d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <i class="fas fa-calendar-day mr-1"></i>
                                                        {{ $dateObj->format('d/m/Y') }} ({{ $dayName }})
                                                    </span>
                                                    <span class="badge badge-secondary">{{ $dateSlots->count() }} tiết</span>
                                                </h6>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm table-hover align-middle mb-0">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th style="width: 60px;">Tiết</th>
                                                                <th style="min-width: 120px;">Lớp</th>
                                                                <th style="min-width: 180px;">Giảng viên</th>
                                                                <th style="min-width: 160px;">Môn học</th>
                                                                <th style="min-width: 160px;">Bài học</th>
                                                                <th style="min-width: 130px;">Phòng</th>
                                                                <th style="min-width: 160px;">Nội dung</th>
                                                                <th style="min-width: 140px;">Ghi chú</th>
                                                                <th style="width: 110px;">Trạng thái</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($dateSlots as $slot)
                                                                @php
                                                                    $idx = $flatIndex++;
                                                                    $oldSlot = $oldSlots[$idx] ?? null;
                                                                @endphp
                                                                <tr>
                                                                    <td class="text-center font-weight-bold">
                                                                        {{ $slot->period_number }}
                                                                        @if ($slot->period_number <= 5)
                                                                            <div><small class="text-success">S</small></div>
                                                                        @else
                                                                            <div><small class="text-warning">C</small></div>
                                                                        @endif
                                                                        <input type="hidden" name="slots[{{ $idx }}][id]" value="{{ $slot->id }}">
                                                                    </td>
                                                                    <td>{{ $slot->trainingClass?->code ?? '-' }}</td>
                                                                    <td>
                                                                        <select name="slots[{{ $idx }}][teacher_id]" class="form-control form-control-sm">
                                                                            <option value="">-- Chọn GV --</option>
                                                                            @foreach ($teachers as $teacher)
                                                                                @php $selectedTeacher = $oldSlot['teacher_id'] ?? $slot->teacher_id; @endphp
                                                                                <option value="{{ $teacher->id }}" @selected((int) $selectedTeacher === (int) $teacher->id)>
                                                                                    {{ $teacher->name }}@if($teacher->employee_code) ({{ $teacher->employee_code }})@endif
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $selectedSubjectId = $oldSlot['subject_id'] ?? $slot->subject_id;
                                                                            $selectedSubject = $subjects->firstWhere('id', $selectedSubjectId);
                                                                            $subjectLabel = $selectedSubject
                                                                                ? $selectedSubject->code . ' - ' . $selectedSubject->name
                                                                                : ($slot->subject ?? '--');
                                                                        @endphp
                                                                        <input type="hidden" name="slots[{{ $idx }}][subject_id]" value="{{ $selectedSubjectId }}">
                                                                        <div>{{ $subjectLabel }}</div>
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $selectedLesson = $oldSlot['subject_lesson_id'] ?? $slot->subject_lesson_id;
                                                                            $lessonOptions = $subjectLessons->where('subject_id', $selectedSubjectId);
                                                                        @endphp
                                                                        <select name="slots[{{ $idx }}][subject_lesson_id]" class="form-control form-control-sm">
                                                                            <option value="">-- Chọn bài --</option>
                                                                            @foreach ($lessonOptions as $lesson)
                                                                                <option value="{{ $lesson->id }}" @selected((int) $selectedLesson === (int) $lesson->id)>
                                                                                    B{{ $lesson->lesson_no }}: {{ $lesson->title }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <select name="slots[{{ $idx }}][room_id]" class="form-control form-control-sm">
                                                                            <option value="">-- Phòng --</option>
                                                                            @foreach ($rooms as $room)
                                                                                @php $selectedRoom = $oldSlot['room_id'] ?? $slot->room_id; @endphp
                                                                                <option value="{{ $room->id }}" @selected((int) $selectedRoom === (int) $room->id)>{{ $room->code }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control form-control-sm"
                                                                            name="slots[{{ $idx }}][content]"
                                                                            value="{{ $oldSlot['content'] ?? $slot->content }}"
                                                                            maxlength="500" placeholder="Nội dung">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control form-control-sm"
                                                                            name="slots[{{ $idx }}][note]"
                                                                            value="{{ $oldSlot['note'] ?? $slot->note }}"
                                                                            maxlength="500" placeholder="Ghi chú">
                                                                    </td>
                                                                    <td>
                                                                        @php $selectedStatus = $oldSlot['slot_status'] ?? ($slot->slot_status ?? 'planned'); @endphp
                                                                        <select name="slots[{{ $idx }}][slot_status]" class="form-control form-control-sm">
                                                                            <option value="planned" @selected($selectedStatus === 'planned')>planned</option>
                                                                            <option value="updated" @selected($selectedStatus === 'updated')>updated</option>
                                                                            <option value="cancelled" @selected($selectedStatus === 'cancelled')>cancelled</option>
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

                        <div class="mt-3 d-flex flex-wrap">
                            <button type="submit" class="btn btn-success mr-2 mb-2">Luu phan cong</button>
                            @if ($canSubmitToTrainingOffice)
                                <small class="text-muted align-self-center mb-2">Sau khi luu, bam "Gui PDT duyet" o tren de trinh lich thang.</small>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .nav-tabs { border-bottom: 2px solid #dee2e6; }
        .nav-tabs .nav-link { padding: .5rem 1rem; font-size: .875rem; }
        .nav-tabs .nav-link.active { font-weight: 600; border-bottom: 2px solid #007bff; }
        .tab-content { background: transparent; }
    </style>
@endsection
