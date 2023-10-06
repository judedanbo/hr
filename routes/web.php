<?php

use App\Enums\EmployeeStatusEnum;
use App\Enums\Nationality;
use App\Enums\NoteTypeEnum;
use App\Enums\StaffTypeEnum;
use App\Http\Controllers\ContactTypeController;
use App\Http\Controllers\DependentController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionPersonController;
use App\Http\Controllers\InstitutionRankController;
use App\Http\Controllers\InstitutionStatusController;
use App\Http\Controllers\JobCategoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\NationalityController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PersonAvatarController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonRolesController;
use App\Http\Controllers\PromoteStaffController;
use App\Http\Controllers\PromotionBatchController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionExportController;
use App\Http\Controllers\Reports\RecruitmentController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\StaffStatusController;
use App\Http\Controllers\StaffTypeController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UnitTypeController;
use App\Models\Contact;
use App\Models\Dependent;
use App\Models\Institution;
use App\Models\JobCategory;
use App\Models\Unit;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

require __DIR__ . '/auth.php';

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'year' => date('Y'),
        'logo' => asset('images/inner-logo.png'),
    ]);
});

Route::get('/dashboard', function () {
    return redirect()->route('institution.show', [1]);
})->middleware(['auth', 'verified'])->name('dashboard');
// })->name('dashboard');

// Application Routes
// person
Route::controller(PersonController::class)->middleware(['auth'])->group(function () {
    Route::get('/person', 'index')->name('person.index');
    Route::get('/person/{person}', 'show')->name('person.show');
    Route::post('/person', 'store')->name('person.store');
    Route::post('/person/{person}/contact', 'addContact')->name('person.contact.create');
    Route::post('/person/{person}/address', 'addAddress')->name('person.address.create');
    Route::delete('/person/{person}/address/{address}', 'deleteAddress')->name('person.address.delete');
    // Route::get('/person/avatar', 'avatar')->name('person.ava');
});
Route::get('person/{person}/avatar', [PersonAvatarController::class, 'index'])->name('person.avatar');
Route::get('person/{person}/roles', [PersonRolesController::class, 'show'])->name('person-roles.show');
// Institution
Route::controller(InstitutionController::class)->middleware(['auth'])->group(function () {
    Route::get('/institution', 'index')->name('institution.index');
    Route::get('/institution/create', 'create')->name('institution.create');
    Route::get('/institution/{institution}', 'show')->name('institution.show');
    Route::post('/institution', 'store')->name('institution.store');
    Route::patch('/institution/{institution}', 'update')->name('institution.update');
    Route::delete('/institution/{institution}', 'delete')->name('institution.delete');
    Route::get('/institution/{institution}/staff', 'staffs')->name('institution.staffs');
    Route::get('/institution/{institution}/staff/{staff}', 'staff')->name('institution.staff');
    Route::get('/institution/{institution}/ranks', 'jobs')->name('institution.jobs');
});
// InstitutionRankController::class;
Route::get('/institution/{institution}/ranks', [InstitutionRankController::class, 'index'])->middleware(['auth'])->name('institution.job-list');

Route::get('/institution/{institution}/units', function (Institution $institution) {
    $institution->load('allUnits');
    return $institution->allUnits->map(fn ($unit) => [
        'value' => $unit->id,
        'label' => $unit->name,
    ]);
})->middleware(['auth'])->name('institution.unit-list');
// unit
Route::get('/institution/{institution}/statuses', function (Institution $institution) {

    // return $institution->statuses;
    $statuses = null;
    foreach (EmployeeStatusEnum::cases() as $county) {
        $status = new \stdClass;
        $status->value = $county->value;
        $status->label = $county->label() . ' (' . $county->name . ')';
        $statuses[] = $status;
    }
    return $statuses;
})->name('institution.statuses');

Route::get('/institution/{institution}/staff-types', function (Institution $institution) {
    $types = null;
    foreach (StaffTypeEnum::cases() as $staffType) {
        $type = new \stdClass;
        $type->value = $staffType->value;
        $type->label = $staffType->label();
        $types[] = $type;
    }
    return $types;
})->name('institution.staff-types');

