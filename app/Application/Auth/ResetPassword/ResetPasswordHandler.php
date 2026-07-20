<?php

namespace App\Application\Auth\ResetPassword;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordHandler
{
    public function handle(ResetPasswordRequest $request): void
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = Password::reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
            ]);
            $user->setRememberToken(Str::random(60));
            $user->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [$this->friendlyMessage($status)],
            ]);
        }
    }

    private function friendlyMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Không tìm thấy tài khoản với email này.',
            Password::INVALID_TOKEN => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu gửi lại.',
            Password::RESET_THROTTLED => 'Bạn vừa thực hiện thao tác này. Vui lòng thử lại sau ít phút.',
            default => 'Không thể đặt lại mật khẩu. Vui lòng thử lại.',
        };
    }
}
