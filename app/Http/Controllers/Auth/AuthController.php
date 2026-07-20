<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            if ($user->isLocked()) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Tài khoản của bạn đã bị khoá. Vui lòng liên hệ admin.',
                ]);
            }

            $request->session()->regenerate();
            return redirect()->intended(route('schedule.index'));
        }

        return back()->withErrors([
            'email' => 'Email hoặc mật khẩu không đúng.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
