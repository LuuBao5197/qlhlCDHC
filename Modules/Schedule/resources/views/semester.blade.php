@extends('layouts.dashboard')

@section('title', 'Lịch Huấn Luyện Học Kỳ')

@section('content')
<style>
    .schedule-wrapper { padding: 20px; background: #f1f5f9; min-height: 100vh; }
    .schedule-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; }

    .table-container {
        overflow-x: auto;
        overflow-y: hidden;
        position: relative;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        cursor: pointer;
    }

    .table-container:hover {
        box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.18);
    }

    .schedule-table { border-collapse: separate; border-spacing: 0; width: max-content; min-width: 100%; table-layout: fixed; display: inline-table; }

    .schedule-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2100;
        background: rgba(15, 23, 42, 0.72);
        backdrop-filter: blur(10px);
        overflow: auto;
    }

    .schedule-modal.show {
        display: block;
    }

    .schedule-modal-content {
        position: relative;
        width: min(98%, 1600px);
        margin: 2rem auto;
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.24);
        padding: 16px;
        min-height: 80vh;
        max-height: calc(100vh - 4rem);
    }

    .schedule-modal-close {
        position: absolute;
        top: 16px;
        right: 16px;
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background: rgba(15, 23, 42, 0.06);
        color: #0f172a;
        font-size: 1.5rem;
        cursor: pointer;
    }

    .modal-table-scroll {
        overflow-x: auto;
        overflow-y: auto;
        max-height: calc(100vh - 120px);
        padding-top: 12px;
    }

    /* Sticky Headers */
    .schedule-table thead th {
        position: sticky; top: 0; z-index: 30;
        background: #f8fafc; border-bottom: 2px solid #cbd5e1; border-right: 1px solid #e2e8f0;
        padding: 10px; width: 180px; /* Tăng nhẹ độ rộng để chữ không bị bó */
    }

    .sticky-col {
        position: sticky; left: 0; z-index: 20;
        background: #f8fafc !important; width: 100px !important;
        border-right: 2px solid #cbd5e1 !important;
        font-weight: 800; text-align: center; color: #475569;
    }

    .schedule-table thead th.sticky-col { z-index: 40; }

    .schedule-table td { border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; height: 40px; padding: 5px !important; }

    /* UI Khối môn học - CẬP NHẬT CĂN GIỮA */
    .subject-block {
        min-height: 100%; width: 100%; padding: 10px 12px; box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: center; /* Căn giữa theo chiều dọc */
        align-items: flex-start;    /* Căn giữa theo chiều ngang */
        text-align: left;     /* Căn giữa chữ bên trong */
        border-left: 5px solid #3b82f6;
        transition: all 0.2s;
        border-radius: 12px;
    }

    .morning-bg { background: #f0f9ff; border-left-color: #2563eb; }
    .afternoon-bg { background: #fffbeb; border-left-color: #d97706; }

    .subject-name {
        font-weight: 700; color: #1e3a8a; font-size: 13px;
        margin-bottom: 6px; line-height: 1.2;
        width: 100%; /* Đảm bảo text-align hoạt động */
    }

    .subject-desc { font-size: 11px; color: #64748b; line-height: 1.4; width: 100%; }

    .empty-cell { background-color: #fafafa; background-image: radial-gradient(#e2e8f0 0.5px, transparent 0.5px); background-size: 15px 15px; }

    .date-header { display: flex; flex-direction: column; align-items: center; }
    .date-num { font-size: 15px; font-weight: 800; color: #1e293b; }
    .day-txt { font-size: 11px; color: #3b82f6; text-transform: uppercase; font-weight: 700; }
</style>

<div class="schedule-wrapper">
    <div class="schedule-card">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
            <h2 style="margin: 0; font-weight: 800; color: #1e293b;">📅 LỊCH HUẤN LUYỆN HỌC KỲ </h2>
            <p style="margin: 5px 0 0; color: #64748b;">
                Học kỳ: {{ $plan->semester }} | Năm học: {{ $plan->year }}
                @if($className) | Lớp: <strong>{{ $className }}</strong> @endif
            </p>
        </div>

        <div class="table-container" onclick="openScheduleModal()">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th class="sticky-col">TIẾT</th>
                        @foreach($dates as $date)
                            @php $dw = $date->dayOfWeekIso; @endphp
                            <th>
                                <div class="date-header">
                                    <span class="day-txt">{{ $dw == 7 ? 'Chủ Nhật' : 'Thứ ' . ($dw + 1) }}</span>
                                    <span class="date-num">{{ $date->format('d/m') }}</span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($periods as $period)
                        <tr>
                            <td class="sticky-col">Tiết {{ $period }}</td>
                            @foreach($dates as $date)
                                @php
                                    $dateKey = $date->toDateString();
                                    $cell = $renderRows[$period][$dateKey] ?? ['type' => 'empty'];
                                @endphp

                                @if($cell['type'] === 'hidden') @continue @endif

                                @if($cell['type'] === 'subject')
                                    <td rowspan="{{ $cell['rowspan'] }}" colspan="{{ $cell['colspan'] }}">
                                        <div class="subject-block {{ $period <= 5 ? 'morning-bg' : 'afternoon-bg' }}">
                                            <div class="subject-name">{{ $cell['data']['subject'] }}</div>
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
    </div>
</div>

<div id="scheduleModal" class="schedule-modal" onclick="closeScheduleModal()" aria-hidden="true">
    <div class="schedule-modal-content" onclick="event.stopPropagation()">
        <button type="button" class="schedule-modal-close" aria-label="Đóng" onclick="closeScheduleModal()">&times;</button>
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
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
</script>
@endsection
