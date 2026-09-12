@extends('layouts.dashboard')

@section('title', 'Admin Panel - User Management')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Tạo tài khoản mới</h4>
                    <p class="text-muted">
                        Hệ thống chạy nội bộ, không gửi email — tài khoản được cấp sẵn mật khẩu mặc định
                        <strong>{{ $defaultPassword }}</strong>. Vui lòng cung cấp mật khẩu này cho người dùng;
                        hệ thống sẽ bắt buộc họ đổi mật khẩu ngay lần đăng nhập đầu tiên.
                        Tài khoản giáo viên được tạo từ màn hình <a href="{{ route('management.index') }}">Quản lý giáo viên</a>.
                        Một tài khoản có thể giữ đồng thời nhiều vai trò.
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
                            <div class="col-md-4 form-group">
                                <label for="create-user-name">Họ tên</label>
                                <input type="text" id="create-user-name" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="create-user-email">Email</label>
                                <input type="email" id="create-user-email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255" required>
                            </div>
                            <div class="col-md-4 form-group" id="create-user-department-group" style="display: none;">
                                <label for="create-user-department">Khoa</label>
                                <select id="create-user-department" name="department_id" class="form-control">
                                    <option value="">-- Chọn khoa --</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Vai trò</label>
                            <div class="create-user-roles" style="display: flex; flex-wrap: wrap; gap: 12px;">
                                @foreach ($assignableRoles as $role)
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input create-user-role-checkbox"
                                            id="create-user-role-{{ $role->slug }}"
                                            name="roles[]"
                                            value="{{ $role->slug }}"
                                            data-department-scoped="{{ in_array($role->slug, $departmentScopedRoles, true) ? '1' : '0' }}"
                                            @checked(in_array($role->slug, (array) old('roles', []), true))
                                        >
                                        <label class="form-check-label" for="create-user-role-{{ $role->slug }}">{{ $role->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Tạo tài khoản</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Yêu cầu đặt lại mật khẩu</h4>
                    <p class="text-muted">
                        Người dùng gửi yêu cầu khi quên mật khẩu. Duyệt để cho phép họ truy cập link đặt mật khẩu mới đã được cấp sẵn cho họ.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Người dùng</th>
                                    <th>Email</th>
                                    <th>Thời gian gửi</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($passwordResetRequests as $resetRequest)
                                    <tr>
                                        <td>{{ $resetRequest->user->name }}</td>
                                        <td>{{ $resetRequest->user->email }}</td>
                                        <td>{{ $resetRequest->requested_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.password-reset-requests.approve', $resetRequest) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">Duyệt</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.password-reset-requests.reject', $resetRequest) }}" class="d-inline" onsubmit="return confirm('Từ chối yêu cầu của {{ $resetRequest->user->email }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Từ chối</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Không có yêu cầu nào đang chờ duyệt.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title">Ảnh nền trang đăng nhập</h4>
                    <p class="text-muted">
                        Tuỳ chỉnh ảnh nền hiển thị ở trang đăng nhập. Nên dùng ảnh ngang (khuyến nghị tối thiểu 1920x1080),
                        định dạng JPG/PNG/WEBP, dung lượng tối đa 5MB.
                    </p>

                    @if ($errors->has('background'))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->get('background') as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row align-items-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div style="width: 100%; aspect-ratio: 16/9; border-radius: 8px; overflow: hidden; background: #eef0f5; display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6;">
                                @if ($loginBackgroundUrl)
                                    <img src="{{ $loginBackgroundUrl }}" alt="Ảnh nền đăng nhập hiện tại" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <span class="text-muted small">Đang dùng nền mặc định</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-8">
                            <form method="POST" action="{{ route('admin.settings.login-background.update') }}" enctype="multipart/form-data" class="mb-2">
                                @csrf
                                <div class="form-group d-flex align-items-center" style="gap: .75rem;">
                                    <input type="file" name="background" accept="image/png,image/jpeg,image/webp" class="form-control" required style="max-width: 320px;">
                                    <button type="submit" class="btn btn-primary">Tải ảnh lên</button>
                                </div>
                            </form>
                            @if ($loginBackgroundUrl)
                                <form method="POST" action="{{ route('admin.settings.login-background.reset') }}" onsubmit="return confirm('Khôi phục ảnh nền mặc định?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Khôi phục mặc định</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Danh sách người dùng</h4>

                    <div class="table-responsive">
                        <table class="table table-striped admin-users-table">
                            <colgroup>
                                <col style="width: 13%;">
                                <col style="width: 16%;">
                                <col style="width: 10%;">
                                <col style="width: 33%;">
                                <col style="width: 8%;">
                                <col style="width: 8%;">
                                <col style="width: 12%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Vai trò</th>
                                    <th>Status</th>
                                    <th>Mật khẩu</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    @php
                                        $userRoleSlugs = $user->roles->pluck('slug')->all();
                                    @endphp
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->department?->name ?? '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.users.update-roles', $user) }}" class="user-roles-form">
                                                @csrf
                                                <div class="user-roles-grid">
                                                    @foreach ($assignableRoles as $role)
                                                        <div class="form-check">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input user-role-checkbox"
                                                                id="user-{{ $user->id }}-role-{{ $role->slug }}"
                                                                name="roles[]"
                                                                value="{{ $role->slug }}"
                                                                data-department-scoped="{{ in_array($role->slug, $departmentScopedRoles, true) ? '1' : '0' }}"
                                                                @checked(in_array($role->slug, $userRoleSlugs, true))
                                                                @disabled(in_array($role->slug, $departmentScopedRoles, true) && $user->department_id === null)
                                                            >
                                                            <label class="form-check-label small" for="user-{{ $user->id }}-role-{{ $role->slug }}">{{ $role->name }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if ($user->department_id === null)
                                                    <p class="text-muted small mb-1">Chưa thuộc khoa nào — không thể gán vai trò Giáo vụ/Chủ nhiệm khoa.</p>
                                                @endif
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Cập nhật vai trò</button>
                                            </form>
                                            @php $otherRoles = $user->roles->pluck('name')->diff($assignableRoles->pluck('name')); @endphp
                                            @if ($otherRoles->isNotEmpty())
                                                <p class="text-muted small mb-0">Vai trò khác: {{ $otherRoles->implode(', ') }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $user->isLocked() ? 'badge-danger' : ($user->isApproved() ? 'badge-success' : 'badge-secondary') }}">
                                                {{ $user->isLocked() ? 'Đã khoá' : ucfirst($user->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($user->mustChangePassword())
                                                <span class="badge badge-warning">Chưa đổi (mặc định)</span>
                                            @else
                                                <span class="badge badge-success">Đã đổi</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($user->id !== auth()->id())
                                                @if ($user->isLocked())
                                                    <form method="POST" action="{{ route('admin.users.unlock', $user) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Mở khoá</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.users.lock', $user) }}" class="d-inline" onsubmit="return confirm('Khoá tài khoản {{ $user->email }}?');">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Khoá</button>
                                                    </form>
                                                @endif
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

    <style>
        /* Bảng danh sách người dùng: cột co giãn cố định + nội dung chia hàng thay vì tràn ngang */
        .admin-users-table {
            table-layout: fixed;
        }

        .admin-users-table td,
        .admin-users-table th {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .admin-users-table .user-roles-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px 10px;
            margin-bottom: 6px;
        }

        .admin-users-table .user-roles-grid .form-check {
            min-width: 0;
        }

        .admin-users-table .user-roles-grid .form-check-label {
            white-space: normal;
        }

        /* Bảng gốc dùng màu stripe đen (#000) từ vendor CSS — ghi đè lại tông sáng cho khớp theme */
        .admin-users-table.table-striped tbody tr:nth-of-type(odd) {
            background-color: #FAFBF9;
        }
    </style>

    <script>
        (function () {
            var departmentGroup = document.getElementById('create-user-department-group');
            var departmentSelect = document.getElementById('create-user-department');
            var createRoleCheckboxes = document.querySelectorAll('.create-user-role-checkbox');

            function anyDepartmentScopedChecked(checkboxes) {
                return Array.prototype.some.call(checkboxes, function (checkbox) {
                    return checkbox.checked && checkbox.getAttribute('data-department-scoped') === '1';
                });
            }

            function toggleCreateDepartment() {
                var needsDepartment = anyDepartmentScopedChecked(createRoleCheckboxes);
                departmentGroup.style.display = needsDepartment ? '' : 'none';
                departmentSelect.required = needsDepartment;
                if (!needsDepartment) {
                    departmentSelect.value = '';
                }
            }

            Array.prototype.forEach.call(createRoleCheckboxes, function (checkbox) {
                checkbox.addEventListener('change', toggleCreateDepartment);
            });
            toggleCreateDepartment();
        })();
    </script>
@endsection
