@php
    $canManageHolidayCalendar = auth()->user() && (auth()->user()->isTrainingOffice() || auth()->user()->isAdmin());
@endphp

@if ($canManageHolidayCalendar)
    <div class="card mt-3">
        <div class="card-body">
            <h5 class="card-title mb-1">Quản trị ngày nghỉ lễ, tết</h5>
            <small class="text-muted">Quản lý đợt nghỉ lễ, tết cho toàn bộ hệ thống. Có thể khai báo một khoảng nhiều ngày liên tiếp.</small>

            <form method="POST" action="{{ route('holiday-calendar.store') }}" class="border rounded p-3 bg-light mt-3">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Tên đợt nghỉ</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                            value="{{ old('name') }}" maxlength="255" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Từ ngày</label>
                        @include('schedule::partials._date-picker-field', [
                            'label' => '',
                            'name' => 'start_date',
                            'field' => 'holiday_calendar_new_start_date',
                            'displayId' => 'holidayCalendarNewStartDate',
                            'nativeId' => 'holidayCalendarNewStartDateNative',
                            'value' => old('start_date'),
                            'inputClass' => 'form-control-sm',
                            'wrapperClass' => 'mb-0',
                            'buttonLabel' => 'Lịch',
                            'required' => true,
                        ])
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="mb-1">Đến ngày</label>
                        @include('schedule::partials._date-picker-field', [
                            'label' => '',
                            'name' => 'end_date',
                            'field' => 'holiday_calendar_new_end_date',
                            'displayId' => 'holidayCalendarNewEndDate',
                            'nativeId' => 'holidayCalendarNewEndDateNative',
                            'value' => old('end_date'),
                            'inputClass' => 'form-control-sm',
                            'wrapperClass' => 'mb-0',
                            'buttonLabel' => 'Lịch',
                            'required' => true,
                        ])
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="mb-1">Trạng thái</label>
                        <select name="is_active" class="form-control form-control-sm">
                            <option value="1" @selected((string) old('is_active', '1') === '1')>Đang sử dụng</option>
                            <option value="0" @selected((string) old('is_active', '1') === '0')>Tạm tắt</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Thêm</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label class="mb-1">Ghi chú</label>
                        <input type="text" name="note" class="form-control form-control-sm"
                            value="{{ old('note') }}" maxlength="1000"
                            placeholder="Mô tả bổ sung nếu cần">
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Tên đợt nghỉ</th>
                            <th style="width: 150px;">Từ ngày</th>
                            <th style="width: 150px;">Đến ngày</th>
                            <th style="width: 140px;">Trạng thái</th>
                            <th>Ghi chú</th>
                            <th style="width: 320px;">Thao tác</th>
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
                                    'name' => 'start_date',
                                    'field' => 'holiday_calendar_'.$holiday->id.'_start_date',
                                    'displayId' => 'holidayCalendarStartDate'.$holiday->id,
                                    'nativeId' => 'holidayCalendarStartDateNative'.$holiday->id,
                                    'value' => optional($holiday->start_date)->format('Y-m-d'),
                                    'inputClass' => 'form-control-sm',
                                    'wrapperClass' => 'mb-0',
                                    'buttonLabel' => 'Lịch',
                                    'required' => true,
                                ])
                                </td>
                                <td>
                                @include('schedule::partials._date-picker-field', [
                                    'label' => '',
                                    'name' => 'end_date',
                                    'field' => 'holiday_calendar_'.$holiday->id.'_end_date',
                                    'displayId' => 'holidayCalendarEndDate'.$holiday->id,
                                    'nativeId' => 'holidayCalendarEndDateNative'.$holiday->id,
                                    'value' => optional($holiday->end_date)->format('Y-m-d'),
                                    'inputClass' => 'form-control-sm',
                                    'wrapperClass' => 'mb-0',
                                    'buttonLabel' => 'Lịch',
                                    'required' => true,
                                ])
                                </td>
                                <td>
                                        <select name="is_active" class="form-control form-control-sm">
                                            <option value="1" @selected($holiday->is_active)>Đang sử dụng</option>
                                            <option value="0" @selected(! $holiday->is_active)>Tạm tắt</option>
                                        </select>
                                </td>
                                <td>
                                        <input type="text" name="note" class="form-control form-control-sm"
                                            value="{{ $holiday->note }}" maxlength="1000">
                                </td>
                                <td>
                                        <div class="d-flex">
                                            <button type="submit" class="btn btn-sm btn-outline-primary mr-2">Lưu</button>
                                    </form>

                                    <form method="POST" action="{{ route('holiday-calendar.destroy', $holiday->id) }}"
                                        onsubmit="return confirm('Xoa dot nghi nay?');" class="mb-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                    </form>

                                    <form method="POST" action="{{ route('holiday-calendar.cancel-slots', $holiday->id) }}"
                                        onsubmit="return confirm('Hủy tất cả tiết môn học chưa giảng dạy từ ' + '{{ optional($holiday->start_date)->format('d/m/Y') }}' + ' đến ' + '{{ optional($holiday->end_date)->format('d/m/Y') }}' + '? Các Khoa liên quan sẽ phải phân công lại và gửi duyệt.');"
                                        class="mb-0 ml-2">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Hủy tiết trùng lịch</button>
                                    </form>
                                        </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Chưa có đợt nghỉ nào trong holiday calendar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
