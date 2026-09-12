<?php

namespace App\Application\Auth\ResetPassword;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetRequest;
use Illuminate\Contracts\View\View;

class ShowResetPasswordController extends Controller
{
    /**
     * Trang trạng thái yêu cầu đặt lại mật khẩu. Người dùng bookmark link này sau
     * khi gửi yêu cầu quên mật khẩu — trang tự hiển thị theo trạng thái hiện tại:
     * đang chờ duyệt, bị từ chối, hoặc form đặt mật khẩu mới khi Admin đã duyệt.
     */
    public function __invoke(string $token): View
    {
        $resetRequest = PasswordResetRequest::query()->where('token', $token)->first();

        return view('auth.reset-password', [
            'token' => $token,
            'resetRequest' => $resetRequest,
        ]);
    }
}
