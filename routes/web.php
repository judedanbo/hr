<?php

use App\Http\Controllers\DependentController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\InstitutionPersonController;
use App\Http\Controllers\Reports\RecruitmentController;
use App\Http\Controllers\UnitController;
use App\Models\Dependent;
use App\Models\Institution;
use App\Models\Unit;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__ . '/auth.php';

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'year' => Date('Y'),
        'logo' => asset('images/inner-logo.png')
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('institution.show', [30305]);
})->middleware(['auth', 'verified'])->name('dashboard');
// })->name('dashboard');


// Application Routes
// person
Route::controller(PersonController::class)->middleware(['auth'])->group(function () {
    Route::get('/person', 'index')->name('person.index');
    Route::get('/person/{person}', 'show')->name('person.show');
    Route::post('/person/{person}/contact', 'addContact')->name('person.contact.create');
    Route::post('/person/{person}/address', 'addAddress')->name('person.address.create');
    Route::delete('/person/{person}/address/{address}', 'deleteAddress')->name('person.address.delete');
});
// Institution
Route::controller(InstitutionController::class)->middleware(['auth'])->group(function () {
    Route::get('/institution', 'index')->name('institution.index');
    Route::get('/institution/{institution}', 'show')->name('institution.show');
    Route::get('/institution/{institution}/staff', 'staffs')->name('institution.staffs');
    Route::get('/institution/{institution}/staff/{staff}', 'staff')->name('institution.staff');
    Route::get('/institution/{institution}/ranks', 'jobs')->name('institution.jobs');
});
// unit
Route::controller(UnitController::class)->middleware(['auth'])->group(function () {
    Route::get('/unit', 'index')->name('unit.index');
    Route::get('/unit/{unit}', 'show')->name('unit.show');
});


// staff
Route::controller(InstitutionPersonController::class)->middleware(['auth'])->group(function () {
    Route::get('/staff', 'index')->name('staff.index');
    Route::get('/staff/{staff}', 'show')->name('staff.show');
    Route::post('/staff/{staff}/dependent', 'createDependent')->name('staff.dependent.create');
    Route::delete('/staff/{staff}/dependent/{dependent}', 'deleteDependent')->name('staff.dependent.delete');
});


// dependent
Route::controller(DependentController::class)->middleware(['auth'])->group(function () {
    // Route::get('/dependent', 'index')->name('dependent.index');
    // Route::get('/dependent/create', 'create')->name('dependent.create');
    // Route::get('/dependent/{dependent}', 'show')->name('dependent.show');
    Route::delete('/dependent/{dependent}', 'destroy')->name('dependent.delete');
});


Route::controller(JobController::class)->middleware(['auth'])->group(function () {
    Route::get('/rank', 'index')->name('job.index');
    Route::get('/rank/{job}', 'show')->name('job.show');
});

// report
Route::get('/report', [RecruitmentController::class, 'index'])->middleware(['auth'])->name('report.index');
Route::get('/report/recruitment', [RecruitmentController::class, 'recruitment'])->middleware(['auth'])->name('report.recruitment');
Route::get('/report/recruitment/chart', [RecruitmentController::class, 'recruitmentChart'])->middleware(['auth'])->name('report.recruitment.chart');
Route::get('/report/recruitment/details', [RecruitmentController::class, 'detail'])->middleware(['auth'])->name('report.recruitment.details');

Route::get('report/recruitment/export/all', [RecruitmentController::class, 'exportAll'])->middleware(['auth'])->name('report.recruitment.export-data');
Route::get('report/recruitment/export/summary', [RecruitmentController::class, 'exportSummary'])->middleware(['auth'])->name('report.recruitment.export-summary');