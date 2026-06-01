<?php

use App\Http\Controllers\StaffSearchOptionsController;
use App\Http\Controllers\StaffStatisticsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:user:read'])->get('/user', function (Request $request) {
    return $request->user();
});

// Staff search filter options
Route::middleware(['auth:sanctum', 'abilities:staff-search:read'])->prefix('staff-search')->group(function () {
    Route::get('/options', [StaffSearchOptionsController::class, 'index'])->name('api.staff-search.options');
    Route::get('/job-categories', [StaffSearchOptionsController::class, 'jobCategories'])->name('api.staff-search.job-categories');
    Route::get('/jobs', [StaffSearchOptionsController::class, 'jobs'])->name('api.staff-search.jobs');
    Route::get('/units', [StaffSearchOptionsController::class, 'units'])->name('api.staff-search.units');
    Route::get('/departments', [StaffSearchOptionsController::class, 'departments'])->name('api.staff-search.departments');
});

// Staff statistics
Route::middleware(['auth:sanctum', 'abilities:staff-statistics:read'])
    ->get('/staff-statistics', [StaffStatisticsController::class, 'index'])
    ->name('api.staff-statistics');
