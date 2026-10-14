@extends('layouts.dashboard')

@section('title', 'Xem lịch ngày/tuần')

@section('content')
    @php
        $weekdayNames = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
        $baseQuery = ['mode' => $mode, 'scope' => $scope, 'id' => $scopeId, 'group' => $group];
        $sessionLabels = ['morning' => 'Sáng', 'afternoon' => 'Chiều'];
        $whenLabel = fn (array $day) => $weekdayNames[$day['date']->dayOfWeekIso] . ', ' . $day['date']->format('d/m/Y');
        $rangeLabel = fn (array $block) => $block['from'] === $block['to']
            ? 'Tiết ' . $block['from']
            : 'Tiết ' . $block['from'] . '-' . $block['to'];
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

        /* Khung cuon rieng: tieu de ngay va cot dau (Tiet/Buoi/Lop) dinh khi cuon. */
        .cal-scroll { overflow: auto; max-height: calc(100vh - 140px); border: 1.5px solid var(--cal-line); border-radius: 8px; }
        .cal-table { width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; min-width: 720px; }
        .cal-table th, .cal-table td { vertical-align: top; border-right: 1px solid var(--cal-line); border-bottom: 1px solid var(--cal-line); padding: 6px; }
        .cal-table th:last-child, .cal-table td:last-child { border-right: 0; }
        .cal-table thead th { position: sticky; top: 0; z-index: 3; background: var(--cal-green-dark); color: #fff; text-align: center; font-size: .95rem; padding: 10px 6px; }
        .cal-table thead th a { color: inherit; text-decoration: none; display: block; }
        .cal-table thead th a:hover { text-decoration: underline; }
        .cal-table thead th small { display: block; font-weight: 500; opacity: .9; }
        .cal-table thead th.cal-today { background: #E0A800; color: #1B1B1B; }
        .cal-table td.cal-today { background: #FFF8E1; }
        .cal-table td.cal-weekend { background: #F6F1EC; }
        .cal-table tr.cal-afternoon > td { border-top: 3px solid var(--cal-green); }
        .cal-table .cal-period { width: 64px; text-align: center; font-weight: 700; color: var(--cal-green-dark); background: #E3EEDF; vertical-align: middle; }
        .cal-table .cal-period small { display: block; font-weight: 600; color: #44523F; }
        .cal-table tbody .cal-period, .cal-table .cal-rowhead { position: sticky; left: 0; z-index: 2; }
        .cal-table thead th.cal-period { z-index: 4; left: 0; background: var(--cal-green-dark); color: #fff; }
        .cal-table .cal-rowhead { width: 110px; background: #E3EEDF; color: var(--cal-green-dark); font-weight: 700; vertical-align: middle; text-align: center; word-break: break-word; }
        .cal-table.cal-matrix .cal-rowhead { width: 120px; }
        .cal-session { margin-bottom: 6px; }
        .cal-session:last-child { margin-bottom: 0; }
        .cal-session-label { display: block; margin: 0 0 3px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--cal-green-dark); }
        .cal-clickable { cursor: pointer; transition: box-shadow .12s, transform .12s; }
        .cal-clickable:hover, .cal-clickable:focus { outline: none; box-shadow: 0 0 0 2px var(--k), 0 3px 8px rgba(0,0,0,.18); transform: translateY(-1px); }

        /* Theme goc dat .modal-content nen den (#000); ep nen sang cho modal lich de chu doc ro. */
        .cal-modal .modal-content { border: 0; border-radius: 10px; overflow: hidden; color: #1B2A1E; background-color: #fff !important; box-shadow: 0 12px 40px rgba(0,0,0,.35); }
        .cal-modal .modal-header { border: 0; padding: 16px 20px; background: var(--k, #1F4D29); align-items: center; }
        .cal-modal .modal-header .close { color: #fff !important; opacity: 1 !important; text-shadow: none; font-size: 1.9rem; font-weight: 400; }
        .cal-modal .modal-header .close:hover { color: #fff !important; background: rgba(255,255,255,.22); border-radius: 50%; }
        /* Chu trang sang, dam, co bong nhe de noi ro tren nen mau (khong bi theme ghi de). */
        .cal-modal .modal-header .cal-modal-kind,
        .cal-modal .modal-header .modal-title { color: #fff !important; opacity: 1 !important; text-shadow: 0 1px 2px rgba(0,0,0,.35); }
        .cal-modal .cal-modal-kind { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
        .cal-modal .modal-title { font-weight: 800; font-size: 1.25rem; line-height: 1.3; }
        .cal-modal .modal-body { padding: 0; background-color: #fff !important; }
        .cal-modal dl { margin: 0; }
        .cal-modal .cal-row { display: flex; gap: 12px; padding: 12px 20px; border-bottom: 1px solid #D5E0D0; background: #fff; }
        .cal-modal .cal-row:nth-child(even) { background: #F4F8F1; }
        .cal-modal .cal-row:last-child { border-bottom: 0; }
        .cal-modal dt { flex: 0 0 110px; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #2F4A2C !important; margin: 0; padding-top: 3px; }
        .cal-modal dd { flex: 1; margin: 0; font-size: 1rem; font-weight: 700; color: #111C13 !important; word-break: break-word; }
        .cal-modal .cal-chip { display: inline-block; margin: 0 6px 4px 0; padding: 2px 10px; border-radius: 12px; background: #E3EEDF; color: #1F4D29; font-weight: 700; font-size: .85rem; }
        /* Nen header dam hon cac mau the de chu trang du tuong phan (>= 5:1). */
        .cal-modal[data-kind="assigned"] { --k: #17622A; }
        .cal-modal[data-kind="unassigned"] { --k: #4A5560; }
        .cal-modal[data-kind="self_study"] { --k: #08626B; }
        .cal-modal[data-kind="event"] { --k: #A35F00; }
        .cal-modal[data-kind="holiday"] { --k: #AD1F1F; }
        .cal-empty { padding: 28px; text-align: center; color: #44523F; font-weight: 500; }

        .cal-item { margin-bottom: 6px; padding: 7px 9px; border-radius: 6px; border-left: 5px solid var(--k); background: var(--bg); color: var(--cal-ink); font-size: .9rem; line-height: 1.35; }
        .cal-item:last-child { margin-bottom: 0; }
        /* Xem theo ngay: cac muc trong cung tiet xep luoi nhieu cot (4-5 cot tren man hinh rong) de bot cuon. */
        .cal-cell-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 6px; align-items: start; }
        .cal-cell-grid .cal-item { margin-bottom: 0; height: 100%; }
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
        .cal-badge-range { background: #1F4D29; }
        .cal-note { font-size: .8rem; color: #44523F; margin: 12px 0 0; }
    </style>

    <div class="cal-page">
        <div class="card">
            <div class="card-body">
                <h4 class="cal-title">{{ $title }}</h4>
                <p class="cal-range">{{ $period->rangeLabel() }}</p>

                <form method="GET" action="{{ route('schedule.calendar') }}" class="cal-filter" id="cal-filter-form">
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    <input type="hidden" name="group" value="{{ $group }}">

                    <div class="cal-field">
                        <label for="cal-date">Chọn ngày</label>
                        <input type="date" id="cal-date" name="date" class="cal-input" value="{{ $selectedDate }}" onchange="this.form.submit()">
                    </div>

                    <div class="cal-field">
                        <label>Xem theo</label>
                        <div class="cal-seg">
                            <a href="{{ route('schedule.calendar', array_merge($baseQuery, ['mode' => 'week', 'date' => $selectedDate])) }}" class="{{ $mode === 'week' ? 'active' : '' }}">Tuần</a>
                            <a href="{{ route('schedule.calendar', array_merge($baseQuery, ['mode' => 'day', 'date' => $selectedDate])) }}" class="{{ $mode === 'day' ? 'active' : '' }}">Ngày</a>
                        </div>
                    </div>

                    @if($canGroup)
                        <div class="cal-field">
                            <label>Nhóm theo</label>
                            <div class="cal-seg">
                                <a href="{{ route('schedule.calendar', array_merge($baseQuery, ['group' => 'period', 'date' => $selectedDate])) }}" class="{{ $group === 'period' ? 'active' : '' }}">Tiết</a>
                                <a href="{{ route('schedule.calendar', array_merge($baseQuery, ['group' => 'class', 'date' => $selectedDate])) }}" class="{{ $group === 'class' ? 'active' : '' }}">Lớp</a>
                            </div>
                        </div>
                    @endif

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

                @php
                    $dayCellClass = fn ($day) => $day['date']->toDateString() === $today
                        ? 'cal-today'
                        : ($day['date']->isoWeekday() >= 6 ? 'cal-weekend' : '');
                @endphp

                @if(! $calendar['hasItems'])
                    <div class="cal-scroll"><div class="cal-empty">Không có tiết nào trong khoảng thời gian này.</div></div>
                @else
                    <div class="cal-scroll">
                        <table class="cal-table {{ $mode === 'week' && $group === 'class' ? 'cal-matrix' : '' }}">
                            <thead>
                                <tr>
                                    <th class="cal-period">{{ $mode === 'day' ? 'Tiết' : ($group === 'class' ? 'Lớp' : 'Buổi') }}</th>
                                    @foreach($calendar['days'] as $day)
                                        <th class="{{ $day['date']->toDateString() === $today ? 'cal-today' : '' }}">
                                            @if($mode === 'week')
                                                <a href="{{ route('schedule.calendar', array_merge($baseQuery, ['mode' => 'day', 'date' => $day['date']->toDateString()])) }}" title="Xem chi tiết ngày này">
                                                    {{ $weekdayNames[$day['date']->dayOfWeekIso] }}
                                                    <small>{{ $day['date']->format('d/m/Y') }}</small>
                                                </a>
                                            @else
                                                {{ $weekdayNames[$day['date']->dayOfWeekIso] }}
                                                <small>{{ $day['date']->format('d/m/Y') }}</small>
                                            @endif
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @if($mode === 'day')
                                    {{-- Xem theo ngay: moi tiet co du lieu la mot hang, cac muc xep luoi nhieu cot. --}}
                                    @php $firstAfternoon = collect($calendar['periods'])->first(fn ($p) => $p > 5); @endphp
                                    @foreach($calendar['periods'] as $periodNumber)
                                        <tr class="{{ $periodNumber === $firstAfternoon ? 'cal-afternoon' : '' }}">
                                            <td class="cal-period">
                                                {{ $periodNumber }}
                                                @if($loop->first && $periodNumber <= 5)<small>Sáng</small>@elseif($periodNumber === $firstAfternoon)<small>Chiều</small>@endif
                                            </td>
                                            @foreach($calendar['days'] as $day)
                                                <td class="{{ $dayCellClass($day) }}">
                                                    <div class="cal-cell cal-cell-grid">
                                                        @foreach($day['cells'][$periodNumber] ?? [] as $item)
                                                            @include('schedule::partials._calendar-card', ['item' => $item + ['periodLabel' => 'Tiết ' . $periodNumber], 'when' => $whenLabel($day)])
                                                        @endforeach
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @elseif($group === 'class')
                                    {{-- Xem tuan nhom theo lop: hang = lop, cot = ngay, moi o chia buoi sang/chieu. --}}
                                    @foreach($calendar['matrix'] as $row)
                                        <tr>
                                            <td class="cal-rowhead">{{ $row['label'] }}</td>
                                            @foreach($calendar['days'] as $day)
                                                @php $dayKey = $day['date']->toDateString(); @endphp
                                                <td class="{{ $dayCellClass($day) }}">
                                                    @foreach($sessionLabels as $sessionKey => $sessionLabel)
                                                        @if(! empty($row['cells'][$dayKey][$sessionKey]))
                                                            <div class="cal-session">
                                                                <span class="cal-session-label">{{ $sessionLabel }}</span>
                                                                @foreach($row['cells'][$dayKey][$sessionKey] as $block)
                                                                    @include('schedule::partials._calendar-card', ['item' => $block, 'showClass' => false, 'range' => $rangeLabel($block), 'when' => $whenLabel($day)])
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @else
                                    {{-- Xem tuan nhom theo tiet: hang = buoi (sang/chieu), cac tiet lien tiep giong nhau da duoc gop. --}}
                                    @foreach($sessionLabels as $sessionKey => $sessionLabel)
                                        @php
                                            $sessionHasItems = collect($calendar['days'])->contains(
                                                fn ($day) => collect($day['blocks'])->contains('session', $sessionKey)
                                            );
                                        @endphp
                                        @if($sessionHasItems)
                                            <tr class="{{ $sessionKey === 'afternoon' ? 'cal-afternoon' : '' }}">
                                                <td class="cal-period">{{ $sessionLabel }}</td>
                                                @foreach($calendar['days'] as $day)
                                                    <td class="{{ $dayCellClass($day) }}">
                                                        @foreach(collect($day['blocks'])->where('session', $sessionKey) as $block)
                                                            @include('schedule::partials._calendar-card', ['item' => $block, 'range' => $rangeLabel($block), 'when' => $whenLabel($day)])
                                                        @endforeach
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                @endif

                <p class="cal-note">
                    Chọn một ngày bất kỳ, hệ thống tự hiển thị tuần chứa ngày đó (Thứ 2 - Chủ nhật). Tiết chưa phân công chỉ hiển thị tên môn học.
                </p>
            </div>
        </div>
    </div>

    <div class="modal fade cal-modal" id="cal-modal" tabindex="-1" role="dialog" aria-labelledby="cal-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="cal-modal-kind" id="cal-modal-kind"></div>
                        <h5 class="modal-title" id="cal-modal-title"></h5>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body"><dl id="cal-modal-body"></dl></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            // Modal chi tiet tiet hoc: noi dung doc tu data-cal-detail cua the, gan bang textContent (khong chen HTML).
            var modal = document.getElementById('cal-modal');
            if (!modal || !window.jQuery) { return; }
            document.body.appendChild(modal); // tranh bi che boi backdrop khi nam trong khung noi dung
            var $modal = window.jQuery(modal);
            var body = document.getElementById('cal-modal-body');

            function addRow(label, value) {
                if (!value || (Array.isArray(value) && !value.length)) { return; }
                var row = document.createElement('div');
                row.className = 'cal-row';
                var dt = document.createElement('dt');
                dt.textContent = label;
                var dd = document.createElement('dd');
                if (Array.isArray(value)) {
                    value.forEach(function (text) {
                        var chip = document.createElement('span');
                        chip.className = 'cal-chip';
                        chip.textContent = text;
                        dd.appendChild(chip);
                    });
                } else {
                    dd.textContent = value;
                }
                row.appendChild(dt);
                row.appendChild(dd);
                body.appendChild(row);
            }

            function open(card) {
                var d;
                try { d = JSON.parse(card.getAttribute('data-cal-detail')); } catch (e) { return; }
                modal.setAttribute('data-kind', d.kind);
                document.getElementById('cal-modal-kind').textContent = d.kindLabel;
                document.getElementById('cal-modal-title').textContent = d.subject;
                body.textContent = '';
                addRow('Thời gian', [d.when, d.periods].filter(Boolean).join(' · '));
                addRow(d.classes && d.classes.length > 1 ? 'Các lớp' : 'Lớp', d.classes);
                addRow('Bài học', d.lesson);
                addRow('Loại tiết', d.lessonType);
                addRow('Tổ', d.group);
                addRow('Giáo viên', d.teacher);
                addRow('Phòng', d.room);
                $modal.modal('show');
            }

            document.addEventListener('click', function (event) {
                var card = event.target.closest('[data-cal-detail]');
                if (card) { open(card); }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') { return; }
                var card = event.target.closest && event.target.closest('[data-cal-detail]');
                if (card) { event.preventDefault(); open(card); }
            });
        })();

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
    @endpush
@endsection
