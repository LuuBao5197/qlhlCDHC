@extends('layouts.dashboard')

@section('title', 'Báo cáo tổng quan')

@section('content')
    @include('reports::teaching.partials.nav')
    @include('reports::teaching.partials.filters')

    <div class="row">
        @foreach([
            'Tổng số tiết dạy thực tế' => number_format($data['cards']['actual_periods']),
            'Tổng số tiết đã đánh giá' => number_format($data['cards']['evaluated_periods']),
            'Tổng số tiết chưa đánh giá' => number_format($data['cards']['unevaluated_periods']),
            'Tỷ lệ tiết đã đánh giá' => number_format($data['cards']['evaluation_rate'], 2) . '%',
            'Tổng lượt lớp được đánh giá' => number_format($data['cards']['quality_turns']),
            'Tổng số học viên vắng' => number_format($data['cards']['absent_count']),
            'Tổng quân số đã ghi nhận' => number_format($data['cards']['attendance_count']),
            'Tỷ lệ vắng' => number_format($data['cards']['absence_rate'], 2) . '%',
        ] as $label => $value)
            <div class="col-xl-3 col-sm-6 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted font-weight-normal">{{ $label }}</h6>
                        <h3 class="mb-0">{{ $value }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-6 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Tỷ lệ xếp loại</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Xếp loại</th>
                                    <th>Số lượt</th>
                                    <th>Tỷ lệ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['ratingDistribution'] as $bucket)
                                    <tr>
                                        <td>{{ $bucket['label'] }}</td>
                                        <td>{{ number_format($bucket['count']) }}</td>
                                        <td>{{ number_format($bucket['rate'], 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Top giáo viên nhiều tiết dạy</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Giáo viên</th>
                                    <th>Khoa</th>
                                    <th>Số tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['topTeachers'] as $teacher)
                                    <tr>
                                        <td>{{ $teacher->name }}</td>
                                        <td>{{ $teacher->department_name ?? '-' }}</td>
                                        <td>{{ number_format($teacher->actual_periods) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center">Không có dữ liệu</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted mt-3 mb-0">{{ $data['groupingNote'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Top lớp có tiết Trung bình/Yếu</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Lớp</th>
                                    <th>Số lượt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['topLowClasses'] as $class)
                                    <tr>
                                        <td>{{ $class->name }}</td>
                                        <td>{{ number_format($class->low_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr>
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
                    <h4 class="card-title">Top môn/bài học Trung bình/Yếu</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Môn/Bài học</th>
                                    <th>Số lượt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['topLowLessons'] as $lesson)
                                    <tr>
                                        <td>{{ trim(($lesson->subject_name ?? '-') . ' / ' . ($lesson->lesson_title ?? '-'), ' /') }}</td>
                                        <td>{{ number_format($lesson->low_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
