<?php

use Illuminate\Support\Facades\Route;
use Modules\Schedule\Application\GetSchedule\GetScheduleController;
use Modules\Schedule\Application\GetScheduleSemester\GetScheduleSemesterController;
use Modules\Schedule\Application\CreateScheduleSemester\CreateScheduleSemesterController;
use Modules\Schedule\Application\CreateScheduleSemester\PreviewScheduleSemesterController;
use Modules\Schedule\Application\UpdateSchedule\UpdateScheduleController;
use Modules\Schedule\Application\DeleteSchedule\DeleteScheduleController;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleController;
use Modules\Schedule\Application\AssignMonthlySchedule\AssignMonthlyScheduleMergeController;
use Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch\DepartmentMonthlyAssignmentBatchController;
use Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch\DepartmentMonthlyAssignmentBatchReviewController;
use Modules\Schedule\Application\TrainingOfficeReviewDepartmentMonthlyAssignmentBatch\TrainingOfficeReviewDepartmentMonthlyAssignmentBatchController;
use Modules\Schedule\Application\MonthlyAssignmentDossier\MonthlyAssignmentDossierController;
use Modules\Schedule\Application\SubmitMonthlyAssignmentDossier\SubmitMonthlyAssignmentDossierController;
use Modules\Schedule\Application\TrainingOfficeReviewMonthlyAssignmentDossier\TrainingOfficeReviewMonthlyAssignmentDossierController;
use Modules\Schedule\Application\LeadershipReviewMonthlyAssignmentDossier\LeadershipReviewMonthlyAssignmentDossierController;
use Modules\Schedule\Application\TeachingSupportRequest\TeachingSupportRequestController;
use Modules\Schedule\Application\TeachingSupportChangeRequest\TeachingSupportChangeRequestController;
use Modules\Schedule\Application\CreateChangeRequest\CreateChangeRequestController;
use Modules\Schedule\Application\CreateChangeRequest\CreateChangeRequestPageController;
use Modules\Schedule\Application\ReviewChangeRequest\ReviewChangeRequestController;
use Modules\Schedule\Application\SubmitSemesterPlan\SubmitSemesterPlanController;
use Modules\Schedule\Application\TrainingOfficeReviewSemesterPlan\TrainingOfficeReviewSemesterPlanController;
use Modules\Schedule\Application\LeadershipReviewSemesterPlan\LeadershipReviewSemesterPlanController;
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
    Route::post('/schedules/semester-preview', PreviewScheduleSemesterController::class)
        ->name('schedule.semester-preview');

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
    Route::post('/schedules/{id}/training-office-review', TrainingOfficeReviewSemesterPlanController::class)
        ->name('schedule.training-office-review');
    Route::post('/schedules/{id}/leadership-review', LeadershipReviewSemesterPlanController::class)
        ->name('schedule.leadership-review');

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
    Route::post('/monthly-schedules/{id}/teaching-support-requests', [TeachingSupportRequestController::class, 'store'])
        ->name('teaching-support-requests.store');
    Route::get('/monthly-schedules/{id}/teaching-support-requests/modal', [TeachingSupportRequestController::class, 'monthlyAssignmentModal'])
        ->name('monthly-schedule.teaching-support-requests.modal');
    Route::get('/department-monthly-assignment-batches', [DepartmentMonthlyAssignmentBatchController::class, 'index'])
        ->name('department-monthly-assignment-batches.index');
    Route::get('/department-monthly-assignment-batches/{id}', [DepartmentMonthlyAssignmentBatchController::class, 'show'])
        ->name('department-monthly-assignment-batches.show');
    Route::post('/department-monthly-assignment-batches/{id}/approve', [DepartmentMonthlyAssignmentBatchReviewController::class, 'approve'])
        ->name('department-monthly-assignment-batches.approve');
    Route::post('/department-monthly-assignment-batches/{id}/return', [DepartmentMonthlyAssignmentBatchReviewController::class, 'returnBatch'])
        ->name('department-monthly-assignment-batches.return');
    Route::post('/department-monthly-assignment-batches/{id}/training-office-review', TrainingOfficeReviewDepartmentMonthlyAssignmentBatchController::class)
        ->name('department-monthly-assignment-batches.training-office-review');

    // Monthly assignment dossier (PDT tong hop -> Lanh dao PDT -> BGH)
    Route::get('/monthly-assignment-dossiers', [MonthlyAssignmentDossierController::class, 'index'])
        ->name('monthly-assignment-dossiers.index');
    Route::post('/monthly-assignment-dossiers', [MonthlyAssignmentDossierController::class, 'store'])
        ->name('monthly-assignment-dossiers.store');
    Route::get('/monthly-assignment-dossiers/{id}', [MonthlyAssignmentDossierController::class, 'show'])
        ->name('monthly-assignment-dossiers.show');
    Route::post('/monthly-assignment-dossiers/{id}/submit', SubmitMonthlyAssignmentDossierController::class)
        ->name('monthly-assignment-dossiers.submit');
    Route::post('/monthly-assignment-dossiers/{id}/training-office-review', TrainingOfficeReviewMonthlyAssignmentDossierController::class)
        ->name('monthly-assignment-dossiers.training-office-review');
    Route::post('/monthly-assignment-dossiers/{id}/leadership-review', LeadershipReviewMonthlyAssignmentDossierController::class)
        ->name('monthly-assignment-dossiers.leadership-review');

    Route::get('/teaching-support-requests', [TeachingSupportRequestController::class, 'index'])
        ->name('teaching-support-requests.index');
    Route::get('/teaching-support-requests/inbox', [TeachingSupportRequestController::class, 'inbox'])
        ->name('teaching-support-requests.inbox');
    Route::get('/teaching-support-requests/{id}', [TeachingSupportRequestController::class, 'show'])
        ->name('teaching-support-requests.show');
    Route::post('/teaching-support-requests/{id}/review', [TeachingSupportRequestController::class, 'review'])
        ->name('teaching-support-requests.review');
    Route::post('/teaching-support-requests/{id}/withdraw', [TeachingSupportRequestController::class, 'withdraw'])
        ->name('teaching-support-requests.withdraw');
    Route::post('/teaching-support-requests/{id}/confirm', [TeachingSupportRequestController::class, 'confirm'])
        ->name('teaching-support-requests.confirm');
    Route::post('/teaching-support-request-items/{id}/assign', [TeachingSupportRequestController::class, 'assignItem'])
        ->name('teaching-support-request-items.assign');
    Route::get('/teaching-support-requests/{id}/change-requests/create', [TeachingSupportChangeRequestController::class, 'create'])
        ->name('teaching-support-change-requests.create');
    Route::post('/teaching-support-requests/{id}/change-requests', [TeachingSupportChangeRequestController::class, 'store'])
        ->name('teaching-support-change-requests.store');
    Route::get('/teaching-support-change-requests/{id}', [TeachingSupportChangeRequestController::class, 'show'])
        ->name('teaching-support-change-requests.show');
    Route::post('/teaching-support-change-requests/{id}/review', [TeachingSupportChangeRequestController::class, 'review'])
        ->name('teaching-support-change-requests.review');

    Route::post('/change-requests/{id}/review', ReviewChangeRequestController::class)
        ->name('change-request.review');
    Route::get('/change-requests/create', CreateChangeRequestPageController::class)
        ->name('change-request.create');
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
