<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Training\Models\Department;
use Modules\Training\Models\Teacher;


class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (!$user || !$user->isAdmin()) {
                abort(403, 'Access denied. Admin only.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $pendingUsers = User::query()
            ->with(['department', 'requestedDepartment'])
            ->where('status', User::STATUS_PENDING)
            ->paginate(15);
        $approvedUsers = User::query()
            ->with(['department', 'requestedDepartment'])
            ->where('status', User::STATUS_APPROVED)
            ->paginate(15);
        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('admin.index', compact('pendingUsers', 'approvedUsers', 'departments'));
    }

    public function approve(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (! in_array($user->requested_role, [User::ROLE_TEACHER, User::ROLE_DEPARTMENT_STAFF, User::ROLE_TRAINING_OFFICE], true)) {
            return back()->with('error', 'Vai tro dang ky khong hop le.');
        }

        if (
            in_array($user->requested_role, [User::ROLE_TEACHER, User::ROLE_DEPARTMENT_STAFF], true)
            && empty($user->requested_department_id)
        ) {
            return back()->with('error', 'Tai khoan nay chua co khoa dang ky. Khong the phe duyet.');
        }

        if ($user->requested_role === User::ROLE_TEACHER) {
            $teacherCode = trim((string) $user->employee_code);

            if ($teacherCode === '') {
                return back()->with('error', 'Tài khoản giáo viên bắt buộc phải có mã giáo viên trước khi phê duyệt.');
            }

            $teacherByCode = Teacher::query()
                ->where('teacher_code', $teacherCode)
                ->first();

            if ($teacherByCode !== null && $teacherByCode->user_id !== null && (int) $teacherByCode->user_id !== (int) $user->id) {
                return back()->with('error', 'Mã giáo viên đã được liên kết với tài khoản khác.');
            }
        }

        DB::transaction(function () use ($user): void {
            $user->update([
                'status' => User::STATUS_APPROVED,
                'role' => $user->requested_role,
                'department_id' => in_array($user->requested_role, [User::ROLE_TEACHER, User::ROLE_DEPARTMENT_STAFF], true)
                    ? (int) $user->requested_department_id
                    : null,
            ]);

            if ($user->requested_role === User::ROLE_TEACHER) {
                $teacherCode = trim((string) $user->employee_code);

                Teacher::query()
                    ->where('user_id', $user->id)
                    ->where('teacher_code', '!=', $teacherCode)
                    ->update(['user_id' => null]);

                Teacher::query()->updateOrCreate(
                    ['teacher_code' => $teacherCode],
                    [
                        'name' => $user->name,
                        'status' => 'active',
                        'department_id' => (int) $user->requested_department_id,
                        'user_id' => $user->id,
                    ]
                );
            }
        });

        return back()->with('success', 'User approved successfully.');
    }

    public function reject($id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => User::STATUS_REJECTED]);

        return back()->with('success', 'User rejected successfully.');
    }

    public function updateRole(Request $request, $id)
    {
        return back()->with('error', 'Tinh nang doi vai tro thu cong da bi tat. Admin chi phe duyet/tu choi dang ky.');
    }
}
