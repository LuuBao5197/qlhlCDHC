<?php

namespace App\Application\Account\UpdateProfile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class UpdateProfileController extends Controller
{
    public function __construct(
        private UpdateProfileHandler $handler
    ) {}

    public function __invoke(UpdateProfileRequest $request): RedirectResponse
    {
        $this->handler->handle($request);

        return back()->with('success', 'Cập nhật hồ sơ thành công.');
    }
}
