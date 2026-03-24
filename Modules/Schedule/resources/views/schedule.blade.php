<!DOCTYPE html>
<html>
<head>
    <title>Lịch huấn luyện</title>
    <style>
        body {
            font-family: Arial;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #f2f2f2;
        }

        .header {
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            margin-bottom: 10px;
        }

        .morning {
            background-color: #e3f2fd;
        }

        .afternoon {
            background-color: #fff3e0;
        }
    </style>
</head>
<body>

<div class="header">
    LỊCH HUẤN LUYỆN THÁNG {{ $schedule->month }}/{{ $schedule->year }} - {{ $schedule->class_name }}
</div>

@php
    $dates = $schedule->scheduleSlots
        ->sortBy('date')
        ->pluck('date')
        ->unique()
        ->values();

    $periods = range(1, 9);
    $slotGrid = [];

    foreach ($schedule->scheduleSlots as $slot) {
        $d = \Carbon\Carbon::parse($slot->date)->format('Y-m-d');
        $slotGrid[$d][$slot->period_number] = $slot;
    }

    $dateHeaders = collect($dates)->map(function($date) {
        $d = \Carbon\Carbon::parse($date);
        return [
            'key' => $d->format('Y-m-d'),
            'label' => $d->format('d/m'),
            'day' => $d->locale('vi')->shortDayName,
        ];
    });
@endphp

@php
    $morningSubjects = [];
    $afternoonSubjects = [];

    foreach ($dateHeaders as $header) {
        $d = $header['key'];
        $morningSubjects[$d] = $slotGrid[$d][1]->subject ?? null; // all 1-5 same
        $afternoonSubjects[$d] = $slotGrid[$d][6]->subject ?? null; // all 6-9 same
    }

    $renderRowWithColspan = function($subjects) use ($dateHeaders) {
        $cells = [];
        $currentSubject = null;
        $currentContent = null;
        $colspan = 0;
        $dateKeys = array_column($dateHeaders->toArray(), 'key');

        foreach ($dateKeys as $key) {
            $subject = $subjects[$key] ?? null;

            if ($subject === $currentSubject) {
                $colspan++;
            } else {
                if ($currentSubject !== null) {
                    $cells[] = [
                        'subject' => $currentSubject,
                        'colspan' => $colspan,
                        'content' => $currentContent,
                    ];
                }
                $currentSubject = $subject;
                $currentContent = $subject ? 'Học môn ' . $subject : '-';
                $colspan = 1;
            }
        }

        if ($currentSubject !== null) {
            $cells[] = [
                'subject' => $currentSubject,
                'colspan' => $colspan,
                'content' => $currentContent,
            ];
        }

        return $cells;
    };

    $morningCells = $renderRowWithColspan($morningSubjects);
    $afternoonCells = $renderRowWithColspan($afternoonSubjects);
@endphp

<div style="margin-bottom: 16px; font-weight: bold;">Buổi sáng (Tiết 1-5)</div>
<table>
    <thead>
        <tr>
            <th>Thời gian</th>
            @foreach ($dateHeaders as $header)
                <th>{{ $header['label'] }}<br>{{ $header['day'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><b>1-5</b></td>
            @foreach ($morningCells as $cell)
                <td colspan="{{ $cell['colspan'] }}" style="background:#e3f2fd;">
                    <b>{{ $cell['subject'] ?? '-' }}</b><br>
                    <small>{{ $cell['content'] ?? '-' }}</small>
                </td>
            @endforeach
        </tr>
    </tbody>
</table>

<div style="margin: 20px 0 16px; font-weight: bold;">Buổi chiều (Tiết 6-9)</div>
<table>
    <thead>
        <tr>
            <th>Thời gian</th>
            @foreach ($dateHeaders as $header)
                <th>{{ $header['label'] }}<br>{{ $header['day'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><b>6-9</b></td>
            @foreach ($afternoonCells as $cell)
                <td colspan="{{ $cell['colspan'] }}" style="background:#fff3e0;">
                    <b>{{ $cell['subject'] ?? '-' }}</b><br>
                    <small>{{ $cell['content'] ?? '-' }}</small>
                </td>
            @endforeach
        </tr>
    </tbody>
</table>

</body>
</html>
