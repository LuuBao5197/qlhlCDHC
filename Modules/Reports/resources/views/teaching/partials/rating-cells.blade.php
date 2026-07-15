@foreach($ratingLabels as $level => $label)
    <td>
        {{ number_format((int) ($ratings[$level]['count'] ?? 0)) }}
        <span class="text-muted">({{ number_format((float) ($ratings[$level]['rate'] ?? 0), 2) }}%)</span>
    </td>
@endforeach
