@extends('layouts.dashboard')

@section('title', 'Settings')

@section('content')
    @php($user = auth()->user())
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Account Settings</h4>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> {{ $user->email }}</p>
                            <p><strong>Mã giáo viên/nhân sự:</strong> {{ $user->employee_code ?? '-' }}
                                <small class="text-muted d-block">Liên hệ Admin nếu cần thay đổi email hoặc mã này.</small>
                            </p>
                            <p><strong>Vai trò:</strong>
                                <span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $user->role ?? 'Not Assigned')) }}</span>
                            </p>
                            <p><strong>Trạng thái:</strong>
                                @if($user->isApproved())
                                    <span class="badge badge-success">Approved</span>
                                @elseif($user->isPending())
                                    <span class="badge badge-warning">Pending Approval</span>
                                @elseif($user->isRejected())
                                    <span class="badge badge-danger">Rejected</span>
                                @endif
                            </p>
                            <p><strong>Tham gia từ:</strong> {{ $user->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Cập nhật hồ sơ</h4>

                    @if ($errors->updateProfile->any() ?? false)
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->updateProfile->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Họ tên</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control p_input" required>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control p_input">
                        </div>
                        <div class="form-group">
                            <label>Ảnh đại diện</label>
                            @if ($user->avatar_path)
                                <div class="mb-2">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}" alt="avatar" style="width:64px;height:64px;border-radius:50%;object-fit:cover;">
                                </div>
                            @endif
                            <input type="file" name="avatar" accept="image/*" class="form-control-file">
                        </div>
                        <button type="submit" class="btn btn-primary">Lưu hồ sơ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Đổi mật khẩu</h4>

                    @if ($errors->changePassword->any() ?? false)
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->changePassword->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control p_input" required>
                        </div>
                        <div class="form-group">
                            <label>Mật khẩu mới</label>
                            <input type="password" name="password" class="form-control p_input" minlength="8" required>
                            <small class="text-muted">Tối thiểu 8 ký tự.</small>
                        </div>
                        <div class="form-group">
                            <label>Xác nhận mật khẩu mới</label>
                            <input type="password" name="password_confirmation" class="form-control p_input" minlength="8" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
