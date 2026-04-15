<?php

use Illuminate\Support\Facades\Route;
use Modules\Training\Application\Management\Departments\ManageDepartmentsController;
use Modules\Training\Application\Management\Rooms\ManageRoomsController;
use Modules\Training\Application\Management\Students\ManageStudentsController;
use Modules\Training\Application\Management\SubjectLessons\ManageSubjectLessonsController;
use Modules\Training\Application\Management\Subjects\ManageSubjectsController;
use Modules\Training\Application\Management\Teachers\ManageTeachersController;
use Modules\Training\Application\Management\TrainingClasses\ManageTrainingClassesController;

Route::middleware(['auth', 'management.access'])->prefix('management')->name('management.')->group(function () {
    Route::apiResource('departments', ManageDepartmentsController::class);
    Route::apiResource('training-classes', ManageTrainingClassesController::class);
    Route::apiResource('teachers', ManageTeachersController::class);
    Route::apiResource('rooms', ManageRoomsController::class);
    Route::apiResource('subjects', ManageSubjectsController::class);
    Route::apiResource('subject-lessons', ManageSubjectLessonsController::class);
    Route::apiResource('students', ManageStudentsController::class);
});