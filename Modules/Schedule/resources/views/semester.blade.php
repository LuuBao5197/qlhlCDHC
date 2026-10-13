@extends('layouts.dashboard')

@section('title', 'Lịch học kỳ')

@section('content')
@php
    $dates = collect($dates ?? []);
    $periods = collect($periods ?? range(1, 9));
    $renderRows = $renderRows ?? [];
    $classOptions = collect($classOptions ?? []);
    $planOptions = collect($planOptions ?? []);
    $className = $className ?? null;
    $ambiguous = $ambiguous ?? false;

    $dateHeaders = collect($dates)->map(function ($date) {
        $dow = $date->dayOfWeekIso;

        return [
            'key' => $date->toDateString(),
            'label' => $date->format('d/m'),
            'day' => match ($dow) {
                1 => 'Thứ 2',
                2 => 'Thứ 3',
                3 => 'Thứ 4',
                4 => 'Thứ 5',
                5 => 'Thứ 6',
                6 => 'Thứ 7',
                default => 'Chủ nhật',
            },
        ];
    });
@endphp

<style>
    .schedule-wrapper { padding: 20px; background: #f1f5f9; min-height: 100vh; }
    .schedule-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }
    .table-container { overflow-x: auto; overflow-y: hidden; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
    .schedule-table { border-collapse: separate; border-spacing: 0; width: max-content; min-width: 100%; table-layout: fixed; }
    .schedule-table thead th { position: sticky; top: 0; z-index: 30; background: #f8fafc; border-bottom: 2px solid #cbd5e1; border-right: 1px solid #e2e8f0; padding: 10px; width: 120px; }
    .sticky-col { position: sticky; left: 0; z-index: 20; background: #f8fafc !important; width: 92px !important; border-right: 2px solid #cbd5e1 !important; font-weight: 800; text-align: center; color: #475569; }
    .schedule-table thead th.sticky-col { z-index: 40; }
    .schedule-table td { border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; height: 44px; padding: 4px !important; vertical-align: middle; }
    .empty-cell { background: #fafafa; background-image: radial-gradient(#e2e8f0 0.5px, transparent 0.5px); background-size: 15px 15px; }
    .date-header { display: flex; flex-direction: column; align-items: center; line-height: 1.1; }
    .date-num { font-size: 15px; font-weight: 800; color: #1e293b; }
    .day-txt { font-size: 11px; color: #3b82f6; text-transform: uppercase; font-weight: 700; }
    .cell-block { min-height: 100%; width: 100%; padding: 8px 10px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; border-left: 5px solid #3b82f6; border-radius: 10px; text-align: left; }
    .cell-title { font-weight: 700; color: #1e3a8a; font-size: 13px; line-height: 1.2; }
    .cell-desc { font-size: 11px; color: #64748b; line-height: 1.35; margin-top: 4px; }
    .cell-badge { display: inline-block; margin-bottom: 4px; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    .legend { display: flex; flex-wrap: wrap; gap: 8px; }
    .legend-item { display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 999px; background: #fff; }
    .legend-swatch { width: 14px; height: 14px; border-radius: 4px; display: inline-block; }
    .schedule-modal { display: none; position: fixed; inset: 0; z-index: 2100; background: rgba(15, 23, 42, 0.72); backdrop-filter: blur(10px); overflow: auto; }
    .schedule-modal.show { display: block; }
    .schedule-modal-content { position: relative; width: min(98%, 1600px); margin: 2rem auto; background: #ffffff; border-radius: 18px; box-shadow: 0 28px 80px rgba(15, 23, 42, 0.24); padding: 16px; min-height: 80vh; max-height: calc(100vh - 4rem); }
    .schedule-modal-close { position: absolute; top: 16px; right: 16px; width: 40px; height: 40px; border: none; border-radius: 50%; background: rgba(15, 23, 42, 0.06); color: #0f172a; font-size: 1.5rem; cursor: pointer; }
    .modal-table-scroll { overflow-x: auto; overflow-y: auto; max-height: calc(100vh - 230px); padding-top: 12px; }
    .schedule-page-title { flex: 1 1 520px; padding: 16px 20px; border-radius: 14px; background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #fff; box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3); }
    .schedule-page-title-sub { font-size: 0.8rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #bfdbfe; }
    .schedule-page-title-main { margin: 4px 0 0; font-size: 1.9rem; font-weight: 800; color: #fff; line-height: 1.25; }
    .schedule-page-title-main .title-sep { margin: 0 8px; opacity: 0.5; font-weight: 400; }
    .schedule-page-title .meta-chip { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.35); color: #fff; padding: 5px 14px; border-radius: 999px; font-size: 0.95rem; }
    .schedule-modal-title { padding: 8px 56px 14px 8px; border-bottom: 3px solid #2563eb; margin-bottom: 4px; }
    .schedule-modal-title-main { margin: 0 0 10px; font-size: 1.6rem; font-weight: 800; color: #1e3a8a; letter-spacing: 0.3px; }
    .schedule-modal-title-meta { display: flex; flex-wrap: wrap; gap: 8px; }
    .schedule-modal-title-meta .meta-chip { padding: 6px 14px; border-radius: 999px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e293b; font-size: 0.95rem; }
    .schedule-modal-title-meta .meta-chip.meta-class { background: #1e3a8a; border-color: #1e3a8a; color: #fff; font-size: 1.05rem; }
</style>

<div class="schedule-wrapper">
    <div class="schedule-card">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                @if ($plan)
                    @php
                        $pageClass = $classOptions->firstWhere('code', $className);
                        $pageClassLabel = $className
                            ? $className . ($pageClass?->name ? ' - ' . $pageClass->name : '')
                            : 'Tất cả các lớp';
                    @endphp
                    <div class="schedule-page-title">
                        <div class="schedule-page-title-sub">Lịch học kỳ</div>
                        <h2 class="schedule-page-title-main">
                            <i class="fas fa-graduation-cap"></i>
                            {{ $pageClassLabel }}
                            <span class="title-sep">|</span>
                            HỌC KỲ {{ $plan->semester }} &ndash; NĂM HỌC {{ $plan->year }}
                        </h2>
                        <div class="schedule-modal-title-meta mt-2">
                            @if ($plan->trainingBatch)
                                <span class="meta-chip">Khóa: <strong>{{ $plan->trainingBatch->code }}</strong></span>
                            @endif
                            @if ($dates->isNotEmpty())
                                <span class="meta-chip">Bắt đầu: <strong>{{ $dates->first()->format('d/m/Y') }}</strong></span>
                                <span class="meta-chip">Kết thúc: <strong>{{ $dates->last()->format('d/m/Y') }}</strong></span>
                            @endif
                        </div>
                    </div>
                @else
                    <h2 style="margin: 0; font-weight: 800; color: #1e293b;">Lịch học kỳ</h2>
                @endif
                @auth
                    @if($plan && (auth()->user()->isTrainingOffice() || auth()->user()->isAdmin()))
                        <a href="{{ route('schedule.semester-events.index', $plan->id) }}" class="btn btn-sm btn-outline-primary">
                            Quản lý sự kiện học kỳ
                        </a>
                    @endif
                @endauth
            </div>

            @if ($plan)
                @include('partials._admin-backfill-badge', ['adminBackfillLog' => $plan->adminBackfillLog])
            @endif

            <div class="legend mt-3">
                <span class="legend-item"><span class="legend-swatch" style="background:#ffedd5;"></span>Nghỉ lễ</span>
                <span class="legend-item"><span class="legend-swatch" style="background:#dbeafe;"></span>Ôn thi</span>
                <span class="legend-item"><span class="legend-swatch" style="background:#fee2e2;"></span>Thi</span>
                <span class="legend-item"><span class="legend-swatch" style="background:#ede9fe;"></span>Sự kiện khác</span>
            </div>

            @if ($plan)
                <form method="GET" action="{{ route('schedule.semester', ['semester' => $plan->semester, 'year' => $plan->year]) }}"
                    class="d-flex flex-wrap align-items-end gap-2 mt-3">
                    <input type="hidden" name="training_batch_id" value="{{ $plan->training_batch_id }}">
                    <div>
                        <label class="mb-1 small text-muted">Lớp</label>
                        <select name="className" class="form-control form-control-sm" style="min-width: 200px;"
                            onchange="this.form.submit()">
                            <option value="">-- Tất cả các lớp --</option>
                            @foreach ($classOptions as $classOption)
                                <option value="{{ $classOption->code }}" @selected($className === $classOption->code)>
                                    {{ $classOption->code }}@if($classOption->name) - {{ $classOption->name }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">Xem</button>
                    <a href="{{ route('schedule.semester') }}" class="btn btn-sm btn-outline-secondary">Chọn học kỳ khác</a>
                </form>
            @endif
        </div>

        @if (!$plan)
            <div class="p-4">
                @if ($planOptions->isEmpty())
                    <div class="text-muted">Chưa có kế hoạch học kỳ nào để hiển thị.</div>
                @else
                    <p class="text-muted mb-2">
                        @if ($ambiguous)
                            Học kỳ/năm học này có nhiều khóa học khác nhau, vui lòng chọn khóa cần xem:
                        @else
                            Chọn học kỳ để xem lịch tổng quát:
                        @endif
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($planOptions as $planOption)
                            <a href="{{ route('schedule.semester', ['semester' => $planOption->semester, 'year' => $planOption->year, 'training_batch_id' => $planOption->training_batch_id]) }}"
                                class="btn btn-sm btn-outline-primary">
                                HK {{ $planOption->semester }} / {{ $planOption->year }}
                                @if ($planOption->trainingBatch) - {{ $planOption->trainingBatch->code }} @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @elseif ($dates->isEmpty())
            <div class="p-4 text-muted">Chưa có dữ liệu lịch học kỳ để hiển thị.</div>
        @else
            <div class="table-container" onclick="openScheduleModal()">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th class="sticky-col">Tiết</th>
                            @foreach($dateHeaders as $header)
                                <th>
                                    <div class="date-header">
                                        <span class="day-txt">{{ $header['day'] }}</span>
                                        <span class="date-num">{{ $header['label'] }}</span>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods as $period)
                            <tr>
                                <td class="sticky-col">Tiết {{ $period }}</td>
                                @foreach($dateHeaders as $header)
                                    @php
                                        $dateKey = $header['key'];
                                        $cell = $renderRows[$period][$dateKey] ?? ['type' => 'empty'];
                                    @endphp

                                    @if($cell['type'] === 'hidden')
                                        @continue
                                    @endif

                                    @if(in_array($cell['type'], ['subject', 'event'], true))
                                        @php
                                            $data = $cell['data'] ?? [];
                                            $bg = $data['color'] ?? '#f0f9ff';
                                            $border = $data['border_color'] ?? '#2563eb';
                                            $badgeLabel = $cell['type'] === 'event'
                                                ? strtoupper((string) ($data['event_type'] ?? 'event'))
                                                : 'MON HOC';
                                        @endphp
                                        <td rowspan="{{ $cell['rowspan'] }}" colspan="{{ $cell['colspan'] }}">
                                            <div class="cell-block" style="background: {{ $bg }}; border-left-color: {{ $border }};">
                                                <div class="cell-badge" style="background: {{ $border }}; color: #fff;">{{ $badgeLabel }}</div>
                                                <div class="cell-title">{{ $data['label'] ?? 'Khong co du lieu' }}</div>
                                                @if(!empty($data['description']))
                                                    <div class="cell-desc">{{ $data['description'] }}</div>
                                                @endif
                                            </div>
                                        </td>
                                    @else
                                        <td class="empty-cell"></td>
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div id="scheduleModal" class="schedule-modal" onclick="closeScheduleModal()" aria-hidden="true">
    <div class="schedule-modal-content" onclick="event.stopPropagation()">
        <button type="button" class="schedule-modal-close" aria-label="Dong" onclick="closeScheduleModal()">&times;</button>
        @if ($plan && $dates->isNotEmpty())
            @php
                $selectedClass = $classOptions->firstWhere('code', $className);
                $modalClassLabel = $className
                    ? $className . ($selectedClass?->name ? ' - ' . $selectedClass->name : '')
                    : 'Tất cả các lớp';
            @endphp
            <div class="schedule-modal-title">
                <h3 class="schedule-modal-title-main">
                    LỊCH HỌC KỲ {{ $plan->semester }} &ndash; NĂM HỌC {{ $plan->year }}
                </h3>
                <div class="schedule-modal-title-meta">
                    <span class="meta-chip meta-class">Lớp: <strong>{{ $modalClassLabel }}</strong></span>
                    @if ($plan->trainingBatch)
                        <span class="meta-chip">Khóa: <strong>{{ $plan->trainingBatch->code }}</strong></span>
                    @endif
                    <span class="meta-chip">Học kỳ: <strong>{{ $plan->semester }}</strong></span>
                    <span class="meta-chip">Bắt đầu: <strong>{{ $dates->first()->format('d/m/Y') }}</strong></span>
                    <span class="meta-chip">Kết thúc: <strong>{{ $dates->last()->format('d/m/Y') }}</strong></span>
                </div>
            </div>
        @endif
        <div class="modal-table-scroll" id="modalTableScroll"></div>
    </div>
</div>

<script>
    function openScheduleModal() {
        var modal = document.getElementById('scheduleModal');
        var sourceTable = document.querySelector('.table-container table.schedule-table');
        var target = document.getElementById('modalTableScroll');

        if (!sourceTable || !target) {
            return;
        }

        target.innerHTML = '';
        var clone = sourceTable.cloneNode(true);
        clone.style.width = '100%';
        target.appendChild(clone);

        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeScheduleModal() {
        var modal = document.getElementById('scheduleModal');
        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
</script>
@endsection
