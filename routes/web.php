<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ManagementController;

Route::get('/', function () {
    return view('index.index');
});

// Auth routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.perform');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected redirect home
Route::middleware('auth')->get('/home', function () {
    return redirect()->route('schedule.index');
});

// Settings route
Route::middleware('auth')->get('/settings', function () {
    return view('settings.index');
})->name('settings');

// Management UI route
Route::middleware(['auth', 'management.access'])
    ->get('/management', [ManagementController::class, 'index'])
    ->name('management.index');

// Admin routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\AdminController::class, 'index'])->name('index');
    Route::post('/users/{id}/approve', [App\Http\Controllers\Admin\AdminController::class, 'approve'])->name('users.approve');
    Route::post('/users/{id}/reject', [App\Http\Controllers\Admin\AdminController::class, 'reject'])->name('users.reject');
});
