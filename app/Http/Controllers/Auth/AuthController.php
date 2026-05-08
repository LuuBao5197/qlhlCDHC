<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Modules\Training\Models\Department;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            /** @var User|null $user */
            $user = Auth::user();

            if (! $user) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Không thể xác thực người dùng.',
                ]);
            }

            if ($user->isPending()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tài khoản của bạn đang chờ phê duyệt từ admin.',
                ]);
            }

            if ($user->isRejected()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tài khoản của bạn đã bị từ chối.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('schedule.index'));
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ])->withInput();
    }

    public function showRegisterForm()
    {
        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('auth.register', compact('departments'));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => ['required', 'string', Rule::in([
                User::ROLE_TEACHER,
                User::ROLE_DEPARTMENT_STAFF,
                User::ROLE_TRAINING_OFFICE,
            ])],
            'employee_code' => [
                'nullable',
                'string',
                'max:255',
                'required_if:role,' . User::ROLE_TEACHER,
                Rule::unique('users', 'employee_code'),
            ],
            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
                'required_if:role,' . User::ROLE_TEACHER . ',' . User::ROLE_DEPARTMENT_STAFF,
            ],
        ], [
            'department_id.required_if' => 'Vui lòng chọn khoa cho vai trò đã chọn.',
            'employee_code.required_if' => 'Giáo viên phải nhập mã giáo viên khi đăng ký.',
            'employee_code.unique' => 'Mã giáo viên đã được sử dụng bởi tài khoản khác.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => null,
            'requested_role' => $validated['role'],
            'department_id' => null,
            'requested_department_id' => in_array($validated['role'], [User::ROLE_TEACHER, User::ROLE_DEPARTMENT_STAFF], true)
                ? (int) $validated['department_id']
                : null,
            'employee_code' => $validated['role'] === User::ROLE_TEACHER
                ? trim((string) $validated['employee_code'])
                : null,
            'status' => User::STATUS_PENDING,
        ]);

        // Don't auto-login pending users
        // Auth::login($user);

        return redirect()->route('login')
            ->with('success', 'Đăng ký thành công! Tài khoản của bạn đang chờ phê duyệt từ admin.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
