@extends('layouts.dashboard')

@section('title', 'Xem lịch ngày/tuần')

@section('content')
    @php
        $weekdayNames = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
        $kindLabels = [
            'assigned' => 'Đã phân công',
            'unassigned' => 'Chưa phân công',
            'self_study' => 'Tự học',
            'event' => 'Sự kiện',
            'holiday' => 'Nghỉ lễ',
        ];
        $typeClasses = ['Lý thuyết' => 'cal-badge-theory', 'Thực hành' => 'cal-badge-practice'];
        $baseQuery = ['mode' => $mode, 'scope' => $scope, 'id' => $scopeId];
        $today = now()->toDateString();
        $title = $mode === 'day'
            ? 'Lịch ngày ' . $period->start->format('d/m/Y')
            : 'Lịch tuần ' . $period->start->isoWeek . '/' . $period->start->isoWeekYear;
    @endphp

    <style>
        .cal-page { --cal-green: #2F6B3A; --cal-green-dark: #1F4D29; --cal-ink: #1B2A1E; --cal-line: #B8C4B3; }
        .cal-page .card { border: 1px solid var(--cal-line); box-shadow: 0 2px 8px rgba(31,77,41,.10); }
        .cal-title { color: var(--cal-green-dark); font-weight: 700; margin-bottom: 2px; }
        .cal-range { color: #44523F; margin-bottom: 16px; font-weight: 500; }

        .cal-filter { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; padding: 14px; margin-bottom: 14px;
            background: #EEF3EA; border: 1px solid var(--cal-line); border-radius: 8px; }
        .cal-field { display: flex; flex-direction: column; }
        .cal-field label { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--cal-green-dark); margin-bottom: 4px; }
        .cal-page .cal-input { height: 40px; min-width: 150px; padding: 0 10px; color: var(--cal-ink); font-weight: 500;
            background: #fff; border: 1.5px solid #7F9578; border-radius: 6px; }
        .cal-page .cal-input:focus { outline: none; border-color: var(--cal-green); box-shadow: 0 0 0 3px rgba(47,107,58,.25); }

        .cal-seg { display: inline-flex; border: 1.5px solid var(--cal-green); border-radius: 6px; overflow: hidden; height: 40px; }
        .cal-seg a { display: flex; align-items: center; padding: 0 16px; font-weight: 600; color: var(--cal-green); background: #fff; text-decoration: none; }
        .cal-seg a + a { border-left: 1.5px solid var(--cal-green); }
        .cal-seg a.active { background: var(--cal-green); color: #fff; }

        .cal-btn { display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 16px; font-weight: 600;
            border-radius: 6px; border: 1.5px solid var(--cal-green); color: var(--cal-green); background: #fff; text-decoration: none; cursor: pointer; }
        .cal-btn:hover { background: #E3EEDF; color: var(--cal-green-dark); text-decoration: none; }
        .cal-btn-primary { background: var(--cal-green); color: #fff; }
        .cal-btn-primary:hover { background: var(--cal-green-dark); color: #fff; }
        .cal-nav { display: flex; gap: 8px; margin-bottom: 14px; }

        .cal-legend { display: flex; flex-wrap: wrap; gap: 14px; margin: 0 0 12px; font-size: .85rem; color: var(--cal-ink); font-weight: 500; }
        .cal-legend span::before { content: ''; display: inline-block; width: 12px; height: 12px; margin-right: 6px; border-radius: 3px; vertical-align: -1px; background: var(--c); }

        .cal-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; min-width: 720px;
            border: 1.5px solid var(--cal-line); border-radius: 8px; overflow: hidden; }
        .cal-table th, .cal-table td { vertical-align: top; border-right: 1px solid var(--cal-line); border-bottom: 1px solid var(--cal-line); padding: 6px; }
        .cal-table th:last-child, .cal-table td:last-child { border-right: 0; }
        .cal-table thead th { background: var(--cal-green-dark); color: #fff; text-align: center; font-size: .95rem; padding: 10px 6px; }
        .cal-table thead th small { display: block; font-weight: 500; opacity: .9; }
        .cal-table thead th.cal-today { background: #E0A800; color: #1B1B1B; }
        .cal-table td.cal-today { background: #FFF8E1; }
        .cal-table td.cal-weekend { background: #F6F1EC; }
        .cal-table tr.cal-afternoon > td { border-top: 3px solid var(--cal-green); }
        .cal-table .cal-period { width: 64px; text-align: center; font-weight: 700; color: var(--cal-green-dark); background: #E3EEDF; vertical-align: middle; }
        .cal-table .cal-period small { display: block; font-weight: 600; color: #44523F; }

        .cal-item { margin-bottom: 6px; padding: 7px 9px; border-radius: 6px; border-left: 5px solid var(--k); background: var(--bg); color: var(--cal-ink); font-size: .9rem; line-height: 1.35; }
        .cal-item:last-child { margin-bottom: 0; }
        .cal-item .cal-class { font-size: .75rem; font-weight: 700; color: var(--k); text-transform: uppercase; letter-spacing: .02em; }
        .cal-item .cal-subject { font-weight: 700; font-size: .95rem; }
        .cal-item .cal-lesson { font-weight: 500; }
        .cal-item .cal-meta { color: #33402F; font-weight: 500; }
        .cal-item.k-assigned { --k: #1E7B34; --bg: #E4F5E8; }
        .cal-item.k-unassigned { --k: #7A8794; --bg: #F1F3F5; border-left-style: dashed; }
        .cal-item.k-unassigned .cal-subject { color: #3E4A56; }
        .cal-item.k-self_study { --k: #0A7C86; --bg: #E0F4F6; }
        .cal-item.k-event { --k: #C77700; --bg: #FFF0D6; }
        .cal-item.k-holiday { --k: #C62828; --bg: #FDE4E4; }

        .cal-badge { display: inline-block; padding: 1px 8px; margin: 2px 4px 2px 0; border-radius: 10px; font-size: .75rem; font-weight: 700; color: #fff; background: #5B6670; }
        .cal-badge-theory { background: #1565C0; }
        .cal-badge-practice { background: #E65100; }
        .cal-note { font-size: .8rem; color: #44523F; margin: 12px 0 0; }
    </style>

    <div class="cal-page">
        <div class="card">
            <div class="card-body">
                <h4 class="cal-title">{{ $title }}</h4>
                <p class="cal-range">{{ $period->rangeLabel() }}</p>

                <form method="GET" action="{{ route('schedule.calendar') }}" class="cal-filter" id="cal-filter-form">
                    <input type="hidden" name="mode" value="{{ $mode }}">

                    <div class="cal-field">
                        <label for="cal-date">Chọn ngày</label>
                        <input type="date" id="cal-date" name="date" class="cal-input" value="{{ $selectedDate }}" onchange="this.form.submit()">
                    </div>

                    <div class="cal-field">
                        <label>Xem theo</label>
                        <div class="cal-seg">
                            <a href="{{ route('schedule.calendar', $baseQuery + ['mode' => 'week', 'date' => $selectedDate]) }}" class="{{ $mode === 'week' ? 'active' : '' }}">Tuần</a>
                            <a href="{{ route('schedule.calendar', $baseQuery + ['mode' => 'day', 'date' => $selectedDate]) }}" class="{{ $mode === 'day' ? 'active' : '' }}">Ngày</a>
                        </div>
                    </div>

                    <div class="cal-field">
                        <label for="cal-scope">Phạm vi</label>
                        <select name="scope" id="cal-scope" class="cal-input">
                            @if($canViewSchool)
                                <option value="school" @selected($scope === 'school')>Toàn trường</option>
                            @endif
                            @if($departments->isNotEmpty())
                                <option value="department" @selected($scope === 'department')>Theo khoa</option>
                            @endif
                            @if($classes->isNotEmpty())
                                <option value="class" @selected($scope === 'class')>Theo lớp</option>
                            @endif
                            @if($canViewMine)
                                <option value="mine" @selected($scope === 'mine')>Lịch của tôi</option>
                            @endif
                        </select>
                    </div>

                    <div class="cal-field" data-scope-field="department">
                        <label for="cal-department">Khoa</label>
                        <select name="id" id="cal-department" class="cal-input" data-scope="department" @disabled($scope !== 'department')>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected($scope === 'department' && (int) $scopeId === (int) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cal-field" data-scope-field="class">
                        <label for="cal-class">Lớp</label>
                        <select name="id" id="cal-class" class="cal-input" data-scope="class" @disabled($scope !== 'class')>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected($scope === 'class' && (int) $scopeId === (int) $class->id)>{{ $class->code ?: $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="cal-btn cal-btn-primary">Xem lịch</button>
                </form>

                <div class="cal-nav">
                    <a class="cal-btn" href="{{ route('schedule.calendar', $baseQuery + ['date' => $prevDate]) }}">&laquo; {{ $mode === 'day' ? 'Ngày trước' : 'Tuần trước' }}</a>
                    <a class="cal-btn" href="{{ route('schedule.calendar', $baseQuery + ['date' => $today]) }}">Hôm nay</a>
                    <a class="cal-btn" href="{{ route('schedule.calendar', $baseQuery + ['date' => $nextDate]) }}">{{ $mode === 'day' ? 'Ngày sau' : 'Tuần sau' }} &raquo;</a>
                </div>

                <div class="cal-legend">
                    <span style="--c:#1E7B34">Đã phân công</span>
                    <span style="--c:#7A8794">Chưa phân công</span>
                    <span style="--c:#0A7C86">Tự học</span>
                    <span style="--c:#C77700">Sự kiện</span>
                    <span style="--c:#C62828">Nghỉ lễ</span>
                    <span style="--c:#1565C0">Lý thuyết</span>
                    <span style="--c:#E65100">Thực hành</span>
                </div>

                <div class="table-responsive">
                    <table class="cal-table">
                        <thead>
                            <tr>
                                <th class="cal-period">Tiết</th>
                                @foreach($calendar['days'] as $day)
                                    <th class="{{ $day['date']->toDateString() === $today ? 'cal-today' : '' }}">
                                        {{ $weekdayNames[$day['date']->dayOfWeekIso] }}
                                        <small>{{ $day['date']->format('d/m/Y') }}</small>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calendar['periods'] as $periodNumber)
                                <tr class="{{ $periodNumber === 6 ? 'cal-afternoon' : '' }}">
                                    <td class="cal-period">
                                        {{ $periodNumber }}
                                        @if($periodNumber === 1)<small>Sáng</small>@elseif($periodNumber === 6)<small>Chiều</small>@endif
                                    </td>
                                    @foreach($calendar['days'] as $day)
                                        <td class="{{ $day['date']->toDateString() === $today ? 'cal-today' : ($day['date']->isoWeekday() >= 6 ? 'cal-weekend' : '') }}">
                                            @foreach($day['cells'][$periodNumber] ?? [] as $item)
                                                <div class="cal-item k-{{ $item['kind'] }}" title="{{ $kindLabels[$item['kind']] ?? '' }}">
                                                    @if($item['classes'] !== '')
                                                        <div class="cal-class">{{ $item['classes'] }}</div>
                                                    @endif
                                                    <div class="cal-subject">{{ $item['subject'] }}</div>
                                                    @if($item['lesson'] !== '')
                                                        <div class="cal-lesson">{{ $item['lesson'] }}</div>
                                                    @endif
                                                    @if($item['lessonType'] !== '')
                                                        <span class="cal-badge {{ $typeClasses[$item['lessonType']] ?? '' }}">{{ $item['lessonType'] }}</span>
                                                    @endif
                                                    @if($item['note'] !== '')
                                                        <span class="cal-badge" style="background:#6A4C93">{{ $item['note'] }}</span>
                                                    @endif
                                                    @if($item['teacher'] !== '')
                                                        <div class="cal-meta">GV: {{ $item['teacher'] }}</div>
                                                    @endif
                                                    @if($item['room'] !== '')
                                                        <div class="cal-meta">Phòng: {{ $item['room'] }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="cal-note">
                    Chọn một ngày bất kỳ, hệ thống tự hiển thị tuần chứa ngày đó (Thứ 2 - Chủ nhật). Tiết chưa phân công chỉ hiển thị tên môn học.
                </p>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var scope = document.getElementById('cal-scope');
            if (!scope) { return; }
            var selects = document.querySelectorAll('select[data-scope]');
            function sync() {
                selects.forEach(function (select) {
                    var active = select.getAttribute('data-scope') === scope.value;
                    select.disabled = !active;
                    select.closest('.cal-field').style.display = active ? '' : 'none';
                });
            }
            scope.addEventListener('change', function () { sync(); scope.form.submit(); });
            sync();
        })();
    </script>
@endsection
