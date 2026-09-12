<?php

namespace App\Application\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private ForgotPasswordHandler $handler
    ) {}

    public function __invoke(ForgotPasswordRequest $request): RedirectResponse
    {
        $token = $this->handler->handle($request);

        if ($token === null) {
            return back()->with(
                'success',
                'Nếu email này tồn tại trong hệ thống, yêu cầu đặt lại mật khẩu đã được gửi tới Admin để duyệt.'
            );
        }

        return redirect()->route('password.reset.status', ['token' => $token]);
    }
}
