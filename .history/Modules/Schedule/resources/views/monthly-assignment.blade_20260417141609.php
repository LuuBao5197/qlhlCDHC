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
                                <i class="fas fa-chalkboard-teacher mr-2"></i>Phân công giảng dạy tháng {{ $monthlySchedule->month }}/{{ $monthlySchedule->year }}
                            </h4>
                            <p class="mb-0" style="color: #858796;">
                                Kế hoạch: <strong class="text-dark">{{ $monthlySchedule->plan?->name ?? '-' }}</strong>
                                <span class="mx-1">|</span>
                                Khoa: <strong class="text-dark">{{ $subjects->first()?->department?->name ?? '-' }}</strong>
                            </p>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="badge {{ $statusClass }} px-3 py-2" style="font-size: .85rem;">{{ ucfirst($monthlySchedule->status) }}</span>
                        </div>
                    </div>

                    <div class="mt-3 d-flex flex-wrap">
                        <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm mr-2 mb-2">
                            <i class="fas fa-arrow-left mr-1"></i>Quay lại
                        </a>

                        @if ($canSubmitToTrainingOffice)
                            <form method="POST" action="{{ route('monthly-schedule.submit-training', $monthlySchedule->id) }}" class="mr-2 mb-2">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" name="comment" placeholder="Ghi chú gửi duyệt (optional)">
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
                        <div class="d-flex align-items-center cursor-pointer" data-toggle="collapse" data-target="#subjectListCollapse">
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
                            <span class="badge badge-light border px-2 py-1 mr-1"><span class="period-dot period-morning-dot"></span> Sáng (T1-5)</span>
                            <span class="badge badge-light border px-2 py-1 mr-1"><span class="period-dot period-afternoon-dot"></span> Chiều (T6-9)</span>
                            <span class="badge badge-primary px-2 py-1">{{ $monthlySchedule->scheduleSlots->count() }} tiết</span>
                        </div>
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
                                           id="tab-{{ $chunkIdx }}-tab"
                                           data-toggle="tab"
                                           href="#tab-{{ $chunkIdx }}"
                                           role="tab">
                                            {{ $tabLabel }}
                                            <span class="badge badge-pill {{ $chunkIdx === 0 ? 'badge-light' : 'badge-secondary' }} ml-1">{{ $chunkSlotCount }}</span>
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
                                                    <table class="table table-bordered table-sm table-hover align-middle mb-0 slot-table">
                                                        <thead>
                                                            <tr>
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
                                                                    $hasTeacher = !empty($oldSlot['teacher_id'] ?? $slot->teacher_id);
                                                                    $rowClass = $hasTeacher ? 'slot-assigned' : 'slot-unassigned';
                                                                @endphp
                                                                <tr class="{{ $rowClass }}">
                                                                    <td class="text-center">
                                                                        <span class="period-badge {{ $slot->period_number <= 5 ? 'period-morning' : 'period-afternoon' }}">
                                                                            {{ $slot->period_number }}
                                                                        </span>
                                                                        <input type="hidden" name="slots[{{ $idx }}][id]" value="{{ $slot->id }}">
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="class-pill">{{ $slot->trainingClass?->code ?? '-' }}</span>
                                                                    </td>
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
                                                                        <div class="font-weight-bold" style="font-size: .82rem;">{{ $subjectLabel }}</div>
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

                        <div class="mt-3 d-flex flex-wrap align-items-center">
                            <button type="submit" class="btn btn-success mr-2 mb-2">
                                <i class="fas fa-save mr-1"></i>Lưu phân công
                            </button>
                            @if ($canSubmitToTrainingOffice)
                                <small class="text-muted mb-2">Sau khi lưu, bấm "Gửi PDT duyệt" ở trên để trình lịch tháng.</small>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Tabs */
        .nav-tabs { border-bottom: 2px solid #4e73df; }
        .nav-tabs .nav-link {
            padding: .55rem 1rem;
            font-size: .85rem;
            color: #858796;
            border: 1px solid transparent;
            transition: all .2s;
        }
        .nav-tabs .nav-link:hover { color: #4e73df; background: #f0f3ff; }
        .nav-tabs .nav-link.active {
            font-weight: 700;
            color: #fff;
            background: #4e73df;
            border-color: #4e73df;
            border-radius: .35rem .35rem 0 0;
        }
        .tab-content { background: transparent; }

        /* Date header */
        .date-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: #fff;
            padding: .5rem .85rem;
            border-radius: .35rem;
            margin-bottom: .5rem;
            font-size: .9rem;
        }
        .date-header .badge { background: rgba(255,255,255,.2); color: #fff; }

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
        .slot-table tbody tr:hover { background: #eaecf4 !important; }

        /* Period badge (circle) */
        .period-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px; height: 32px;
            border-radius: 50%;
            font-weight: 700;
            font-size: .85rem;
        }
        .period-morning { background: #d4edda; color: #155724; }
        .period-afternoon { background: #fff3cd; color: #856404; }

        /* Legend dots */
        .period-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; }
        .period-morning-dot { background: #28a745; }
        .period-afternoon-dot { background: #ffc107; }

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
        .slot-assigned { background: #f0fff4 !important; }
        .slot-unassigned { background: #fffcf0 !important; }

        /* Form controls inside table */
        .slot-table select, .slot-table input[type="text"] {
            border: 1px solid #d1d3e2;
            border-radius: .25rem;
            font-size: .82rem;
            transition: border-color .15s;
        }
        .slot-table select:focus, .slot-table input[type="text"]:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 .15rem rgba(78,115,223,.25);
        }

        /* Card accent */
        .border-left-primary { border-left: .25rem solid #4e73df !important; }
        .border-left-info { border-left: .25rem solid #36b9cc !important; }
    </style>
@endsection
