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
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Admin Panel - User Management</h4>

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

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Pending Users -->
                    <div class="mt-4">
                        <h5>Pending Approval ({{ $pendingUsers->total() }})</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Vai trò đăng ký</th>
                                        <th>Khoa đăng ký</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingUsers as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $roleLabels[$user->requested_role] ?? '-' }}</td>
                                            <td>{{ $user->requestedDepartment?->name ?? '-' }}</td>
                                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.users.approve', $user->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.users.reject', $user->id) }}" class="d-inline ml-2">
                                                    @csrf
                                                    @method('POST')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No pending users</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $pendingUsers->links() }}
                    </div>

                    <!-- Approved Users -->
                    <div class="mt-5">
                        <h5>Approved Users ({{ $approvedUsers->total() }})</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($approvedUsers as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->department?->name ?? '-' }}</td>
                                            <td>{{ $roleLabels[$user->role] ?? $user->role }}</td>
                                            <td>
                                                <span class="badge badge-success">{{ ucfirst($user->status) }}</span>
                                            </td>
                                            <td>
                                                -
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No approved users</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $approvedUsers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
