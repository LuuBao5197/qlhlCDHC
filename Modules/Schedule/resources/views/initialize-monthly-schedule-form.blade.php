@extends('layouts.dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-3xl font-bold mb-6">Khởi Tạo Lịch Tháng</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
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
                <select id="month" name="month" class="w-full px-4 py-2 border rounded-lg @error('month') border-red-500 @enderror">
                    <option value="">-- Chọn tháng --</option>
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ old('month') == $m ? 'selected' : '' }}>
                            Tháng {{ $m }}
                        </option>
                    @endfor
                </select>
                @error('month')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div class="mb-6">
                <label for="year" class="block text-gray-700 font-semibold mb-2">Chọn Năm</label>
                <input type="number" id="year" name="year" placeholder="2026" class="w-full px-4 py-2 border rounded-lg @error('year') border-red-500 @enderror" value="{{ old('year', date('Y')) }}">
                @error('year')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <p class="text-blue-700"><strong>Lưu ý:</strong> Chỉ có thể khởi tạo lịch của tháng tiếp theo trở đi. Ngày hôm nay là {{ now()->format('d/m/Y') }}.</p>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                Khởi Tạo Lịch Tháng
            </button>
            <a href="{{ route('monthly-schedule.initialize') }}" class="block text-center mt-4 text-gray-600 hover:text-gray-800">Quay lại</a>
        </form>
    </div>
</div>
@endsection
