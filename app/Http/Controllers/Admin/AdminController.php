<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Modules\Training\Models\Department;

class AdminController extends Controller
{
    /**
     * Role được phép gán từ màn Admin. Teacher không nằm trong danh sách vì cần tạo kèm
     * hồ sơ giảng viên từ màn Quản lý giáo viên; admin/student không thuộc nghiệp vụ cấp phát.
     * `department_head`/`training_office_head` là role "tier cao" — khi chọn sẽ tự kèm role
     * nền tương ứng (`department_staff`/`training_office`) để không mất quyền thao tác cơ bản.
     */
    private const ASSIGNABLE_ROLES = [
        Role::LEADERSHIP,
        Role::TRAINING_OFFICE,
        Role::TRAINING_OFFICE_HEAD,
        Role::DEPARTMENT_STAFF,
        Role::DEPARTMENT_HEAD,
    ];

    private const IMPLIED_ROLES = [
        Role::DEPARTMENT_HEAD => Role::DEPARTMENT_STAFF,
        Role::TRAINING_OFFICE_HEAD => Role::TRAINING_OFFICE,
    ];

    private const DEPARTMENT_SCOPED_ROLES = [
        Role::DEPARTMENT_STAFF,
        Role::DEPARTMENT_HEAD,
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

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'department_id', 'role', 'status']);

        $users = User::query()
            ->with(['department', 'roles'])
            ->when($filters['q'] ?? null, function ($query, string $q): void {
                $query->where(function ($query) use ($q): void {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->when($filters['department_id'] ?? null, function ($query, string $departmentId): void {
                $query->where('department_id', $departmentId);
            })
            ->when($filters['role'] ?? null, function ($query, string $role): void {
                $query->whereHas('roles', fn ($query) => $query->where('slug', $role));
            })
            ->when($filters['status'] ?? null, function ($query, string $status): void {
                $query->where('status', $status);
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::query()->orderBy('name')->get(['id', 'name']);

        $loginBackgroundPath = Setting::get(Setting::KEY_LOGIN_BACKGROUND_PATH);

        $roleOrder = array_flip(self::ASSIGNABLE_ROLES);
        $assignableRoles = Role::query()
            ->whereIn('slug', self::ASSIGNABLE_ROLES)
            ->get(['id', 'slug', 'name'])
            ->sortBy(fn (Role $role): int => $roleOrder[$role->slug] ?? PHP_INT_MAX)
            ->values();

        $passwordResetRequests = PasswordResetRequest::query()
            ->with('user')
            ->where('status', PasswordResetRequest::STATUS_PENDING)
            ->orderBy('requested_at')
            ->get();

        $filterableRoles = Role::query()->orderBy('name')->get(['id', 'slug', 'name']);

        return view('admin.index', [
            'users' => $users,
            'filters' => $filters,
            'departments' => $departments,
            'assignableRoles' => $assignableRoles,
            'filterableRoles' => $filterableRoles,
            'userStatuses' => [
                User::STATUS_PENDING => 'Pending',
                User::STATUS_APPROVED => 'Approved',
                User::STATUS_REJECTED => 'Rejected',
                User::STATUS_LOCKED => 'Đã khoá',
            ],
            'impliedRoles' => self::IMPLIED_ROLES,
            'departmentScopedRoles' => self::DEPARTMENT_SCOPED_ROLES,
            'loginBackgroundUrl' => $loginBackgroundPath ? Storage::disk('public')->url($loginBackgroundPath) : null,
            'passwordResetRequests' => $passwordResetRequests,
            'defaultPassword' => config('accounts.default_password'),
        ]);
    }

    /**
     * Tạo tài khoản với mật khẩu mặc định của hệ thống (không gửi email — mạng nội
     * bộ) — Admin cấp trực tiếp mật khẩu này cho người dùng, tài khoản bắt buộc
     * đổi mật khẩu ngay lần đăng nhập đầu tiên.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(self::ASSIGNABLE_ROLES)],
            'department_id' => [
                Rule::requiredIf(fn () => $this->rolesNeedDepartment((array) $request->input('roles', []))),
                'nullable',
                'integer',
                'exists:departments,id',
            ],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'roles.required' => 'Vui lòng chọn ít nhất một vai trò.',
            'roles.*.in' => 'Vai trò không hợp lệ.',
            'department_id.required' => 'Vui lòng chọn khoa cho vai trò Giáo vụ/Chủ nhiệm khoa.',
            'department_id.exists' => 'Khoa không hợp lệ.',
        ]);

        $slugs = $this->withImpliedRoles($validated['roles']);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make((string) config('accounts.default_password')),
                'must_change_password' => true,
                'role' => null,
                'position' => null,
                'status' => User::STATUS_APPROVED,
                'email_verified_at' => now(),
                'department_id' => $this->rolesNeedDepartment($slugs) ? $validated['department_id'] : null,
            ]);
        } catch (QueryException $e) {
            // Race condition hiếm gặp: 2 request cùng tạo trùng email vượt qua validate,
            // unique constraint ở DB chặn lại — trả về lỗi validate thân thiện thay vì 500.
            return back()->withInput()->withErrors([
                'email' => 'Không thể tạo tài khoản do email bị trùng. Vui lòng thử lại.',
            ]);
        }

        $roleIds = Role::query()->whereIn('slug', $slugs)->pluck('id');
        $user->roles()->attach($roleIds);

        app(InternalNotificationService::class)->notifyAccountCreated($user, $request->user());

        return redirect()->route('admin.index')->with(
            'success',
            'Đã tạo tài khoản ' . $user->email . ' với mật khẩu mặc định "' . config('accounts.default_password') . '". '
                . 'Vui lòng cung cấp mật khẩu này cho người dùng — hệ thống sẽ bắt buộc đổi mật khẩu ngay lần đăng nhập đầu tiên.'
        );
    }

    /**
     * @param array<int, string> $slugs
     * @return list<string>
     */
    private function withImpliedRoles(array $slugs): array
    {
        foreach ($slugs as $slug) {
            if (isset(self::IMPLIED_ROLES[$slug])) {
                $slugs[] = self::IMPLIED_ROLES[$slug];
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @param array<int, string> $slugs
     */
    private function rolesNeedDepartment(array $slugs): bool
    {
        return array_intersect($slugs, self::DEPARTMENT_SCOPED_ROLES) !== [];
    }

    /**
     * Duyệt yêu cầu quên mật khẩu — chỉ từ lúc này token của yêu cầu mới cho phép
     * người dùng vào form đặt mật khẩu mới (xem ResetPasswordHandler).
     */
    public function approvePasswordResetRequest(Request $request, PasswordResetRequest $passwordResetRequest)
    {
        if (! $passwordResetRequest->isPending()) {
            return back()->with('error', 'Yêu cầu này đã được xử lý trước đó.');
        }

        $passwordResetRequest->forceFill([
            'status' => PasswordResetRequest::STATUS_APPROVED,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
        ])->save();

        return back()->with('success', 'Đã duyệt yêu cầu đặt lại mật khẩu cho ' . $passwordResetRequest->user->email . '.');
    }

    public function rejectPasswordResetRequest(Request $request, PasswordResetRequest $passwordResetRequest)
    {
        if (! $passwordResetRequest->isPending()) {
            return back()->with('error', 'Yêu cầu này đã được xử lý trước đó.');
        }

        $passwordResetRequest->forceFill([
            'status' => PasswordResetRequest::STATUS_REJECTED,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
        ])->save();

        return back()->with('success', 'Đã từ chối yêu cầu đặt lại mật khẩu của ' . $passwordResetRequest->user->email . '.');
    }

    public function updateRoles(Request $request, User $user)
    {
        $validated = $request->validate([
            'roles' => ['array'],
            'roles.*' => [Rule::in(self::ASSIGNABLE_ROLES)],
        ], [
            'roles.*.in' => 'Vai trò không hợp lệ.',
        ]);

        $slugs = $this->withImpliedRoles($validated['roles'] ?? []);

        if ($this->rolesNeedDepartment($slugs) && $user->department_id === null) {
            return back()->with('error', 'Tài khoản này chưa thuộc khoa nào — vui lòng gán khoa trước khi thêm vai trò Giáo vụ/Chủ nhiệm khoa.');
        }

        $roleIds = Role::query()->whereIn('slug', $slugs)->pluck('id');

        DB::transaction(function () use ($user, $roleIds): void {
            // Chỉ đồng bộ trong phạm vi các role mà Admin panel được phép gán — không đụng
            // tới role ngoài phạm vi này (vd. teacher/student/admin) nếu user đang giữ.
            $user->roles()->detach(
                Role::query()->whereIn('slug', self::ASSIGNABLE_ROLES)->pluck('id')
            );
            $user->roles()->attach($roleIds);
        });

        return back()->with('success', 'Đã cập nhật vai trò cho ' . $user->email . '.');
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

    public function updateLoginBackground(Request $request)
    {
        $validated = $request->validate([
            'background' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'background.required' => 'Vui lòng chọn ảnh nền.',
            'background.image' => 'File tải lên phải là ảnh.',
            'background.mimes' => 'Ảnh phải có định dạng JPG, PNG hoặc WEBP.',
            'background.max' => 'Ảnh không được lớn hơn 5MB.',
        ]);

        $previousPath = Setting::get(Setting::KEY_LOGIN_BACKGROUND_PATH);

        $path = $request->file('background')->store('branding', 'public');
        Setting::set(Setting::KEY_LOGIN_BACKGROUND_PATH, $path);

        if ($previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        return back()->with('success', 'Đã cập nhật ảnh nền trang đăng nhập.');
    }

    public function resetLoginBackground(Request $request)
    {
        $previousPath = Setting::get(Setting::KEY_LOGIN_BACKGROUND_PATH);

        if ($previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        Setting::set(Setting::KEY_LOGIN_BACKGROUND_PATH, null);

        return back()->with('success', 'Đã khôi phục ảnh nền mặc định cho trang đăng nhập.');
    }
}
