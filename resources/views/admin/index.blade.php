@extends('layouts.dashboard')

@section('title', 'Admin Panel - User Management')

@section('content')
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

                    <!-- Pending Users -->
                    <div class="mt-4">
                        <h5>Pending Approval ({{ $pendingUsers->total() }})</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingUsers as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.users.approve', $user->id) }}" class="d-inline">
                                                    @csrf
                                                    <div class="form-group mb-2">
                                                        <select name="role" class="form-control form-control-sm" required>
                                                            <option value="">Select Role</option>
                                                            <option value="student">Student</option>
                                                            <option value="teacher">Teacher</option>
                                                            <option value="department_staff">Department Staff</option>
                                                            <option value="training_office">Training Office</option>
                                                            <option value="leadership">Leadership</option>
                                                        </select>
                                                    </div>
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
                                            <td colspan="4" class="text-center">No pending users</td>
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
                                            <td>
                                                <form method="POST" action="{{ route('admin.users.update-role', $user->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="role" class="form-control form-control-sm" onchange="this.form.submit()">
                                                        <option value="student" {{ $user->role == 'student' ? 'selected' : '' }}>Student</option>
                                                        <option value="teacher" {{ $user->role == 'teacher' ? 'selected' : '' }}>Teacher</option>
                                                        <option value="department_staff" {{ $user->role == 'department_staff' ? 'selected' : '' }}>Department Staff</option>
                                                        <option value="training_office" {{ $user->role == 'training_office' ? 'selected' : '' }}>Training Office</option>
                                                        <option value="leadership" {{ $user->role == 'leadership' ? 'selected' : '' }}>Leadership</option>
                                                        <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                                    </select>
                                                </form>
                                            </td>
                                            <td>
                                                <span class="badge badge-success">{{ ucfirst($user->status) }}</span>
                                            </td>
                                            <td>
                                                <!-- Additional actions can be added here -->
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No approved users</td>
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