Route::controller(UnitController::class)->middleware(['auth'])->group(function () {
    Route::get('/unit', 'index')->name('unit.index');
    Route::post('/unit', 'store')->name('unit.store');
    Route::get('/unit/{unit}', 'show')->name('unit.show');
    Route::delete('/unit/{unit}', 'delete')->name('unit.delete');
    Route::patch('/unit/{unit}', 'update')->name('unit.update');
});

Route::controller(InstitutionStatusController::class)->middleware(['auth'])->group(function () {
    Route::get('/institution/{institution}/status', 'index')->name('institution-status.index');
    Route::get('/institution/{institution}/status/{status}', 'show')->name('institution-status.show');
});

// staff
Route::controller(InstitutionPersonController::class)->middleware(['auth'])->group(function () {
    Route::get('/staff', 'index')->name('staff.index');
    Route::get('/staff/create', 'create')->name('staff.create');
    Route::post('/staff', 'store')->name('staff.store');
    Route::get('/staff/{staff}', 'show')->name('staff.show');
    // Route::post('/staff/{staff}/promote', 'promote')->name('staff.promote');
    // Route::post('/staff/{staff}/promote', 'editPromote')->name('staff.promote.edit');
    Route::get('/staff/{staff}/edit', 'edit')->name('staff.edit');
    Route::get('/staff/{staff}/promotions', 'promotions')->name('staff.promotion-history');
    // Route::post('/staff/{staff}/transfer', 'transfer')->name('staff.transfer');
    Route::post('/staff/{staff}/dependent', 'createDependent')->name('staff.dependent.create');
    Route::delete('/staff/{staff}/dependent/{dependent}', 'deleteDependent')->name('staff.dependent.delete');
    Route::post('/staff/{staff}/write-note', 'writeNote')->name('staff.write-note');
});
//  promote
Route::controller(PromoteStaffController::class)->middleware(['auth'])->group(function () {
    Route::post('/staff/{staff}/promote', 'store')->name('staff.promote.store');
    Route::patch('/staff/{staff}/promote/{promotion}', 'update')->name('staff.promote.update');
    Route::delete('/staff/{staff}/promote/{job}', 'delete')->name('staff.promote.delete');
});

// transfer
Route::controller(TransferController::class)->middleware(['auth'])->group(function () {
    Route::post('/staff/{staff}/transfer', 'store')->name('staff.transfer.store');
    Route::patch('/staff/{staff}/transfer/{unit}', 'update')->name('staff.transfer.update');
    Route::delete('/staff/{staff}/transfer/{unit}', 'delete')->name('staff.transfer.delete');
});

// dependent
Route::controller(DependentController::class)->middleware(['auth'])->group(function () {
    // Route::get('/dependent', 'index')->name('dependent.index');
    // Route::get('/dependent/create', 'create')->name('dependent.create');
    // Route::get('/dependent/{dependent}', 'show')->name('dependent.show');
    Route::post('/dependent', 'store')->name('dependent.store');
    Route::delete('/dependent/{dependent}', 'destroy')->name('dependent.delete');
});

Route::controller(JobCategoryController::class)->middleware(['auth'])->group(function () {
    Route::get('/job-category', 'index')->name('job-category.index');
    Route::get('/job-category/create', 'create')->name('job-category.create');
    Route::post('/job-category', 'store')->name('job-category.store');
    Route::get('/job-category/{jobCategory}', 'show')->name('job-category.show');
    Route::patch('/job-category/{jobCategory}', 'update')->name('job-category.update');
    Route::delete('/job-category/{jobCategory}', 'delete')->name('job-category.delete');
});

Route::controller(JobController::class)->middleware(['auth'])->group(function () {
    Route::get('/rank', 'index')->name('job.index');
    Route::get('/rank/create', 'create')->name('job.create');
    Route::get('/rank/{job}', 'show')->name('job.show');
    Route::post('/rank', 'store')->name('job.store');
});

