@extends('layouts.dashboard')

@section('title', 'Tạo phiếu đề nghị thay đổi')

@section('content')
    @php
        $user = auth()->user();
        $canDepartmentAssign = $user && ($user->isDepartmentStaff() || $user->isAdmin());
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-start">
                <div class="mb-2">
                    <h4 class="mb-1">Tạo phiếu đề nghị thay đổi kế hoạch giảng dạy</h4>
                    <p class="text-muted mb-0">
                        Tách riêng màn hình tạo phiếu để lọc theo tháng tổng hợp, chọn slot hàng loạt và chỉnh sửa trước khi gửi duyệt.
                    </p>
                </div>
                <div class="mb-2">
                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm">
                        Quay lại dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if (!$canDepartmentAssign)
        <div class="alert alert-info">
            Tài khoản hiện tại không tạo được phiếu thay đổi thường, nhưng vẫn có thể dùng các chức năng phù hợp với vai trò của bạn trên trang này.
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @include('schedule::partials._change-request-create')
                </div>
            </div>
        </div>
    </div>
@endsection
