<?php

use Illuminate\Support\Facades\Route;
use App\Application\Account\ChangePassword\ChangePasswordController;
use App\Application\Account\UpdateProfile\UpdateProfileController;
use App\Application\Auth\ActivateAccount\ActivateAccountController;
use App\Application\Auth\ActivateAccount\ShowActivateAccountController;
use App\Application\Auth\ForgotPassword\ForgotPasswordController;
use App\Application\Auth\ForgotPassword\ShowForgotPasswordController;
use App\Application\Auth\ResetPassword\ResetPasswordController;
use App\Application\Auth\ResetPassword\ShowResetPasswordController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ManagementController;

Route::get('/', function () {
    return view('index.index');
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Quên mật khẩu / đặt lại mật khẩu (tên route giữ theo quy ước password broker mặc định của Laravel)
Route::get('/forgot-password', ShowForgotPasswordController::class)->name('password.request');
Route::post('/forgot-password', ForgotPasswordController::class)->name('password.email');
Route::get('/reset-password', ShowResetPasswordController::class)->name('password.reset');
Route::post('/reset-password', ResetPasswordController::class)->name('password.update');

// Kích hoạt tài khoản (thiết lập mật khẩu lần đầu) — dùng chung cho mọi role,
// người dùng truy cập qua link trong email mời do Admin gửi khi tạo tài khoản.
Route::get('/account/activate', ShowActivateAccountController::class)->name('account.activate.show');
Route::post('/account/activate', ActivateAccountController::class)->name('account.activate.submit');

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
    Route::post('/users/{user}/resend-invitation', [App\Http\Controllers\Admin\AdminController::class, 'resendInvitation'])->name('users.resend-invitation');
});