// report
Route::get('/report', [RecruitmentController::class, 'index'])->middleware(['auth'])->name('report.index');
Route::get('/report/recruitment', [RecruitmentController::class, 'recruitment'])->middleware(['auth'])->name('report.recruitment');
Route::get('/report/recruitment/chart', [RecruitmentController::class, 'recruitmentChart'])->middleware(['auth'])->name('report.recruitment.chart');
Route::get('/report/recruitment/details', [RecruitmentController::class, 'detail'])->middleware(['auth'])->name('report.recruitment.details');

Route::get('report/recruitment/export/all', [RecruitmentController::class, 'exportAll'])->middleware(['auth'])->name('report.recruitment.export-data');
Route::get('report/recruitment/export/summary', [RecruitmentController::class, 'exportSummary'])->middleware(['auth'])->name('report.recruitment.export-summary');

// promotion report
Route::get('/promotion/export', [PromotionExportController::class, 'show'])->middleware(['auth'])->name('export.promotion');

Route::get('/report/promotion', [PromotionController::class, 'index'])->middleware(['auth'])->name('report.promotion');
Route::get('/report/promotion/{year}', [PromotionBatchController::class, 'index'])->middleware(['auth'])->name('report.promotion.year');

Route::controller(PromotionController::class)->middleware(['auth'])->group(function () {
    Route::get('/past-promotion', 'index')->name('promotion.index');
    Route::get('/past-promotion/{year}', 'show')->name('promotion.show');
    Route::get('/past-promotion/{year}/rank', 'byRanks')->name('promotion.ranks');
    Route::get('/past-promotion/{promotion}/export', 'export')->name('promotion.export');
});

Route::controller(PromotionBatchController::class)->middleware(['auth'])->group(function () {
    Route::get('/next-promotions', 'index')->name('promotion.batch.index');
    Route::get('/next-promotions/{year}', 'show')->name('promotion.batch.show');
});

Route::controller(QualificationController::class)->middleware(['auth'])->group(function () {
    Route::get('/qualification', 'index')->name('qualification.index');
    Route::post('/qualification', 'store')->name('qualification.store');
    Route::patch('/qualification/{qualification}', 'update')->name('qualification.update');
    Route::delete('/qualification/{qualification}', 'delete')->name('qualification.delete');
});

Route::get('/contact-type', [ContactTypeController::class, 'index'])->middleware(['auth'])->name('contact-type.index');

Route::get('/marital-status', [MaritalStatusController::class, 'index'])->middleware(['auth'])->name('marital-status.index');
Route::get('/gender', [GenderController::class, 'index'])->middleware(['auth'])->name('gender.index');

Route::get('/nationality', [NationalityController::class, 'index'])->middleware(['auth'])->name('nationality.index');

Route::post('staff-status.save', [StaffStatusController::class, 'store'])->middleware(['auth'])->name('staff-status.save');

Route::post('staff-type.save', [StaffTypeController::class, 'store'])->middleware(['auth'])->name('staff-type.save');

Route::get('/unit-type', [UnitTypeController::class, 'index'])->middleware(['auth'])->name('unit-type.index');

Route::controller(NoteController::class)->middleware(['auth'])->group(function () {
    Route::get('/notes', 'index')->name('notes.index');
    Route::get('/notes/create', 'create')->name('notes.create');
    Route::post('/notes', 'store')->name('notes.store');
    Route::get('/notes/{note}', 'show')->name('notes.show');
    Route::get('/notes/{note}/edit', 'edit')->name('notes.edit');
    Route::patch('/notes/{note}', 'update')->name('notes.update');
    Route::delete('/notes/{note}', 'delete')->name('notes.delete');
});

Route::get('/note-types', function () {
    foreach (NoteTypeEnum::cases() as $type) {
        $types[] = [
            'value' => $type->value,
            'label' => $type->label(),
        ];
    }
    return $types;
    // return NoteTypeEnum::acasll()->map(fn ($type) => [
    //     'value' => $type->id,
    //     'label' => $type->name,
    // ]);
})->middleware(['auth'])->name('note-types');
