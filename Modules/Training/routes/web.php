<?php

use App\Application\Auth\ActivateAccount\ActivateAccountController;
use App\Application\Auth\ActivateAccount\ShowActivateAccountController;
use Illuminate\Support\Facades\Route;
use Modules\Training\Application\Management\Departments\ManageDepartmentsController;
use Modules\Training\Application\Management\Rooms\ManageRoomsController;
use Modules\Training\Application\Management\Students\ManageStudentsController;
use Modules\Training\Application\Management\SubjectLessons\ManageSubjectLessonsController;
use Modules\Training\Application\Management\Subjects\ManageSubjectsController;
use Modules\Training\Application\Management\Teachers\ManageTeachersController;
use Modules\Training\Application\Management\TrainingClasses\ManageTrainingClassesController;
use Modules\Training\Application\Management\TrainingPrograms\ManageTrainingProgramsController;
use Modules\Training\Application\Management\TrainingBatches\ManageTrainingBatchesController;
use Modules\Training\Application\TeacherEvaluation\GetTeacherDailyLog\GetTeacherDailyLogController;
use Modules\Training\Application\TeacherEvaluation\GetTeacherDailyLog\GetTeacherDailyLogEditController;
use Modules\Training\Application\TeacherEvaluation\GetTeacherSlotEvaluation\GetTeacherSlotEvaluationController;
use Modules\Training\Application\TeacherEvaluation\SubmitDailyLog\SubmitDailyLogController;
use Modules\Training\Application\TeacherEvaluation\SubmitTeacherSlotEvaluation\SubmitTeacherSlotEvaluationController;

// Alias tương thích ngược: link kích hoạt trong các email đã gửi trước đây trỏ về
// /teacher-accounts/activate — luồng kích hoạt chính hiện ở /account/activate (routes/web.php).
Route::prefix('teacher-accounts/activate')->name('teacher-accounts.activate.')->group(function () {
    Route::get('/', ShowActivateAccountController::class)->name('show');
    Route::post('/', ActivateAccountController::class)->name('submit');
});

Route::middleware(['auth', 'management.access'])->prefix('management')->name('management.')->group(function () {
    Route::apiResource('training-programs', ManageTrainingProgramsController::class);
    Route::apiResource('training-batches', ManageTrainingBatchesController::class);
    Route::apiResource('departments', ManageDepartmentsController::class);
    Route::apiResource('training-classes', ManageTrainingClassesController::class);
    Route::apiResource('teachers', ManageTeachersController::class);
    Route::apiResource('rooms', ManageRoomsController::class);
    Route::apiResource('subjects', ManageSubjectsController::class);
    Route::apiResource('subject-lessons', ManageSubjectLessonsController::class);
    Route::get('students/import-template', [ManageStudentsController::class, 'downloadImportTemplate'])
        ->name('students.import-template');
    Route::post('students/import', [ManageStudentsController::class, 'import'])
        ->name('students.import');
    Route::apiResource('students', ManageStudentsController::class);
});

/*
|--------------------------------------------------------------------------
| Duty Log Routes — Nhật ký trực ban huấn luyện
|--------------------------------------------------------------------------
|
| Nhân viên phòng đào tạo / trực ban ghi nhận toàn bộ tình hình trong ngày:
|   - Quân số, vắng, nội dung từng tiết của tất cả lớp
|   - Nhận xét tổng hợp hoạt động huấn luyện (Phần 2)
| Truy cập: /duty-log?date=YYYY-MM-DD
|
*/
Route::middleware(['auth'])->prefix('duty-log')->name('duty-log.')->group(function () {
    // Trang xem nhật ký (chỉ đọc)
    Route::get('/', GetTeacherDailyLogController::class)
        ->name('index');

    // Trang nhập/chỉnh sửa nhận xét tổng hợp (phần 2)
    Route::get('/edit', GetTeacherDailyLogEditController::class)
        ->name('edit');

    // Lưu nhận xét tổng hợp (phần 2)
    Route::post('/submit', SubmitDailyLogController::class)
        ->name('submit');
});

/*
|--------------------------------------------------------------------------
| Teacher Slot Evaluation Routes — Đánh giá tiết học
|--------------------------------------------------------------------------
|
| Chỉ giáo viên phụ trách tiết học mới được đánh giá tiết đó.
|
*/
Route::middleware(['auth'])->prefix('teacher-slot-evaluations')->name('teacher-slot-evaluations.')->group(function () {
    Route::get('/', GetTeacherSlotEvaluationController::class)
        ->name('index');

    Route::post('/submit', SubmitTeacherSlotEvaluationController::class)
        ->name('submit');
});
