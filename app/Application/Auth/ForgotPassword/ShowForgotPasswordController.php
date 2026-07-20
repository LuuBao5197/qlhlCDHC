<?php

namespace App\Application\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ShowForgotPasswordController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.forgot-password');
    }
}
