<style>
    body {
        font-family: Arial;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th,
    td {
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
        $dates = $schedule->scheduleSlots->sortBy('date')->pluck('date')->unique()->values();

        $periods = range(1, 9);
        $slotGrid = [];

        foreach ($schedule->scheduleSlots as $slot) {
            $d = \Carbon\Carbon::parse($slot->date)->format('Y-m-d');
            $slotGrid[$d][$slot->period_number] = $slot;
        }

        $dateHeaders = collect($dates)->map(function ($date) {
            $d = \Carbon\Carbon::parse($date);
            return [
                'key' => $d->format('Y-m-d'),
                'label' => $d->format('d/m'),
                'day' => $d->locale('vi')->shortDayName,
            ];
        });
    @endphp

    <div style="margin-bottom: 16px; font-weight: bold;">Buổi sáng (Tiết 1-5)</div>
    <table>
        <thead>
            <tr>
                <th>Tiết</th>
                @foreach ($dateHeaders as $header)
                    <th>{{ $header['label'] }}<br>{{ $header['day'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (range(1, 5) as $period)
                <tr>
                    <td>{{ $period }}</td>
                    @foreach ($dateHeaders as $header)
                        @php
                            $slot = $slotGrid[$header['key']][$period] ?? null;
                        @endphp
                        <td>
                            @if ($slot)
                                <div><b>{{ $slot->subject }}</b></div>
                                <div style="font-size:12px; color:#555;">{{ $slot->content }}</div>
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin: 20px 0 16px; font-weight: bold;">Buổi chiều (Tiết 6-9)</div>
    <table>
        <thead>
            <tr>
                <th>Tiết</th>
                @foreach ($dateHeaders as $header)
                    <th>{{ $header['label'] }}<br>{{ $header['day'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (range(6, 9) as $period)
                <tr>
                    <td>{{ $period }}</td>
                    @foreach ($dateHeaders as $header)
                        @php
                            $slot = $slotGrid[$header['key']][$period] ?? null;
                        @endphp
                        <td>
                            @if ($slot)
                                <div><b>{{ $slot->subject }}</b></div>
                                <div style="font-size:12px; color:#555;">{{ $slot->content }}</div>
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
