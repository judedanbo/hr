<?php

use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\UnitController;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/auth.php';

// Application Routes
// person
Route::controller(PersonController::class)->group(function() {
    Route::get('/person', 'index')->name('person.index');
    Route::get('/person/{person}', 'show')->name('person.show');
});
// Institution
Route::controller(InstitutionController::class)->group(function() {
    Route::get('/institution', 'index')->name('institution.index');
    Route::get('/institution/{institution}', 'show')->name('institution.show');
    Route::get('/institution/{institution}/staff', 'staff')->name('institution.staff');
});
// unit
Route::controller(UnitController::class)->group(function() {
    Route::get('/unit', 'index')->name('unit.index');
    Route::get('/unit/{unit}', 'show')->name('unit.show');
});


// staff
Route::controller(UnitController::class)->group(function() {
    Route::get('/unit', 'index')->name('unit.index');
    Route::get('/unit/{unit}', 'show')->name('unit.show');
});


// test

Route::get('/test', function(){
    return Institution::query()
    ->where('id', 21)
    ->with(['departments'=> function($q){

        $q->countSubs();
    }])
    ->get();
});