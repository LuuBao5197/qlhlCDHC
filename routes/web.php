<?php

use Illuminate\Support\Facades\Route;
use App\Application\Account\ChangePassword\ChangePasswordController;
use App\Application\Account\UpdateProfile\UpdateProfileController;
use App\Application\Auth\ForgotPassword\ForgotPasswordController;
use App\Application\Auth\ForgotPassword\ShowForgotPasswordController;
use App\Application\Auth\ResetPassword\ResetPasswordController;
use App\Application\Auth\ResetPassword\ShowResetPasswordController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ManagementController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Quên mật khẩu: hệ thống chạy nội bộ, không gửi email — người dùng gửi yêu cầu,
// Admin duyệt/từ chối trong màn Quản trị. Token của yêu cầu chỉ cho vào form đặt
// mật khẩu mới sau khi được duyệt (xem PasswordResetRequest, ResetPasswordHandler).
Route::get('/forgot-password', ShowForgotPasswordController::class)->name('password.request');
Route::post('/forgot-password', ForgotPasswordController::class)->name('password.email');
Route::get('/reset-password/{token}', ShowResetPasswordController::class)->name('password.reset.status');
Route::post('/reset-password', ResetPasswordController::class)->name('password.update');

// Protected redirect home
Route::middleware('auth')->get('/home', function () {
    $user = request()->user();

    if ($user->isTrainingOffice() || $user->isAdmin()) {
        return redirect()->route('duty-log.index');
    }

    if ($user->isTeacher()) {
        return redirect()->route('teacher-slot-evaluations.index');
    }

    return redirect()->route('schedule.index');
});

// Settings route
Route::middleware('auth')->group(function () {
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings');

    Route::prefix('account')->name('account.')->group(function () {
        Route::put('/profile', UpdateProfileController::class)->name('profile.update');
        Route::put('/password', ChangePasswordController::class)->name('password.update');
        Route::post('/active-role', [App\Http\Controllers\ActiveRoleController::class, 'update'])->name('active-role.update');
    });
});

Route::middleware('auth')->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('read-all');
});

// Management UI route
Route::middleware(['auth', 'management.access'])
    ->get('/management', [ManagementController::class, 'index'])
    ->name('management.index');

// Admin routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'index'])->name('index');
    Route::post('/users', [App\Http\Controllers\Admin\AdminController::class, 'store'])->name('users.store');
    Route::post('/users/{user}/lock', [App\Http\Controllers\Admin\AdminController::class, 'lock'])->name('users.lock');
    Route::post('/users/{user}/unlock', [App\Http\Controllers\Admin\AdminController::class, 'unlock'])->name('users.unlock');
    Route::post('/users/{user}/roles', [App\Http\Controllers\Admin\AdminController::class, 'updateRoles'])->name('users.update-roles');
    Route::post('/password-reset-requests/{passwordResetRequest}/approve', [App\Http\Controllers\Admin\AdminController::class, 'approvePasswordResetRequest'])->name('password-reset-requests.approve');
    Route::post('/password-reset-requests/{passwordResetRequest}/reject', [App\Http\Controllers\Admin\AdminController::class, 'rejectPasswordResetRequest'])->name('password-reset-requests.reject');
    Route::post('/settings/login-background', [App\Http\Controllers\Admin\AdminController::class, 'updateLoginBackground'])->name('settings.login-background.update');
    Route::post('/settings/login-background/reset', [App\Http\Controllers\Admin\AdminController::class, 'resetLoginBackground'])->name('settings.login-background.reset');
});
