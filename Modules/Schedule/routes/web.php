<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Application\GetSchedule\GetScheduleController;
use Modules\Schedule\Application\GetScheduleSemester\GetScheduleSemesterController;
use Modules\Schedule\Application\CreateScheduleSemester\CreateScheduleSemesterController;
use Modules\Schedule\Application\UpdateSchedule\UpdateScheduleController;
use Modules\Schedule\Application\DeleteSchedule\DeleteScheduleController;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleController;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleMergeController;
use Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch\DepartmentMonthlyAssignmentBatchController;
use Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch\DepartmentMonthlyAssignmentBatchReviewController;
use Modules\Schedule\Application\CreateChangeRequest\CreateChangeRequestController;
use Modules\Schedule\Application\ReviewChangeRequest\ReviewChangeRequestController;
use Modules\Schedule\Application\SubmitSemesterPlan\SubmitSemesterPlanController;
use Modules\Schedule\Application\UpdateScheduleSemester\UpdateScheduleSemesterController;
use Modules\Schedule\Application\DeleteScheduleSemester\DeleteScheduleSemesterController;
use Modules\Schedule\Application\InitializeMonthlySchedule\InitializeMonthlyScheduleController;
use Modules\Schedule\Application\CreateHolidayRescheduleRequest\CreateHolidayRescheduleRequestController;
use Modules\Schedule\Application\CreateHolidayCalendar\CreateHolidayCalendarController;
use Modules\Schedule\Application\UpdateHolidayCalendar\UpdateHolidayCalendarController;
use Modules\Schedule\Application\DeleteHolidayCalendar\DeleteHolidayCalendarController;
use Modules\Schedule\Application\ManageSemesterEvents\ManageSemesterEventsController;
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
    Route::get('/monthly-schedules/{id}/slots/{slotId}/merge-candidates', [AssignMonthlyScheduleMergeController::class, 'mergeCandidates'])
        ->name('monthly-schedule.assignment.merge-candidates');
    Route::post('/monthly-schedules/{id}/slots/{slotId}/merge', [AssignMonthlyScheduleMergeController::class, 'merge'])
        ->name('monthly-schedule.assignment.merge');
    Route::delete('/monthly-schedules/{id}/slot-groups/{groupId}/split', [AssignMonthlyScheduleMergeController::class, 'split'])
        ->name('monthly-schedule.assignment.split');

    // Aggregate department monthly assignment batch workflow
    Route::post('/monthly-schedules/{id}/department-monthly-assignment-batches/submit', [DepartmentMonthlyAssignmentBatchController::class, 'submit'])
        ->name('department-monthly-assignment-batches.submit');
    Route::get('/department-monthly-assignment-batches', [DepartmentMonthlyAssignmentBatchController::class, 'index'])
        ->name('department-monthly-assignment-batches.index');
    Route::get('/department-monthly-assignment-batches/{id}', [DepartmentMonthlyAssignmentBatchController::class, 'show'])
        ->name('department-monthly-assignment-batches.show');
    Route::post('/department-monthly-assignment-batches/{id}/approve', [DepartmentMonthlyAssignmentBatchReviewController::class, 'approve'])
        ->name('department-monthly-assignment-batches.approve');
    Route::post('/department-monthly-assignment-batches/{id}/return', [DepartmentMonthlyAssignmentBatchReviewController::class, 'returnBatch'])
        ->name('department-monthly-assignment-batches.return');

    Route::post('/change-requests/{id}/review', ReviewChangeRequestController::class)
        ->name('change-request.review');
    Route::post('/change-requests', CreateChangeRequestController::class)
        ->name('change-request.store');
    Route::post('/change-requests/holiday-reschedule', CreateHolidayRescheduleRequestController::class)
        ->name('change-request.holiday-reschedule.store');

    Route::post('/holiday-calendars', CreateHolidayCalendarController::class)
        ->name('holiday-calendar.store');
    Route::put('/holiday-calendars/{id}', UpdateHolidayCalendarController::class)
        ->name('holiday-calendar.update');
    Route::delete('/holiday-calendars/{id}', DeleteHolidayCalendarController::class)
        ->name('holiday-calendar.destroy');

    Route::get('/schedules/{id}/semester-events', [ManageSemesterEventsController::class, 'index'])
        ->name('schedule.semester-events.index');
    Route::post('/schedules/{id}/semester-events', [ManageSemesterEventsController::class, 'store'])
        ->name('schedule.semester-events.store');
    Route::put('/schedules/{id}/semester-events/{eventId}', [ManageSemesterEventsController::class, 'update'])
        ->name('schedule.semester-events.update');
    Route::delete('/schedules/{id}/semester-events/{eventId}', [ManageSemesterEventsController::class, 'destroy'])
        ->name('schedule.semester-events.destroy');

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
