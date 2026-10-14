{{-- Muc hien thi 1 tiet/khoi tiet. Tham so: $item, $showClass (bool, mac dinh true), $range (chuoi "Tiết 1-3", tuy chon) --}}
@php
    $kindLabels = [
        'assigned' => 'Đã phân công',
        'unassigned' => 'Chưa phân công',
        'self_study' => 'Tự học',
        'event' => 'Sự kiện',
        'holiday' => 'Nghỉ lễ',
    ];
    $typeClasses = ['Lý thuyết' => 'cal-badge-theory', 'Thực hành' => 'cal-badge-practice'];
    $showClass = $showClass ?? true;
    $range = $range ?? null;
    $when = $when ?? '';
    // Du lieu cho modal chi tiet (JS doc tu data-cal-detail, hien thi bang textContent).
    $detail = [
        'kind' => $item['kind'],
        'kindLabel' => $kindLabels[$item['kind']] ?? '',
        'subject' => $item['subject'],
        'lesson' => $item['lesson'],
        'classes' => $item['classList'] ?? [],
        'lessonType' => $item['lessonType'],
        'group' => $item['note'],
        'teacher' => $item['teacher'],
        'room' => $item['room'],
        'when' => $when,
        'periods' => $range ?? ($item['periodLabel'] ?? ''),
    ];
@endphp
<div class="cal-item cal-clickable k-{{ $item['kind'] }}" role="button" tabindex="0"
     title="Bấm để xem chi tiết" data-cal-detail="{{ json_encode($detail, JSON_UNESCAPED_UNICODE) }}">
    @if($showClass && $item['classes'] !== '')
        <div class="cal-class">{{ $item['classes'] }}</div>
    @endif
    <div class="cal-subject">{{ $item['subject'] }}</div>
    @if($item['lesson'] !== '')
        <div class="cal-lesson">{{ $item['lesson'] }}</div>
    @endif
    @if($range)
        <span class="cal-badge cal-badge-range">{{ $range }}</span>
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
