@extends('layouts.dashboard')

@section('content')
@php
    $strictRollingWindow = (bool) config('schedule.initialize_monthly_schedule.strict_next_month_only', true);
    $allowCurrentMonthForTest = (bool) config('schedule.initialize_monthly_schedule.allow_current_month_in_test', false);
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-3xl font-bold mb-6">Khởi Tạo Lịch Tháng</h1>

        @if ($errors->any())
            <div class="alert alert-danger mb-4" role="alert">
                <strong>Lỗi:</strong>
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('monthly-schedule.initialize') }}" method="POST">
            @csrf

            <div class="mb-6">
                <label for="month" class="block text-gray-700 font-semibold mb-2">Chọn Tháng</label>
                <select id="month" name="month" class="form-control @error('month') is-invalid @enderror">
                    <option value="">-- Chọn tháng --</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ old('month') == $m ? 'selected' : '' }}>
                            Tháng {{ $m }}
                        </option>
                    @endfor
                </select>
                @error('month')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-6">
                <label for="year" class="block text-gray-700 font-semibold mb-2">Chọn Năm</label>
                <input type="number" id="year" name="year" placeholder="2026" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', date('Y')) }}">
                @error('year')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                @if ($strictRollingWindow)
                    <p class="text-blue-700"><strong>Lưu ý:</strong> Được khởi tạo bù tháng hiện tại hoặc chuẩn bị tháng kế tiếp. Ngày hôm nay là {{ now()->format('d/m/Y') }}.</p>
                @elseif ($allowCurrentMonthForTest)
                    <p class="text-blue-700"><strong>Lưu ý (chế độ test):</strong> Được khởi tạo từ tháng hiện tại trở đi. Ngày hôm nay là {{ now()->format('d/m/Y') }}.</p>
                @else
                    <p class="text-blue-700"><strong>Lưu ý:</strong> Chỉ được khởi tạo từ tháng kế tiếp trở đi. Ngày hôm nay là {{ now()->format('d/m/Y') }}.</p>
                @endif
            </div>

            @if (auth()->user()?->isAdmin())
                <div class="alert alert-warning mb-6" style="border:1px dashed #b98900;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="admin_backfill" value="1"
                            id="adminBackfillToggle" {{ old('admin_backfill') ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="adminBackfillToggle">
                            Bổ sung dữ liệu cũ (bỏ qua ràng buộc tháng hiện tại/kế tiếp)
                        </label>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Lý do bổ sung dữ liệu cũ <span class="text-danger">*</span></label>
                        <textarea name="admin_backfill_reason" class="form-control @error('admin_backfill_reason') is-invalid @enderror"
                            rows="2" placeholder="Vi du: Khoi tao bu lich thang truoc khi he thong van hanh...">{{ old('admin_backfill_reason') }}</textarea>
                        @error('admin_backfill_reason')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                Khởi Tạo Lịch Tháng
            </button>
            <a href="{{ route('monthly-schedule.initialize') }}" class="block text-center mt-4 text-gray-600 hover:text-gray-800">Quay lại</a>
        </form>
    </div>
</div>
@endsection
