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
                                Khoa: <strong>{{ $monthlySchedule->department?->name ?? $monthlySchedule->trainingClass?->department?->name ?? '-' }}</strong> |
                                Lop: <strong>{{ $monthlySchedule->class_name }}</strong> |
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

                    <form method="POST" action="{{ route('monthly-schedule.assignment.save', $monthlySchedule->id) }}">
                        @csrf

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover align-middle assignment-table">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">Ngay</th>
                                        <th style="width: 70px;">Tiet</th>
                                        <th style="min-width: 160px;">Lop</th>
                                        <th style="min-width: 200px;">Giang vien</th>
                                        <th style="min-width: 180px;">Mon hoc</th>
                                        <th style="min-width: 180px;">Bai hoc</th>
                                        <th style="min-width: 160px;">Phong</th>
                                        <th style="min-width: 180px;">Noi dung</th>
                                        <th style="min-width: 160px;">Ghi chu</th>
                                        <th style="width: 130px;">Trang thai tiet</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($monthlySchedule->scheduleSlots as $index => $slot)
                                        @php
                                            $oldSlot = $oldSlots[$index] ?? null;
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ optional($slot->date)->format('d/m/Y') ?? '-' }}
                                                <input type="hidden" name="slots[{{ $index }}][id]" value="{{ $slot->id }}">
                                            </td>
                                            <td>{{ $slot->period_number }}</td>
                                            <td>{{ $monthlySchedule->class_name }}</td>
                                            <td>
                                                <select name="slots[{{ $index }}][teacher_id]" class="form-control form-control-sm">
                                                    <option value="">-- Chon GV --</option>
                                                    @foreach ($teachers as $teacher)
                                                        @php
                                                            $selectedTeacher = $oldSlot['teacher_id'] ?? $slot->teacher_id;
                                                        @endphp
                                                        <option value="{{ $teacher->id }}" @selected((int) $selectedTeacher === (int) $teacher->id)>
                                                            {{ $teacher->name }}
                                                            @if ($teacher->employee_code)
                                                                ({{ $teacher->employee_code }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                @php
                                                    $selectedSubjectId = $oldSlot['subject_id'] ?? $slot->subject_id;
                                                    $selectedSubject = $subjects->firstWhere('id', $selectedSubjectId);
                                                    $subjectLabel = $selectedSubject
                                                        ? ($selectedSubject->code . ' - ' . $selectedSubject->name)
                                                        : ($slot->subject ?? '-- Chưa xác định môn --');
                                                @endphp

                                                <input type="hidden" name="slots[{{ $index }}][subject_id]" value="{{ $selectedSubjectId }}">

                                                <div>{{ $subjectLabel }}</div>
                                                @if ($selectedSubject?->department)
                                                    <small class="text-muted">{{ $selectedSubject->department->name }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $selectedLesson = $oldSlot['subject_lesson_id'] ?? $slot->subject_lesson_id;
                                                    $lessonOptions = $subjectLessons->where('subject_id', $selectedSubjectId);
                                                @endphp
                                                <select name="slots[{{ $index }}][subject_lesson_id]" class="form-control form-control-sm">
                                                    <option value="">-- Chon bai --</option>
                                                    @foreach ($lessonOptions as $lesson)
                                                        <option value="{{ $lesson->id }}" @selected((int) $selectedLesson === (int) $lesson->id)>
                                                            S{{ $lesson->subject_id }}-B{{ $lesson->lesson_no }}: {{ $lesson->title }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="slots[{{ $index }}][room_id]" class="form-control form-control-sm">
                                                    <option value="">-- Chon phong --</option>
                                                    @foreach ($rooms as $room)
                                                        @php
                                                            $selectedRoom = $oldSlot['room_id'] ?? $slot->room_id;
                                                        @endphp
                                                        <option value="{{ $room->id }}" @selected((int) $selectedRoom === (int) $room->id)>
                                                            {{ $room->code }} - {{ $room->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    name="slots[{{ $index }}][content]"
                                                    value="{{ $oldSlot['content'] ?? $slot->content }}"
                                                    maxlength="500"
                                                    placeholder="Noi dung tiet hoc"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    class="form-control form-control-sm"
                                                    name="slots[{{ $index }}][note]"
                                                    value="{{ $oldSlot['note'] ?? $slot->note }}"
                                                    maxlength="500"
                                                    placeholder="Ghi chu"
                                                >
                                            </td>
                                            <td>
                                                @php
                                                    $selectedStatus = $oldSlot['slot_status'] ?? $slot->slot_status ?? 'planned';
                                                @endphp
                                                <select name="slots[{{ $index }}][slot_status]" class="form-control form-control-sm">
                                                    <option value="planned" @selected($selectedStatus === 'planned')>planned</option>
                                                    <option value="updated" @selected($selectedStatus === 'updated')>updated</option>
                                                    <option value="cancelled" @selected($selectedStatus === 'cancelled')>cancelled</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">Khong co tiet hoc nao de phan cong.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

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
        .assignment-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #1f2638;
        }
    </style>
@endsection
