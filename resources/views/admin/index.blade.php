@extends('layouts.dashboard')

@section('title', 'Admin Panel - User Management')

@section('content')
    @php
        $roleLabels = [
            \App\Models\User::ROLE_TEACHER => 'Giáo viên',
            \App\Models\User::ROLE_DEPARTMENT_STAFF => 'Nhân viên khoa',
            \App\Models\User::ROLE_TRAINING_OFFICE => 'Nhân viên phòng đào tạo',
            \App\Models\User::ROLE_LEADERSHIP => 'Ban giám hiệu',
            \App\Models\User::ROLE_STUDENT => 'Học viên',
            \App\Models\User::ROLE_ADMIN => 'Quản trị viên',
        ];
    @endphp
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Tạo tài khoản mới</h4>
                    <p class="text-muted">
                        Người được tạo sẽ nhận email mời để tự đặt mật khẩu lần đầu — hệ thống không lưu hay hiển thị mật khẩu cho Admin.
                        Tài khoản giáo viên được tạo từ màn hình <a href="{{ route('management.index') }}">Quản lý giáo viên</a>.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label for="create-user-name">Họ tên</label>
                                <input type="text" id="create-user-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" required>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="create-user-email">Email</label>
                                <input type="email" id="create-user-email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255" required>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="create-user-role">Vai trò</label>
                                <select id="create-user-role" name="role" class="form-control" required>
                                    <option value="">-- Chọn vai trò --</option>
                                    @foreach ($creatableRoles as $role)
                                        <option value="{{ $role }}" @selected(old('role') === $role)>{{ $roleLabels[$role] ?? $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group" id="create-user-department-group" style="display: none;">
                                <label for="create-user-department">Khoa</label>
                                <select id="create-user-department" name="department_id" class="form-control">
                                    <option value="">-- Chọn khoa --</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Tạo tài khoản &amp; gửi email mời</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Danh sách người dùng</h4>

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Kích hoạt</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->department?->name ?? '-' }}</td>
                                        <td>{{ $roleLabels[$user->role] ?? ($user->role ?? '-') }}</td>
                                        <td>
                                            <span class="badge {{ $user->isApproved() ? 'badge-success' : 'badge-secondary' }}">
                                                {{ ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($user->email_verified_at !== null)
                                                <span class="badge badge-success">Đã kích hoạt</span>
                                            @else
                                                <span class="badge badge-warning">Chưa kích hoạt</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($user->email_verified_at === null)
                                                <form method="POST" action="{{ route('admin.users.resend-invitation', $user) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Gửi lại email</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Chưa có người dùng nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var roleSelect = document.getElementById('create-user-role');
            var departmentGroup = document.getElementById('create-user-department-group');
            var departmentSelect = document.getElementById('create-user-department');

            function toggleDepartment() {
                var isDepartmentStaff = roleSelect.value === @json(\App\Models\User::ROLE_DEPARTMENT_STAFF);
                departmentGroup.style.display = isDepartmentStaff ? '' : 'none';
                departmentSelect.required = isDepartmentStaff;
                if (!isDepartmentStaff) {
                    departmentSelect.value = '';
                }
            }

            roleSelect.addEventListener('change', toggleDepartment);
            toggleDepartment();
        })();
    </script>
@endsection
