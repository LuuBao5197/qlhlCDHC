<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Application\GetSchedule\GetScheduleController;
use Modules\Schedule\Application\GetScheduleSemester\GetScheduleSemesterController;
use Modules\Schedule\Application\CreateSchedule\CreateScheduleController;
use Modules\Schedule\Application\UpdateSchedule\UpdateScheduleController;
use Modules\Schedule\Application\DeleteSchedule\DeleteScheduleController;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;


Route::middleware(['auth', 'verified'])->group(function () {
    // List schedules
    Route::get('/schedules', GetScheduleController::class)->name('schedule.index');

    // Create schedule
    Route::get('/schedules/create', [CreateScheduleController::class, 'showForm'])
        ->name('schedule.create');
    Route::post('/schedules', CreateScheduleController::class)->name('schedule.store');

    // Show schedule
    Route::get('/schedules/{id}', [GetScheduleController::class, 'show'])
        ->name('schedule.show');

    // Update schedule
    Route::get('/schedules/{id}/edit', [UpdateScheduleController::class, 'showForm'])
        ->name('schedule.edit');
    Route::put('/schedules/{id}', UpdateScheduleController::class)->name('schedule.update');

    // Delete schedule
    Route::delete('/schedules/{id}', DeleteScheduleController::class)->name('schedule.destroy');

    // Semester schedule
    Route::get('/semester/{semester?}/{year?}/{className?}', GetScheduleSemesterController::class)
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
Route::get('/semester-schedule/{semester?}/{year?}/{className?}', GetScheduleSemesterController::class)
    ->name('schedule.semester.public');
