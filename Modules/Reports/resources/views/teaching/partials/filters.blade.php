@php
    $routeName = request()->route()->getName();
@endphp

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($routeName) }}">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Kiểu thời gian</label>
                        <select name="period_type" class="form-control">
                            <option value="week" @selected($filters['period_type'] === 'week')>Tuần</option>
                            <option value="month" @selected($filters['period_type'] === 'month')>Tháng</option>
                            <option value="semester" @selected($filters['period_type'] === 'semester')>Học kỳ</option>
                            <option value="custom" @selected($filters['period_type'] === 'custom')>Tùy chọn</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Từ ngày</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Đến ngày</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Tháng</label>
                        <input type="month" name="month" value="{{ $filters['month'] }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Ngày trong tuần</label>
                        <input type="date" name="week_start" value="{{ $filters['week_start'] }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>Năm HK</label>
                        <input type="number" name="semester_year" value="{{ $filters['semester_year'] }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>HK</label>
                        <select name="semester" class="form-control">
                            <option value="1" @selected($filters['semester'] === '1')>1</option>
                            <option value="2" @selected($filters['semester'] === '2')>2</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Khoa</label>
                        <select name="department_id" class="form-control">
                            <option value="">Tất cả</option>
                            @foreach($filterOptions['departments'] as $department)
                                <option value="{{ $department->id }}" @selected((int) $filters['department_id'] === (int) $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Giáo viên</label>
                        <select name="teacher_id" class="form-control">
                            <option value="">Tất cả</option>
                            @foreach($filterOptions['teachers'] as $teacher)
                                <option value="{{ $teacher->id }}" @selected((int) $filters['teacher_id'] === (int) $teacher->id)>
                                    {{ $teacher->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Lớp</label>
                        <select name="class_id" class="form-control">
                            <option value="">Tất cả</option>
                            @foreach($filterOptions['classes'] as $class)
                                <option value="{{ $class->id }}" @selected((int) $filters['class_id'] === (int) $class->id)>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Môn học</label>
                        <select name="subject_id" class="form-control">
                            <option value="">Tất cả</option>
                            @foreach($filterOptions['subjects'] as $subject)
                                <option value="{{ $subject->id }}" @selected((int) $filters['subject_id'] === (int) $subject->id)>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Bài học</label>
                        <select name="subject_lesson_id" class="form-control">
                            <option value="">Tất cả</option>
                            @foreach($filterOptions['subjectLessons'] as $lesson)
                                <option value="{{ $lesson->id }}" @selected((int) $filters['subject_lesson_id'] === (int) $lesson->id)>
                                    {{ $lesson->subject_name }} - {{ $lesson->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($showRatingFilter ?? false)
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Xếp loại</label>
                            <select name="rating_level" class="form-control">
                                <option value="">Tất cả</option>
                                @foreach($filterOptions['ratingLabels'] as $level => $label)
                                    <option value="{{ $level }}" @selected($filters['rating_level'] === $level)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                <div class="{{ ($showRatingFilter ?? false) ? 'col-md-1' : 'col-md-2' }} d-flex align-items-end">
                    <div class="form-group w-100">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="mdi mdi-filter"></i> Lọc
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
