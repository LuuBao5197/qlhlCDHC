@extends('layouts.dashboard')
@section('title')
    Lich Huấn Luyện - Kỳ Học
@endsection
@section('content')
    <style>
        .schedule-page
        {
            font-family: 'Arial', sans-serif;
        }

        .schedule-page .container {
            max-width: 1400px;
            margin: auto auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 15px;
        }

        .schedule-page .header {
            text-align: center;
        }

        .schedule-page .header h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }

        .schedule-page .header-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .schedule-page .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .schedule-page .info-label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .schedule-page .info-value {
            font-size: 20px;
            font-weight: 700;
        }

        .schedule-page .calendar-wrapper {
            overflow-x: auto;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
        }

        .schedule-page .calendar {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .schedule-page .calendar th {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
            min-width: 70px;
        }

        .schedule-page .calendar th.period {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-width: 60px;
        }

        .schedule-page .calendar td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-size: 12px;
            min-width: 70px;
            height: 40px;
            vertical-align: middle;
            position: relative;
            background: white;
        }

        .schedule-page .calendar td.period {
            background: #f0f4ff;
            font-weight: 600;
            color: #667eea;
            min-width: 60px;
        }

        .schedule-page.calendar td.slot-cell,
        .schedule-page .calendar td.empty-slot-cell {
            padding: 4px;
        }

        .subject-cell {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px;
            cursor: default;
            transition: all 0.3s ease;
            position: relative;
            min-height: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-weight: 500;
            width: 100%;
            height: 100%;
        }

        .subject-cell:hover {
            background: #e8f4f8;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }

        .subject-code {
            color: #2c3e50;
            font-weight: 700;
        }

        .subject-content {
            color: #7f8c8d;
            font-size: 11px;
            line-height: 1.35;
        }

        .empty-slot {
            min-height: 40px;
        }

        .date-header {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-number {
            font-weight: 700;
            color: #2c3e50;
            font-size: 13px;
        }

        .date-month {
            font-size: 11px;
            color: #7f8c8d;
        }

        .day-of-week {
            font-size: 11px;
            color: #667eea;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .legend {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }

        .legend h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .legend-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .legend-badge.morning {
            background: #cce5ff;
            color: #0066cc;
        }

        .legend-badge.afternoon {
            background: #ffe5cc;
            color: #cc6600;
        }

        .legend-text {
            color: #7f8c8d;
            font-size: 13px;
        }

        schedule-section {
            margin-bottom: 40px;
        }

        .section-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-radius: 6px 6px 0 0;
            margin: 0 0 -2px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .schedule-section:last-child .section-title {
            margin-top: 0;
        }

        .period.morning-period {
            background: linear-gradient(135deg, #cce5ff 0%, #e8f4ff 100%);
            color: #0066cc;
        }

        .period.afternoon-period {
            background: linear-gradient(135deg, #ffe5cc 0%, #fff5e6 100%);
            color: #cc6600;
        }

        @media print {
            body {
                background: white;
            }

            .container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
    <div class="schedule-page">
        <div class="container">
            <div class="header">
                <h1>📅 Lịch Huấn Luyện</h1>

                @if ($plan && count($dates) > 0)
                    <div class="header-info">
                        <div class="info-box">
                            <div class="info-label">Kỳ Học</div>
                            <div class="info-value">HK {{ $plan->semester }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Năm Học</div>
                            <div class="info-value">{{ $plan->year }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Lớp Học</div>
                            <div class="info-value">{{ $className }}</div>
                        </div>
                        <div class="info-box">
                            <div class="info-label">Tổng Buổi Học</div>
                            <div class="info-value">{{ count($scheduleCalendar) }}</div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($plan && count($dates) > 0)
                @php
                    $dateKeys = array_keys($dates);
                    $slotMatches = function ($firstSlot, $secondSlot) {
                        if (!$firstSlot || !$secondSlot) {
                            return false;
                        }

                        return ($firstSlot['subject'] ?? null) === ($secondSlot['subject'] ?? null)
                            && ($firstSlot['content'] ?? null) === ($secondSlot['content'] ?? null);
                    };

                    $buildMergedRows = function (array $periods) use ($dateKeys, $scheduleCalendar, $slotMatches) {
                        $periods = array_values($periods);
                        $dateCount = count($dateKeys);
                        $periodCount = count($periods);
                        $covered = [];
                        $rows = [];

                        foreach ($periods as $rowIndex => $period) {
                            $cells = [];

                            for ($columnIndex = 0; $columnIndex < $dateCount; $columnIndex++) {
                                if (!empty($covered[$rowIndex][$columnIndex])) {
                                    continue;
                                }

                                $dateKey = $dateKeys[$columnIndex];
                                $slot = $scheduleCalendar[$dateKey][$period] ?? null;

                                if (!$slot) {
                                    $cells[] = ['type' => 'empty'];
                                    continue;
                                }

                                $maxWidth = 1;

                                for ($nextColumn = $columnIndex + 1; $nextColumn < $dateCount; $nextColumn++) {
                                    if (!empty($covered[$rowIndex][$nextColumn])) {
                                        break;
                                    }

                                    $candidateSlot = $scheduleCalendar[$dateKeys[$nextColumn]][$period] ?? null;

                                    if (!$slotMatches($slot, $candidateSlot)) {
                                        break;
                                    }

                                    $maxWidth++;
                                }

                                $bestWidth = 1;
                                $bestHeight = 1;
                                $bestArea = 1;

                                for ($width = $maxWidth; $width >= 1; $width--) {
                                    $height = 1;

                                    for ($nextRow = $rowIndex + 1; $nextRow < $periodCount; $nextRow++) {
                                        $canExpand = true;

                                        for ($nextColumn = $columnIndex; $nextColumn < $columnIndex + $width; $nextColumn++) {
                                            if (!empty($covered[$nextRow][$nextColumn])) {
                                                $canExpand = false;
                                                break;
                                            }

                                            $candidateSlot = $scheduleCalendar[$dateKeys[$nextColumn]][$periods[$nextRow]] ?? null;

                                            if (!$slotMatches($slot, $candidateSlot)) {
                                                $canExpand = false;
                                                break;
                                            }
                                        }

                                        if (!$canExpand) {
                                            break;
                                        }

                                        $height++;
                                    }

                                    $area = $width * $height;

                                    if ($area > $bestArea || ($area === $bestArea && $width > $bestWidth)) {
                                        $bestWidth = $width;
                                        $bestHeight = $height;
                                        $bestArea = $area;
                                    }
                                }

                                for ($markRow = $rowIndex; $markRow < $rowIndex + $bestHeight; $markRow++) {
                                    for ($markColumn = $columnIndex; $markColumn < $columnIndex + $bestWidth; $markColumn++) {
                                        $covered[$markRow][$markColumn] = true;
                                    }
                                }

                                $cells[] = [
                                    'type' => 'slot',
                                    'slot' => $slot,
                                    'rowspan' => $bestHeight,
                                    'colspan' => $bestWidth,
                                ];
                            }

                            $rows[] = [
                                'period' => $period,
                                'cells' => $cells,
                            ];
                        }

                        return $rows;
                    };

                    $morningPeriods = [1, 2, 3, 4, 5];
                    $afternoonPeriods = [6, 7, 8, 9];
                    $morningRows = $buildMergedRows($morningPeriods);
                    $afternoonRows = $buildMergedRows($afternoonPeriods);
                @endphp
                <div class="calendar-wrapper">
                    <!-- Morning Schedule (Periods 1-5) -->
                    <div class="schedule-section">
                        <h2 class="section-title">☀️ BUỔI SÁNG (Tiết 1-5)</h2>
                        <table class="calendar">
                            <thead>
                                <tr>
                                    <th class="period">Tiết</th>
                                    @foreach ($dates as $dateStr => $date)
                                        <th>
                                            <div class="date-header">
                                                <div class="date-number">{{ $date->format('d') }}</div>
                                                <div class="date-month">Tháng {{ $date->format('m') }}</div>
                                                <div class="day-of-week">{{ $date->locale('vi')->shortDayName }}</div>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($morningRows as $row)
                                    <tr>
                                        <td class="period morning-period">{{ $row['period'] }}</td>
                                        @foreach ($row['cells'] as $cell)
                                            @if ($cell['type'] === 'slot')
                                                <td class="slot-cell" @if ($cell['rowspan'] > 1) rowspan="{{ $cell['rowspan'] }}" @endif
                                                    @if ($cell['colspan'] > 1) colspan="{{ $cell['colspan'] }}" @endif>
                                                    <div class="subject-cell">
                                                        <span class="subject-code">{{ $cell['slot']['subject'] }}</span>
                                                        {{-- @if (!empty($cell['slot']['content']))
                                                            <span class="subject-content">{{ $cell['slot']['content'] }}</span>
                                                        @endif --}}
                                                    </div>
                                                </td>
                                            @else
                                                <td class="empty-slot-cell">
                                                    <div class="empty-slot"></div>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Afternoon Schedule (Periods 6-9) -->
                    <div class="schedule-section">
                        <h2 class="section-title">🌤️ BUỔI CHIỀU (Tiết 6-9)</h2>
                        <table class="calendar">
                            <thead>
                                <tr>
                                    <th class="period">Tiết</th>
                                    @foreach ($dates as $dateStr => $date)
                                        <th>
                                            <div class="date-header">
                                                <div class="date-number">{{ $date->format('d') }}</div>
                                                <div class="date-month">Tháng {{ $date->format('m') }}</div>
                                                <div class="day-of-week">{{ $date->locale('vi')->shortDayName }}</div>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($afternoonRows as $row)
                                    <tr>
                                        <td class="period afternoon-period">{{ $row['period'] }}</td>
                                        @foreach ($row['cells'] as $cell)
                                            @if ($cell['type'] === 'slot')
                                                <td class="slot-cell" @if ($cell['rowspan'] > 1) rowspan="{{ $cell['rowspan'] }}" @endif
                                                    @if ($cell['colspan'] > 1) colspan="{{ $cell['colspan'] }}" @endif>
                                                    <div class="subject-cell">
                                                        <span class="subject-code">{{ $cell['slot']['subject'] }}</span>
                                                        {{-- @if (!empty($cell['slot']['content']))
                                                            <span class="subject-content">{{ $cell['slot']['content'] }}</span>
                                                        @endif --}}
                                                    </div>
                                                </td>
                                            @else
                                                <td class="empty-slot-cell">
                                                    <div class="empty-slot"></div>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="legend">
                    <h3>Chú Thích</h3>
                    <div class="legend-items">
                        <div class="legend-item">
                            <span class="legend-badge morning">1-5</span>
                            <span class="legend-text">Tiết buổi sáng</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-badge afternoon">6-9</span>
                            <span class="legend-text">Tiết buổi chiều</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <p>📭 Không có dữ liệu lịch huấn luyện cho kỳ học này.</p>
                    <p style="font-size: 13px; color: #bdc3c7;">Vui lòng kiểm tra lại kỳ học, năm học hoặc lớp học.</p>
                </div>
            @endif
        </div>
    </div>

@endsection
