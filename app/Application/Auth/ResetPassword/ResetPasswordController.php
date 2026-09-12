<?php

namespace App\Application\Auth\ResetPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function __construct(
        private ResetPasswordHandler $handler
    ) {}

    public function __invoke(ResetPasswordRequest $request): RedirectResponse
    {
        try {
            $this->handler->handle($request);
        } catch (ValidationException $e) {
            return back()
                ->withInput($request->only('token'))
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('login')
            ->with('success', 'Đặt lại mật khẩu thành công! Vui lòng đăng nhập bằng mật khẩu mới.');
    }
}
