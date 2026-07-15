@extends('layouts.dashboard')

@section('title', 'Báo cáo giáo viên')

@section('content')
    @include('reports::teaching.partials.nav')
    @include('reports::teaching.partials.filters')

    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Khối lượng giảng dạy theo giáo viên</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Giáo viên</th>
                            <th>Khoa</th>
                            <th>Tiết thực tế</th>
                            <th>Ngày dạy</th>
                            <th>Lớp</th>
                            <th>Môn/Bài</th>
                            <th>Tiết ghép</th>
                            <th>Không ghép</th>
                            <th>Tỷ lệ đánh giá</th>
                            @foreach($ratingLabels as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td>{{ $teacher->teacher_name }}</td>
                                <td>{{ $teacher->department_name ?? '-' }}</td>
                                <td>{{ number_format($teacher->actual_periods) }}</td>
                                <td>{{ number_format($teacher->teaching_days) }}</td>
                                <td>{{ number_format($teacher->class_count) }}</td>
                                <td>{{ number_format($teacher->lesson_count) }}</td>
                                <td>{{ number_format($teacher->merged_periods) }}</td>
                                <td>{{ number_format($teacher->single_periods) }}</td>
                                <td>{{ number_format($teacher->evaluation_rate, 2) }}%</td>
                                @include('reports::teaching.partials.rating-cells', ['ratings' => $teacher->ratings])
                            </tr>
                        @empty
                            <tr><td colspan="13" class="text-center">Không có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $teachers->links() }}
            <p class="text-muted mt-3 mb-0">{{ $groupingNote }}</p>
        </div>
    </div>
@endsection
