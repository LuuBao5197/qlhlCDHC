<?php

namespace App\Application\Auth\ResetPassword;

use App\Models\PasswordResetRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordHandler
{
    /**
     * Đặt mật khẩu mới cho yêu cầu đã được Admin duyệt. Token chỉ dùng được một
     * lần — đánh dấu `used_at` ngay sau khi đặt mật khẩu thành công. Người dùng
     * tự chọn mật khẩu ở bước này nên không cần bắt đổi mật khẩu lại lần sau.
     */
    public function handle(ResetPasswordRequest $request): void
    {
        $resetRequest = PasswordResetRequest::query()
            ->where('token', $request->validated('token'))
            ->first();

        if ($resetRequest === null) {
            throw ValidationException::withMessages([
                'token' => ['Liên kết đặt lại mật khẩu không hợp lệ.'],
            ]);
        }

        if ($resetRequest->isUsed()) {
            throw ValidationException::withMessages([
                'token' => ['Liên kết này đã được sử dụng. Vui lòng gửi yêu cầu mới nếu cần đặt lại mật khẩu.'],
            ]);
        }

        if (! $resetRequest->isApproved()) {
            throw ValidationException::withMessages([
                'token' => ['Yêu cầu đặt lại mật khẩu chưa được Admin duyệt.'],
            ]);
        }

        $resetRequest->user->forceFill([
            'password' => Hash::make((string) $request->validated('password')),
            'must_change_password' => false,
        ])->save();

        $resetRequest->forceFill(['used_at' => now()])->save();
    }
}
