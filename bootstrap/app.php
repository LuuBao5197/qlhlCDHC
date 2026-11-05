<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'management.access' => \App\Http\Middleware\EnsureManagementAccess::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureAccountActive::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureMustChangePassword::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\SetActiveRole::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            $message = 'File tải lên quá lớn. Vui lòng chọn file nhỏ hơn.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return back()->withErrors(['file' => $message]);
        });
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Phiên đăng nhập đã hết hạn hoặc tài khoản được đăng nhập ở nơi khác. Vui lòng đăng nhập lại.',
            ]);
        });
    })->create();
