@extends('layouts.dashboard')

@section('title', 'Báo cáo lớp')

@section('content')
    @include('reports::teaching.partials.nav')
    @include('reports::teaching.partials.filters')

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Thống kê theo lớp</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Lớp</th>
                            <th>Tổng tiết</th>
                            <th>Đã đánh giá</th>
                            <th>Tỷ lệ đánh giá</th>
                            <th>Quân số</th>
                            <th>Vắng</th>
                            <th>Tỷ lệ vắng</th>
                            @foreach($ratingLabels as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['classes'] as $class)
                            <tr>
                                <td>{{ $class->class_name }}</td>
                                <td>{{ number_format($class->total_periods) }}</td>
                                <td>{{ number_format($class->evaluated_periods) }}</td>
                                <td>{{ number_format($class->evaluation_rate, 2) }}%</td>
                                <td>{{ number_format($class->attendance_count) }}</td>
                                <td>{{ number_format($class->absent_count) }}</td>
                                <td>{{ number_format($class->absence_rate, 2) }}%</td>
                                @include('reports::teaching.partials.rating-cells', ['ratings' => $class->ratings])
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center">Không có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $data['classes']->links() }}
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Tiết Trung bình/Yếu theo lớp</h4>
            @include('reports::teaching.partials.quality-details-table', ['details' => $data['lowDetails']])
        </div>
    </div>
@endsection
