<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Http\Controllers\ScheduleController;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;


Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('schedules', ScheduleController::class)->names('schedule');
    Route::get('/semester/{semester?}/{year?}/{className?}', [ScheduleController::class, 'semester'])
        ->name('schedule.semester');
});

Route::get('/schedule', function () {
    $schedule = MonthlySchedule::with('scheduleSlots')
        ->where('class_name', 'CD01')
        ->where('month', 3)
        ->where('year', 2026)
        ->first();

    return view('schedule::schedule', compact('schedule')); // phai dung namespace de truy cap view cua module
});

/**
 * Public semester schedule route
 * Usage: /semester-schedule/{semester}/{year}/{className}
 * Example: /semester-schedule/1/2026/CD01
 */
Route::get('/semester-schedule/{semester?}/{year?}/{className?}', [ScheduleController::class, 'semester'])
    ->name('schedule.semester.public');
