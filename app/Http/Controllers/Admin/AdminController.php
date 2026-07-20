<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountInvitationService;
use App\Services\InternalNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Training\Models\Department;

class AdminController extends Controller
{
    /**
     * Role được phép tạo từ màn Admin. Teacher không nằm trong danh sách vì cần tạo kèm
     * hồ sơ giảng viên từ màn Quản lý giáo viên; admin/student không thuộc nghiệp vụ cấp phát.
     */
    private const CREATABLE_ROLES = [
        User::ROLE_LEADERSHIP,
        User::ROLE_TRAINING_OFFICE,
        User::ROLE_DEPARTMENT_STAFF,
    ];

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
        $users = User::query()
            ->with('department')
            ->orderBy('name')
            ->paginate(20);

        $departments = Department::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.index', [
            'users' => $users,
            'departments' => $departments,
            'creatableRoles' => self::CREATABLE_ROLES,
        ]);
    }

    /**
     * Tạo tài khoản với mật khẩu ngẫu nhiên rồi gửi email mời kích hoạt —
     * người dùng tự đặt mật khẩu lần đầu, Admin không bao giờ biết mật khẩu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::in(self::CREATABLE_ROLES)],
            'department_id' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_DEPARTMENT_STAFF),
                'nullable',
                'integer',
                'exists:departments,id',
            ],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.in' => 'Vai trò không hợp lệ.',
            'department_id.required' => 'Vui lòng chọn khoa cho nhân viên khoa.',
            'department_id.exists' => 'Khoa không hợp lệ.',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(40)),
                'role' => $validated['role'],
                'status' => User::STATUS_APPROVED,
                'department_id' => $validated['role'] === User::ROLE_DEPARTMENT_STAFF
                    ? $validated['department_id']
                    : null,
            ]);
        } catch (QueryException $e) {
            // Race condition hiếm gặp: 2 request cùng tạo trùng email vượt qua validate,
            // unique constraint ở DB chặn lại — trả về lỗi validate thân thiện thay vì 500.
            return back()->withInput()->withErrors([
                'email' => 'Không thể tạo tài khoản do email bị trùng. Vui lòng thử lại.',
            ]);
        }

        $sent = app(AccountInvitationService::class)->invite($user);
        app(InternalNotificationService::class)->notifyAccountCreated($user, $request->user());

        return redirect()->route('admin.index')->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Đã tạo tài khoản và gửi email mời kích hoạt tới ' . $user->email . '.'
                : 'Đã tạo tài khoản nhưng chưa gửi được email mời. Vui lòng dùng nút "Gửi lại email" để thử lại.'
        );
    }

    public function resendInvitation(Request $request, User $user)
    {
        if ($user->email_verified_at !== null) {
            return back()->with('error', 'Tài khoản này đã kích hoạt, không cần gửi lại email mời.');
        }

        $sent = app(AccountInvitationService::class)->resend($user);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? 'Đã gửi lại email mời kích hoạt tới ' . $user->email . '.'
                : 'Không gửi được email mời. Vui lòng kiểm tra cấu hình mail và thử lại.'
        );
    }

    public function lock(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Không thể tự khoá tài khoản của chính mình.');
        }

        $user->update(['status' => User::STATUS_LOCKED]);

        return back()->with('success', 'Đã khoá tài khoản ' . $user->email . '.');
    }

    public function unlock(Request $request, User $user)
    {
        $user->update(['status' => User::STATUS_APPROVED]);

        return back()->with('success', 'Đã mở khoá tài khoản ' . $user->email . '.');
    }
}
