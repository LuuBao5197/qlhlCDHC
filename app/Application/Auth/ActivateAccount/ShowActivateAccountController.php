<?php

namespace App\Application\Auth\ActivateAccount;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ShowActivateAccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $email = (string) $request->query('email', '');
        $token = (string) $request->query('token', '');

        $user = $email !== '' ? User::query()->where('email', $email)->first() : null;
        $isValid = $user !== null && $token !== '' && Password::broker()->tokenExists($user, $token);

        return view('auth.activate-account', [
            'email' => $email,
            'token' => $token,
            'isValid' => $isValid,
        ]);
    }
}
