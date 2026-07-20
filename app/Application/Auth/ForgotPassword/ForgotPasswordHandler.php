<?php

namespace App\Application\Auth\ForgotPassword;

use Illuminate\Support\Facades\Password;

class ForgotPasswordHandler
{
    /**
     * Gửi email đặt lại mật khẩu nếu email tồn tại. Trả về false chỉ khi bị throttle,
     * mọi trường hợp khác (kể cả email không tồn tại) đều coi là "thành công" ở tầng
     * response để tránh lộ thông tin email nào đã đăng ký trong hệ thống (user enumeration).
     */
    public function handle(ForgotPasswordRequest $request): bool
    {
        $status = Password::sendResetLink($request->only('email'));

        return $status !== Password::RESET_THROTTLED;
    }
}
