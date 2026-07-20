<?php

namespace App\Application\Auth\ActivateAccount;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ActivateAccountController extends Controller
{
    public function __construct(
        private ActivateAccountHandler $handler
    ) {}

    public function __invoke(ActivateAccountRequest $request): RedirectResponse
    {
        try {
            $this->handler->handle($request);
        } catch (ValidationException $e) {
            return back()
                ->withInput($request->only('email', 'token'))
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('login')
            ->with('success', 'Kích hoạt tài khoản thành công! Vui lòng đăng nhập bằng mật khẩu vừa thiết lập.');
    }
}
