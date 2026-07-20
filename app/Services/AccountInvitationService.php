<?php

namespace App\Services;

use App\Mail\AccountInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

/**
 * Gửi email mời kích hoạt tài khoản (đặt mật khẩu lần đầu) cho user do Admin tạo,
 * dùng chung cho mọi role. Token dùng bảng password_reset_tokens chuẩn của Laravel.
 */
class AccountInvitationService
{
    /**
     * Nhãn role chèn vào wording email; role không có trong map dùng wording chung.
     * Teacher giữ nguyên wording cũ ("Kích hoạt tài khoản giáo viên").
     */
    private const ROLE_TITLES = [
        User::ROLE_TEACHER => 'giáo viên',
    ];

    /**
     * Tạo token đặt mật khẩu và gửi email mời kích hoạt.
     *
     * Lỗi gửi mail (SMTP timeout, sai cấu hình...) chỉ ghi log và trả về false —
     * không ném exception để tài khoản vừa tạo không bị mất; Admin có thể gửi lại sau.
     */
    public function invite(User $user): bool
    {
        try {
            $token = Password::broker()->createToken($user);

            Mail::to($user->email)->send(new AccountInvitationMail($user, $token, $this->roleTitle($user)));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Không gửi được email mời kích hoạt tài khoản.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Gửi lại email mời với token mới. Token cũ (nếu còn) bị vô hiệu hóa,
     * link trong email trước đó sẽ không dùng được nữa.
     */
    public function resend(User $user): bool
    {
        return $this->invite($user);
    }

    private function roleTitle(User $user): ?string
    {
        return self::ROLE_TITLES[$user->role] ?? null;
    }
}
