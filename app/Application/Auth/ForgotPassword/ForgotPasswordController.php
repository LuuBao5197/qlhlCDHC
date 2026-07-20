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
        $notThrottled = $this->handler->handle($request);

        if (! $notThrottled) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Bạn vừa yêu cầu đặt lại mật khẩu. Vui lòng thử lại sau ít phút.']);
        }

        return back()->with(
            'success',
            'Nếu email này tồn tại trong hệ thống, chúng tôi đã gửi một liên kết đặt lại mật khẩu. Vui lòng kiểm tra hộp thư của bạn.'
        );
    }
}
