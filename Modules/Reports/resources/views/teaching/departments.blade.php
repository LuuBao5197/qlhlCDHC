@extends('layouts.dashboard')

@section('title', 'Báo cáo khoa')

@section('content')
    @include('reports::teaching.partials.nav')
    @include('reports::teaching.partials.filters')

    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Thống kê theo khoa</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Khoa</th>
                            <th>Tiết dạy</th>
                            <th>Giáo viên</th>
                            <th>Lớp</th>
                            <th>Lượt đánh giá</th>
                            <th>Tỷ lệ vắng</th>
                            @foreach($ratingLabels as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['departments'] as $department)
                            <tr>
                                <td>{{ $department->department_name }}</td>
                                <td>{{ number_format($department->actual_periods) }}</td>
                                <td>{{ number_format($department->teacher_count) }}</td>
                                <td>{{ number_format($department->class_count) }}</td>
                                <td>{{ number_format($department->quality_turns) }}</td>
                                <td>{{ number_format($department->absence_rate, 2) }}%</td>
                                @include('reports::teaching.partials.rating-cells', ['ratings' => $department->ratings])
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center">Không có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $data['departments']->links() }}
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Giáo viên nhiều tiết Trung bình/Yếu</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Khoa</th>
                                    <th>Giáo viên</th>
                                    <th>Số lượt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['topLowTeachers'] as $teacher)
                                    <tr>
                                        <td>{{ $teacher->department_name ?? '-' }}</td>
                                        <td>{{ $teacher->teacher_name }}</td>
                                        <td>{{ number_format($teacher->low_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">Không có dữ liệu</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Lớp nhiều tiết Trung bình/Yếu</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Khoa</th>
                                    <th>Lớp</th>
                                    <th>Số lượt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['topLowClasses'] as $class)
                                    <tr>
                                        <td>{{ $class->department_name ?? '-' }}</td>
                                        <td>{{ $class->class_name }}</td>
                                        <td>{{ number_format($class->low_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">Không có dữ liệu</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
