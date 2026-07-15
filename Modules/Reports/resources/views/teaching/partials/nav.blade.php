@php
    $items = [
        'reports.teaching.overview' => 'Báo cáo tổng quan',
        'reports.teaching.teachers' => 'Báo cáo giáo viên',
        'reports.teaching.classes' => 'Báo cáo lớp',
        'reports.teaching.quality' => 'Chất lượng giảng dạy',
        'reports.teaching.departments' => 'Báo cáo khoa',
    ];
@endphp

<div class="mb-4">
    <div class="btn-group flex-wrap" role="group">
        @foreach($items as $routeName => $label)
            <a href="{{ route($routeName, request()->query()) }}"
               class="btn btn-sm {{ request()->routeIs($routeName) ? 'btn-primary' : 'btn-outline-info' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
