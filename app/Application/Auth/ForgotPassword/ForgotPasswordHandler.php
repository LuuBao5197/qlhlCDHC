<?php

namespace App\Application\Auth\ForgotPassword;

use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\InternalNotificationService;
use Illuminate\Support\Str;

class ForgotPasswordHandler
{
    /**
     * Hệ thống chạy nội bộ, không gửi email — tạo yêu cầu đặt lại mật khẩu và báo
     * cho Admin duyệt qua thông báo trong hệ thống. Trả về token của yêu cầu (dùng
     * để chuyển hướng người dùng tới trang trạng thái), hoặc null nếu email không
     * tồn tại (tránh lộ thông tin email nào đã đăng ký — user enumeration).
     */
    public function handle(ForgotPasswordRequest $request): ?string
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user === null) {
            return null;
        }

        // Đã có yêu cầu đang chờ/đã duyệt còn hiệu lực — trả lại token cũ thay vì
        // tạo mới, tránh spam thông báo cho Admin mỗi lần người dùng bấm lại nút gửi.
        $existing = $user->passwordResetRequests()
            ->whereIn('status', [PasswordResetRequest::STATUS_PENDING, PasswordResetRequest::STATUS_APPROVED])
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if ($existing !== null) {
            return $existing->token;
        }

        $resetRequest = $user->passwordResetRequests()->create([
            'token' => Str::random(64),
            'status' => PasswordResetRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        app(InternalNotificationService::class)->notifyPasswordResetRequested($resetRequest);

        return $resetRequest->token;
    }
}
