@extends('layouts.dashboard')

@section('title', 'Tạo Lịch Học Kỳ')

@section('content')
<form action="{{ route('schedule.store') }}" method="POST" style="display:flex; flex-direction: column; gap:16px;">
    <!-- Các trường input của form -->
    <div class="form-group">
        <label for="semester">Kỳ Học</label>
        <input type="number" class="form-control" id="semester" name="semester" value="{{ old('semester', 1) }}" />
    </div>
    <div class="form-group">
        <label for="year">Năm Học</label>
        <input type="number" class="form-control" id="year" name="year" value="{{ old('year', date('Y')) }}" />
    </div>
    <!-- Các phần tử khác của form -->
    <button type="submit" class="btn btn-primary">Tạo Lịch</button>
</form>
@endsection
