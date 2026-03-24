<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Huấn Luyện - Kỳ Học</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 4px solid #667eea;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
        }

        .header-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 20px;
            font-weight: 700;
        }

        .calendar-wrapper {
            overflow-x: auto;
            border: 2px solid #ecf0f1;
            border-radius: 6px;
        }

        .calendar {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .calendar th {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px 8px;
            text-align: center;
            font-weight: 600;
            font-size: 12px;
            color: #2c3e50;
            min-width: 70px;
        }

        .calendar th.period {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-width: 60px;
        }

        .calendar td {
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

        .calendar td.period {
            background: #f0f4ff;
            font-weight: 600;
            color: #667eea;
            min-width: 60px;
        }

        .subject-cell {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .subject-cell:hover {
            background: #e8f4f8;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
            transform: scale(1.02);
        }

        .subject-code {
            color: #2c3e50;
            font-weight: 700;
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

        .schedule-section {
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
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📅 Lịch Huấn Luyện</h1>

        @if($plan && count($dates) > 0)
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

    @if($plan && count($dates) > 0)
        @php
            $periods = range(1, 9);
        @endphp

        <div class="calendar-wrapper">
            <div class="schedule-section">
                <h2 class="section-title">🗓️ Lịch Kỳ Học (Tiết 1-9)</h2>
                <table class="calendar">
                    <thead>
                        <tr>
                            <th>Tiết \ Ngày</th>
                            @foreach ($dates as $date)
                                <th>{{ $date->format('d/m') }}<br>{{ $date->locale('vi')->shortDayName }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($periods as $period)
                            <tr>
                                <td class="period">{{ $period }}</td>
                                @foreach ($dates as $date)
                                    @php
                                        $dateKey = $date->format('Y-m-d');
                                        $slot = $scheduleCalendar[$dateKey][$period] ?? null;
                                    @endphp
                                    <td>
                                        @if($slot)
                                            <strong>{{ $slot['subject'] }}</strong><br>
                                            <small>{{ $slot['content'] ?? '' }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>
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

</body>
</html>
