@extends('layouts.dashboard')

@section('title', 'Quan ly su kien hoc ky')

@section('content')
@php
    $globalEvents = $events->whereNull('class_id');
    $classEvents = $events->whereNotNull('class_id')->groupBy('class_id');
    $eventOptions = collect($eventTypes);
@endphp

<style>
    .event-page { padding: 18px; }
    .event-shell {
        border: 1px solid #dbe7f3;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        overflow: hidden;
    }
    .event-head {
        padding: 18px 22px;
        background: linear-gradient(135deg, #0f4c81, #1677b3);
        color: #fff;
    }
    .event-body { padding: 18px; }
    .event-section {
        border: 1px solid #dfe8f1;
        border-radius: 14px;
        padding: 16px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 76, 129, 0.04);
    }
    .event-section + .event-section { margin-top: 16px; }
    .event-table td, .event-table th { vertical-align: middle; }
    .event-table .form-control,
    .event-table .custom-select { min-width: 0; }
    .compact-input { min-width: 110px; }
    .compact-label { font-size: 12px; color: #5b6b7f; margin-bottom: .35rem; }
    .event-badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; display: inline-block; }
    .event-swatch { width: 14px; height: 14px; border-radius: 4px; display: inline-block; }
    .event-mini { font-size: 12px; color: #64748b; }
    .event-summary {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    .event-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,.14);
        color: #fff;
        font-size: 12px;
    }
    .event-form-grid {
        display: grid;
        grid-template-columns: 1.2fr .8fr .8fr .7fr .7fr 1fr auto;
        gap: 10px;
        align-items: end;
    }
    .event-note-grid {
        grid-template-columns: 1.6fr auto;
    }
    @media (max-width: 1200px) {
        .event-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .event-inline-form .form-control,
    .event-inline-form .custom-select {
        border-radius: 10px;
    }
    .event-period-stack {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .event-period-stack .form-control {
        min-width: 58px;
        text-align: center;
    }
    .event-period-separator {
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }
    .event-action-stack {
        display: grid;
        gap: 8px;
    }
    .event-action-stack .btn {
        width: 100%;
    }
    .event-type-cell {
        display: grid;
        gap: 8px;
        min-width: 150px;
    }
    .event-row-input {
        min-width: 120px;
    }
</style>

<div class="event-page">
    <div class="event-shell shadow-sm">
        <div class="event-head">
            <h4 class="mb-1">Quan ly su kien hoc ky</h4>
            <div>{{ $plan->name }} | Khoa dao tao: {{ $plan->trainingBatch?->code ?? '-' }}</div>
            <div class="event-summary">
                <span class="event-chip">Tong: {{ $events->count() }}</span>
                <span class="event-chip">Nghi le: {{ $globalEvents->count() }}</span>
                <span class="event-chip">Theo lop: {{ $classEvents->flatten(1)->count() }}</span>
            </div>
        </div>
        <div class="event-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Su kien chung</h5>
                    <div class="event-mini">Chi dung cho nghi le ap dung tren toan bo hoc ky.</div>
                </div>
                <a href="{{ route('schedule.semester.public', ['semester' => $plan->semester, 'year' => $plan->year, 'training_batch_id' => $plan->training_batch_id]) }}"
                    class="btn btn-sm btn-outline-secondary">
                    Xem lich hoc ky
                </a>
            </div>

            <div class="event-section mb-4">
                <form method="POST" action="{{ route('schedule.semester-events.store', $plan->id) }}" class="mb-0 event-inline-form">
                    @csrf
                    <input type="hidden" name="event_type" value="holiday">
                    <input type="hidden" name="class_id" value="">
                    <div class="event-form-grid">
                        <div>
                            <label class="compact-label">Ten nghi le</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Vi du: Tet Nguyen Dan" required>
                        </div>
                        <div>
                            <label class="compact-label">Tu ngay</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" required>
                        </div>
                        <div>
                            <label class="compact-label">Den ngay</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" required>
                        </div>
                        <div>
                            <label class="compact-label">Tu tiet</label>
                            <input type="number" min="1" max="9" name="period_from" class="form-control form-control-sm compact-input" value="1">
                        </div>
                        <div>
                            <label class="compact-label">Den tiet</label>
                            <input type="number" min="1" max="9" name="period_to" class="form-control form-control-sm compact-input" value="9">
                        </div>
                        <div>
                            <label class="compact-label">Ghi chu</label>
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Mo ta ngan">
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary btn-sm">Them</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="event-section mb-4">
                <h6 class="mb-3">Danh sach nghi le</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered event-table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 80px;">#</th>
                                <th>Ten</th>
                                <th style="width: 130px;">Tu ngay</th>
                                <th style="width: 130px;">Den ngay</th>
                                <th style="width: 100px;">Tiet</th>
                                <th>Ghi chu</th>
                                <th style="width: 140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($globalEvents as $event)
                                <tr>
                                    <td>{{ $event->id }}</td>
                                    <td>
                                        <input
                                            type="text"
                                            name="title"
                                            form="global-event-{{ $event->id }}"
                                            class="form-control form-control-sm event-row-input"
                                            value="{{ $event->title }}"
                                            required>
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            name="start_date"
                                            form="global-event-{{ $event->id }}"
                                            class="form-control form-control-sm"
                                            value="{{ optional($event->start_date)->format('Y-m-d') }}"
                                            required>
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            name="end_date"
                                            form="global-event-{{ $event->id }}"
                                            class="form-control form-control-sm"
                                            value="{{ optional($event->end_date)->format('Y-m-d') }}"
                                            required>
                                    </td>
                                    <td>
                                        <div class="event-period-stack">
                                            <input
                                                type="number"
                                                min="1"
                                                max="9"
                                                name="period_from"
                                                form="global-event-{{ $event->id }}"
                                                class="form-control form-control-sm"
                                                value="{{ $event->period_from ?? 1 }}">
                                            <span class="event-period-separator">-</span>
                                            <input
                                                type="number"
                                                min="1"
                                                max="9"
                                                name="period_to"
                                                form="global-event-{{ $event->id }}"
                                                class="form-control form-control-sm"
                                                value="{{ $event->period_to ?? 9 }}">
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="note"
                                            form="global-event-{{ $event->id }}"
                                            class="form-control form-control-sm event-row-input"
                                            value="{{ $event->note }}">
                                    </td>
                                    <td class="text-center">
                                        <form id="global-event-{{ $event->id }}" method="POST" action="{{ route('schedule.semester-events.update', [$plan->id, $event->id]) }}" class="d-none">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="event_type" value="holiday">
                                            <input type="hidden" name="class_id" value="">
                                        </form>
                                        <div class="event-action-stack">
                                            <button type="submit" form="global-event-{{ $event->id }}" class="btn btn-sm btn-outline-primary">Luu</button>
                                            <form method="POST" action="{{ route('schedule.semester-events.destroy', [$plan->id, $event->id]) }}" class="mb-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoa su kien nay?')">Xoa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Chua co su kien chung nao.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="event-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Su kien theo lop</h5>
                        <div class="event-mini">On thi, thi, va su kien dac biet cua tung lop.</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('schedule.semester-events.store', $plan->id) }}" class="mb-4 event-inline-form">
                    @csrf
                    <div class="event-form-grid">
                        <div>
                            <label class="compact-label">Loai</label>
                            <select name="event_type" class="form-control form-control-sm" required>
                                @foreach ($eventOptions->where('value', '!=', 'holiday') as $type)
                                    <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="compact-label">Lop</label>
                            <select name="class_id" class="form-control form-control-sm" required>
                                <option value="">Chon lop</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->code }} - {{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="compact-label">Ten su kien</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="On thi / Thi" required>
                        </div>
                        <div>
                            <label class="compact-label">Tu ngay</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" required>
                        </div>
                        <div>
                            <label class="compact-label">Den ngay</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" required>
                        </div>
                        <div>
                            <label class="compact-label">Tu tiet</label>
                            <input type="number" min="1" max="9" name="period_from" class="form-control form-control-sm" value="1">
                        </div>
                        <div>
                            <label class="compact-label">Den tiet</label>
                            <input type="number" min="1" max="9" name="period_to" class="form-control form-control-sm" value="9">
                        </div>
                    </div>
                    <div class="event-form-grid event-note-grid mt-3">
                        <div>
                            <label class="compact-label">Ghi chu</label>
                            <input type="text" name="note" class="form-control form-control-sm" placeholder="Mo ta ngan">
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary btn-sm">Them</button>
                        </div>
                    </div>
                </form>

                @forelse ($classes as $class)
                    @php $rows = $classEvents->get($class->id, collect()); @endphp
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>{{ $class->code }} - {{ $class->name }}</strong>
                            <span class="event-mini">{{ $rows->count() }} su kien</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered event-table mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 70px;">#</th>
                                        <th style="width: 120px;">Loai</th>
                                        <th>Ten</th>
                                        <th style="width: 120px;">Tu ngay</th>
                                        <th style="width: 120px;">Den ngay</th>
                                        <th style="width: 90px;">Tiet</th>
                                        <th>Ghi chu</th>
                                        <th style="width: 120px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $event)
                                        @php
                                            $selectedType = $eventOptions->firstWhere('value', $event->event_type);
                                        @endphp
                                        <tr>
                                            <td>{{ $event->id }}</td>
                                            <td>
                                                <div class="event-type-cell">
                                                    <span class="event-badge" style="background: {{ $selectedType['color'] ?? '#e2e8f0' }};">
                                                        {{ $selectedType['label'] ?? $event->event_type }}
                                                    </span>
                                                    <select name="event_type" form="class-event-{{ $event->id }}" class="form-control form-control-sm">
                                                        @foreach ($eventOptions->where('value', '!=', 'holiday') as $type)
                                                            <option value="{{ $type['value'] }}" @selected($event->event_type === $type['value'])>{{ $type['label'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    name="title"
                                                    form="class-event-{{ $event->id }}"
                                                    class="form-control form-control-sm event-row-input"
                                                    value="{{ $event->title }}"
                                                    required>
                                            </td>
                                            <td>
                                                <input
                                                    type="date"
                                                    name="start_date"
                                                    form="class-event-{{ $event->id }}"
                                                    class="form-control form-control-sm"
                                                    value="{{ optional($event->start_date)->format('Y-m-d') }}"
                                                    required>
                                            </td>
                                            <td>
                                                <input
                                                    type="date"
                                                    name="end_date"
                                                    form="class-event-{{ $event->id }}"
                                                    class="form-control form-control-sm"
                                                    value="{{ optional($event->end_date)->format('Y-m-d') }}"
                                                    required>
                                            </td>
                                            <td>
                                                <div class="event-period-stack">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max="9"
                                                        name="period_from"
                                                        form="class-event-{{ $event->id }}"
                                                        class="form-control form-control-sm"
                                                        value="{{ $event->period_from ?? 1 }}">
                                                    <span class="event-period-separator">-</span>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max="9"
                                                        name="period_to"
                                                        form="class-event-{{ $event->id }}"
                                                        class="form-control form-control-sm"
                                                        value="{{ $event->period_to ?? 9 }}">
                                                </div>
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    name="note"
                                                    form="class-event-{{ $event->id }}"
                                                    class="form-control form-control-sm event-row-input"
                                                    value="{{ $event->note }}">
                                            </td>
                                            <td class="text-center">
                                                <form id="class-event-{{ $event->id }}" method="POST" action="{{ route('schedule.semester-events.update', [$plan->id, $event->id]) }}" class="d-none">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="class_id" value="{{ $event->class_id }}">
                                                </form>
                                                <div class="event-action-stack">
                                                    <button type="submit" form="class-event-{{ $event->id }}" class="btn btn-sm btn-outline-primary">Luu</button>
                                                    <form method="POST" action="{{ route('schedule.semester-events.destroy', [$plan->id, $event->id]) }}" class="mb-0">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoa su kien nay?')">Xoa</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted py-4">Chua co su kien cho lop nay.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Chua co lop duoc ap dung trong ke hoach nay.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
