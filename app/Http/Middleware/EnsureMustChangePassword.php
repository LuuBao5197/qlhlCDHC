<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMustChangePassword
{
    /**
     * Tài khoản vừa được Admin cấp mật khẩu mặc định (must_change_password = true)
     * không được thao tác bất kỳ chức năng nào khác cho tới khi đổi mật khẩu ở
     * trang Cài đặt — chỉ trang này, cập nhật hồ sơ đi kèm, và đăng xuất được phép.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTE_NAMES = [
        'settings',
        'account.password.update',
        'account.profile.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustChangePassword() && ! in_array($request->route()?->getName(), self::ALLOWED_ROUTE_NAMES, true)) {
            return redirect()->route('settings')->with(
                'warning',
                'Bạn phải đổi mật khẩu trước khi tiếp tục sử dụng hệ thống.'
            );
        }

        return $next($request);
    }
}
