<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_STUDENT,
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
