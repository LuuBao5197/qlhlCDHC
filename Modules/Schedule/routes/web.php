<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Application\GetSchedule\GetScheduleController;
use Modules\Schedule\Application\GetScheduleSemester\GetScheduleSemesterController;
use Modules\Schedule\Application\CreateScheduleSemester\CreateScheduleSemesterController;
use Modules\Schedule\Application\UpdateSchedule\UpdateScheduleController;
use Modules\Schedule\Application\DeleteSchedule\DeleteScheduleController;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleController;
use Modules\Schedule\Application\ReviewChangeRequest\ReviewChangeRequestController;
use Modules\Schedule\Application\ReviewMonthlySchedule\ReviewMonthlyScheduleController;
use Modules\Schedule\Application\SubmitMonthlyScheduleToLeadership\SubmitMonthlyScheduleToLeadershipController;
use Modules\Schedule\Application\SubmitMonthlyScheduleToTrainingOffice\SubmitMonthlyScheduleToTrainingOfficeController;
use Modules\Schedule\Application\SubmitSemesterPlan\SubmitSemesterPlanController;
use Modules\Schedule\Application\UpdateScheduleSemester\UpdateScheduleSemesterController;
use Modules\Schedule\Application\DeleteScheduleSemester\DeleteScheduleSemesterController;
use Modules\Schedule\Application\InitializeMonthlySchedule\InitializeMonthlyScheduleController;
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;


Route::middleware(['auth', 'verified'])->group(function () {
    // List schedules
    Route::get('/schedules', GetScheduleController::class)->name('schedule.index');

    // Create schedule
    Route::get('/schedules/create', [CreateScheduleSemesterController::class, 'showForm'])
        ->name('schedule.create');
    Route::get('/schedules/import-template', [CreateScheduleSemesterController::class, 'downloadImportTemplate'])
        ->name('schedule.import-template');
    Route::post('/schedules', CreateScheduleSemesterController::class)->name('schedule.store');

    // Show schedule
    Route::get('/schedules/{id}', [GetScheduleController::class, 'show'])
        ->name('schedule.show');

    // Update schedule
    // Route::get('/schedules/{id}/edit', [UpdateScheduleController::class, 'showForm'])
    //     ->name('schedule.edit');
    // Route::put('/schedules/{id}', UpdateScheduleController::class)->name('schedule.update');

    Route::get('/schedules/{id}/edit', [UpdateScheduleSemesterController::class, 'showForm'])
        ->name('schedule.edit');
    Route::put('/schedules/{id}', UpdateScheduleSemesterController::class)->name('schedule.update');

    // Delete schedule semester
    Route::delete('/schedules/{id}', DeleteScheduleSemesterController::class)->name('schedule.destroy');
    // Delete schedule
    // Route::delete('/schedules/{id}', DeleteScheduleController::class)->name('schedule.destroy');
    Route::post('/schedules/{id}/submit', SubmitSemesterPlanController::class)->name('schedule.submit');

    // Training office review flow
    Route::get('/monthly-schedules/{id}/assignment', [AssignMonthlyScheduleController::class, 'showForm'])
        ->name('monthly-schedule.assignment');
    Route::post('/monthly-schedules/{id}/assignment', AssignMonthlyScheduleController::class)
        ->name('monthly-schedule.assignment.save');
    Route::post('/monthly-schedules/{id}/submit-to-training', SubmitMonthlyScheduleToTrainingOfficeController::class)
        ->name('monthly-schedule.submit-training');

    Route::post('/monthly-schedules/{id}/review', ReviewMonthlyScheduleController::class)
        ->name('monthly-schedule.review');
    Route::post('/monthly-schedules/{id}/submit-to-leadership', SubmitMonthlyScheduleToLeadershipController::class)
        ->name('monthly-schedule.submit-leadership');
    Route::post('/change-requests/{id}/review', ReviewChangeRequestController::class)
        ->name('change-request.review');

    // Semester schedule
    Route::get('/semester/{semester?}/{year?}/{className?}', GetScheduleSemesterController::class)
        ->name('schedule.semester');

    // Initialize monthly schedule
    Route::get('/monthly-schedules/initialize', [InitializeMonthlyScheduleController::class, 'showForm'])
        ->name('monthly-schedule.initialize.form');
    Route::post('/monthly-schedules/initialize', InitializeMonthlyScheduleController::class)
        ->name('monthly-schedule.initialize');
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
