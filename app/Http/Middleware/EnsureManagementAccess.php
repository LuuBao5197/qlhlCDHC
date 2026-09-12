<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagementAccess
{
    /**
     * Allow only admin/leadership users to access management routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! ($user->isAdmin() || $user->isLeadership())) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You are not authorized to access this resource.',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
