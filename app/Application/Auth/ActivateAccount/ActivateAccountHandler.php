<?php

namespace App\Application\Auth\ActivateAccount;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ActivateAccountHandler
{
    public function handle(ActivateAccountRequest $request): User
    {
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $status = Password::broker()->reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [$this->friendlyMessage($status)],
            ]);
        }

        return User::where('email', $credentials['email'])->firstOrFail();
    }

    private function friendlyMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Không tìm thấy tài khoản với email này.',
            Password::INVALID_TOKEN => 'Liên kết kích hoạt không hợp lệ hoặc đã hết hạn. Vui lòng liên hệ Admin để được gửi lại email.',
            Password::RESET_THROTTLED => 'Bạn vừa thực hiện thao tác này. Vui lòng thử lại sau ít phút.',
            default => 'Không thể kích hoạt tài khoản. Vui lòng thử lại.',
        };
    }
}
