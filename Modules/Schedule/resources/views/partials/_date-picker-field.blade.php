@php
    $label = $label ?? '';
    $name = $name ?? '';
    $field = $field ?? $name;
    $value = $value ?? '';
    $placeholder = $placeholder ?? 'DD/MM/YYYY';
    $buttonLabel = $buttonLabel ?? 'Lich';
    $help = $help ?? '';
    $wrapperClass = $wrapperClass ?? '';
    $inputClass = $inputClass ?? '';
    $form = $form ?? '';
    $displayId = $displayId ?? '';
    $nativeId = $nativeId ?? '';
    $required = (bool)($required ?? false);
    $readonly = (bool)($readonly ?? false);
    $min = $min ?? '';
    $max = $max ?? '';
    $minSource = $minSource ?? '';
    $maxSource = $maxSource ?? '';
    $nativeValue = $nativeValue ?? $value;
    $displayValue = '';

    if ($value !== '' && $value !== null) {
        try {
            $displayValue = \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            $displayValue = (string) $value;
        }
    }
@endphp

<div class="schedule-date-field js-schedule-date-field {{ $wrapperClass }}"
    data-field="{{ $field }}"
    @if ($min !== '') data-min="{{ $min }}" @endif
    @if ($max !== '') data-max="{{ $max }}" @endif
    @if ($minSource !== '') data-min-source="{{ $minSource }}" @endif
    @if ($maxSource !== '') data-max-source="{{ $maxSource }}" @endif>
    @if ($label !== '')
        <label class="form-label">{{ $label }}</label>
    @endif
    <div class="schedule-date-input-group input-group">
        <input type="text"
            class="form-control schedule-date-display js-schedule-date-display {{ $inputClass }}"
            data-date-display="{{ $field }}"
            @if ($displayId !== '') id="{{ $displayId }}" @endif
            placeholder="{{ $placeholder }}"
            value="{{ $displayValue }}"
            @if ($readonly) readonly @endif
            @if ($form !== '') form="{{ $form }}" @endif
            @if ($required) required @endif>
        <input type="hidden"
            name="{{ $name }}"
            data-date-native="{{ $field }}"
            @if ($nativeId !== '') id="{{ $nativeId }}" @endif
            value="{{ $nativeValue }}"
            @if ($form !== '') form="{{ $form }}" @endif>
        <button type="button" class="btn btn-outline-secondary schedule-date-toggle"
            data-date-picker="{{ $field }}">{{ $buttonLabel }}</button>
    </div>
    @if ($help !== '')
        <small class="text-muted">{{ $help }}</small>
    @endif
</div>
