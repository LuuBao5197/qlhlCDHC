@php
    $canManageHolidayCalendar = auth()->user() && (auth()->user()->isTrainingOffice() || auth()->user()->isAdmin());
@endphp

@if ($canManageHolidayCalendar)
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="mb-1">Quan tri holiday calendar</h5>
            <small class="text-muted">Quan ly ngay nghi de dung chung cho dieu chinh lich nghi le/tet.</small>

            <form method="POST" action="{{ route('holiday-calendar.store') }}" class="border rounded p-3 bg-light mt-3">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="mb-1">Ten ngay nghi</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                            value="{{ old('name') }}" maxlength="255" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Ngay</label>
                        @include('schedule::partials._date-picker-field', [
                            'label' => '',
                            'name' => 'date',
                            'field' => 'holiday_calendar_new_date',
                            'displayId' => 'holidayCalendarNewDate',
                            'nativeId' => 'holidayCalendarNewDateNative',
                            'value' => old('date'),
                            'inputClass' => 'form-control-sm',
                            'wrapperClass' => 'mb-0',
                            'buttonLabel' => 'Lich',
                            'required' => true,
                        ])
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Trang thai</label>
                        <select name="is_active" class="form-control form-control-sm">
                            <option value="1" @selected((string) old('is_active', '1') === '1')>Dang su dung</option>
                            <option value="0" @selected((string) old('is_active', '1') === '0')>Tam tat</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Them</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label class="mb-1">Ghi chu</label>
                        <input type="text" name="note" class="form-control form-control-sm"
                            value="{{ old('note') }}" maxlength="1000"
                            placeholder="Mo ta bo sung neu can">
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Ten ngay nghi</th>
                            <th style="width: 150px;">Ngay</th>
                            <th style="width: 140px;">Trang thai</th>
                            <th>Ghi chu</th>
                            <th style="width: 320px;">Thao tac</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($holidayCalendars ?? collect() as $holiday)
                        <tr>
                            <td>{{ $holiday->id }}</td>
                            <td>
                                <form method="POST" action="{{ route('holiday-calendar.update', $holiday->id) }}" class="mb-0">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" class="form-control form-control-sm"
                                        value="{{ $holiday->name }}" maxlength="255" required>
                            </td>
                            <td>
                                @include('schedule::partials._date-picker-field', [
                                    'label' => '',
                                    'name' => 'date',
                                    'field' => 'holiday_calendar_'.$holiday->id.'_date',
                                    'displayId' => 'holidayCalendarDate'.$holiday->id,
                                    'nativeId' => 'holidayCalendarDateNative'.$holiday->id,
                                    'value' => optional($holiday->date)->format('Y-m-d'),
                                    'inputClass' => 'form-control-sm',
                                    'wrapperClass' => 'mb-0',
                                    'buttonLabel' => 'Lich',
                                    'required' => true,
                                ])
                                </td>
                                <td>
                                        <select name="is_active" class="form-control form-control-sm">
                                            <option value="1" @selected($holiday->is_active)>Dang su dung</option>
                                            <option value="0" @selected(! $holiday->is_active)>Tam tat</option>
                                        </select>
                                </td>
                                <td>
                                        <input type="text" name="note" class="form-control form-control-sm"
                                            value="{{ $holiday->note }}" maxlength="1000">
                                </td>
                                <td>
                                        <div class="d-flex">
                                            <button type="submit" class="btn btn-sm btn-outline-primary mr-2">Luu</button>
                                    </form>

                                    <form method="POST" action="{{ route('holiday-calendar.destroy', $holiday->id) }}"
                                        onsubmit="return confirm('Xoa ngay nghi nay?');" class="mb-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xoa</button>
                                    </form>
                                        </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Chua co ngay nghi nao trong holiday calendar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
