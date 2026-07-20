<?php

namespace App\Application\Account\ChangePassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ChangePasswordController extends Controller
{
    public function __construct(
        private ChangePasswordHandler $handler
    ) {}

    public function __invoke(ChangePasswordRequest $request): RedirectResponse
    {
        $this->handler->handle($request);

        return back()->with('success', 'Đổi mật khẩu thành công.');
    }
}
