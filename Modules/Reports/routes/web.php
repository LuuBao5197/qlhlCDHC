<?php

use Illuminate\Support\Facades\Route;
use Modules\Reports\Application\TeachingStatistics\TeachingStatisticsController;

Route::middleware('auth')->prefix('reports/teaching')->name('reports.teaching.')->group(function () {
    Route::get('/', [TeachingStatisticsController::class, 'overview'])->name('overview');
    Route::get('/teachers', [TeachingStatisticsController::class, 'teachers'])->name('teachers');
    Route::get('/quality', [TeachingStatisticsController::class, 'quality'])->name('quality');
    Route::get('/classes', [TeachingStatisticsController::class, 'classes'])->name('classes');
    Route::get('/departments', [TeachingStatisticsController::class, 'departments'])->name('departments');
});
