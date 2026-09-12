<?php

namespace App\Http\Middleware;

use App\Support\ActiveRoleContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chia sẻ role đang "active" (chỉ để đổi giao diện/menu) cho mọi view — không chặn request nào.
 */
class SetActiveRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        View::share('activeRole', $user !== null ? ActiveRoleContext::current($user) : null);

        return $next($request);
    }
}
