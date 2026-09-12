<?php

namespace App\Application\Account\ChangePassword;

use Illuminate\Support\Facades\Hash;

class ChangePasswordHandler
{
    public function handle(ChangePasswordRequest $request): void
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make((string) $request->validated('password')),
            'must_change_password' => false,
        ])->save();
    }
}
