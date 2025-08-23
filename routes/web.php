<?php

use App\Enums\CountryEnum;
use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use App\Enums\EmployeeStatusEnum;
use App\Enums\NoteTypeEnum;
use App\Enums\StaffTypeEnum;
use App\Http\Controllers\AgeController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\CategoryRanks;
use App\Http\Controllers\ContactTypeController;
use App\Http\Controllers\DependentController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\InstitutionPersonController;
use App\Http\Controllers\InstitutionRankController;
use App\Http\Controllers\InstitutionStatusController;
use App\Http\Controllers\JobCategoryController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\NationalityController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PersonAvatarController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonRolesController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PromoteAllStaffController;
use App\Http\Controllers\PromoteStaffController;
use App\Http\Controllers\PromotionBatchController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionExportController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\QualificationDocumentController;
use App\Http\Controllers\RankStaffController;
use App\Http\Controllers\RankStaffStatsController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\Reports\RecruitmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SeparationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffReportController;
use App\Http\Controllers\StaffStatusController;
use App\Http\Controllers\StaffTypeController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StaffListController;
use App\Models\Dependent;
use App\Models\District;
use App\Models\Institution;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Office;
use App\Models\Person;
use App\Models\Unit;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Contracts\Role;

require __DIR__ . '/auth.php';

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'year' => date('Y'),
        'logo' => asset('images/inner-logo.png'),
    ]);
});

Route::controller(ChangePasswordController::class)->middleware(['auth'])->group(function () {
    Route::get('/change-password', 'index')->name('change-password.index');
    Route::post('/change-password', 'store')->name('change-password.store');
});
// Route::get('/change-password', [ChangePasswordController::class, 'index'])->middleware(['auth'])->name('change-password');

Route::controller(UserController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/user', 'index')->name('user.index');
    Route::get('/user/{user}', 'show')->name('user.show');
    Route::post('/user/', 'store')->name('user.store');
    Route::patch('/user/{user}', 'update')->name('user.update');
    Route::delete('/user/{user}', 'delete')->name('user.delete');
    Route::post('/user/{user}', 'resetPassword')->name('user.reset-password');
    Route::get('/user/{user}/roles', 'roles')->name('user.roles');
    Route::get('/user/{user}/permissions', 'permissions')->name('user.permissions');
    Route::get('/user/{user}/roles-permissions', 'rolesPermissions')->name('user.roles-permissions');
});
Route::controller(RoleController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/role', 'index')->name('role.index');
    Route::get('/roles-list', 'list')->name('roles.list');
    Route::get('/role/{role}', 'show')->name('role.show');
    // Route::patch('/user/{user}', 'update')->name('user.update');
    // Route::delete('/user', 'delete')->name('user.delete');
    Route::post('/user/{user}/add-role', 'addRole')->name('user.add.roles');
    Route::patch('/user/{user}/revoke-role', 'revokeRole')->name('user.revoke.roles');
});
Route::controller(PermissionController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/permission', 'index')->name('permission.index');
    Route::get('/permission-list', 'list')->name('permission.list');
    // Route::get('/user/{user}', 'show')->name('user.show');
    // Route::patch('/user/{user}', 'update')->name('user.update');
    // Route::delete('/user', 'delete')->name('user.delete');
    Route::post('/user/{user}/add-permission', 'addPermission')->name('user.add.permissions');
    Route::patch('/user/{user}/revoke-permission', 'revokePermission')->name('user.revoke.permissions');
});

Route::get('/dashboard', function () {
    request()->session()->reflash();
    $error = request()->session()->get('error');
    // dd($error);
    if (auth()->user()->hasRole('super-administrator')) {
        if (Institution::count() < 1) {
            session()->flash('error', 'Please create institution to proceed');
            return redirect()->route('institution.index');
        }
        return redirect()->route('institution.show', [1]);
    }
    if (auth()->user()->hasRole('staff')) {
        // dd(auth()->user()->person);
        if (auth()->user()->person) {
            return redirect()
                ->route('staff.show', [auth()->user()->person->institution->first()->staff->id]);
        }
    }
    // TODO: design custom page for users without staff information
    return auth()->logout();
})->middleware(['auth', 'password_changed', 'verified'])->name('dashboard');
// })->name('dashboard');

// Application Routes
// person
Route::controller(PersonController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/person', 'index')->name('person.index');
    Route::get('/person/{person}', 'show')->name('person.show');
    Route::get('/person/{person}/edit', 'edit')->name('person.edit');
    Route::patch('/person/{person}', 'update')->name('person.update');
    Route::post('/person', 'store')->name('person.store');
    Route::post('/person/{person}/contact', 'addContact')->name('person.contact.create');
    Route::post('/person/{person}/identity', 'addIdentity')->name('person.identity.create');
    Route::post('/person/{person}/identity/{identity}', 'updateIdentity')->name('person.identity.update');
    Route::delete('/person/{person}/identity/{identity}', 'deleteIdentity')->name('person.identity.delete');
    Route::post('/person/{person}/contact/{contact}', 'updateContact')->name('person.contact.update');
    Route::delete('/person/{person}/contact/{contact}', 'deleteContact')->name('person.contact.delete');
    Route::post('/person/{person}/address', 'addAddress')->name('person.address.create');
    Route::delete('/person/{person}/address/{address}', 'deleteAddress')->name('person.address.delete');
    // Route::get('/person/avatar', 'avatar')->name('person.ava');
});
Route::get('person/{person}/avatar', [PersonAvatarController::class, 'index'])->name('person.avatar');
Route::get('person/{person}/roles', [PersonRolesController::class, 'show'])->name('person-roles.show');
Route::get('person/{person}/dependent', [PersonRolesController::class, 'dependent'])->name('person-roles.dependent');
Route::post('person/{person}/avatar', [PersonAvatarController::class, 'update'])->middleware(['auth', 'password_changed'])->name('person.avatar.update');
Route::delete('person/{person}/avatar/delete', [PersonAvatarController::class, 'delete'])->middleware(['auth', 'password_changed'])->name('person.avatar.delete');

// Institution
Route::controller(InstitutionController::class)->middleware(['auth', 'password_changed'])->group(function () {
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
Route::get('/institution/{institution}/ranks', [InstitutionRankController::class, 'index'])->middleware(['auth', 'password_changed'])->name('institution.job-list');

Route::get('/institution/{institution}/units', function (Institution $institution) {
    $institution->load('allUnits');

    return $institution->allUnits->map(fn($unit) => [
        'value' => $unit->id,
        'label' => $unit->name,
    ]);
})->middleware(['auth', 'password_changed'])->name('institution.unit-list');
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

Route::controller(UnitController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/unit', 'index')->name('unit.index');
    Route::post('/unit', 'store')->name('unit.store');
    Route::get('/unit/{unit}', 'show')->name('unit.show');
    Route::delete('/unit/{unit}', 'delete')->name('unit.delete');
    Route::patch('/unit/{unit}', 'update')->name('unit.update');
    Route::get('/unit/{unit}/details', 'details')->name('unit.details');
    Route::post('/unit/{unit}/details', 'addSub')->name('unit.add-sub');
    Route::get('/unit/{unit}/download', 'download')->name('export.unit.staff');
});

Route::get('/unit-list', function () {
    // return 'all units';
    $units = Unit::all();

    return $units->map(fn($unit) => [
        'value' => $unit->id,
        'label' => $unit->name,
    ]);
})->middleware(['auth', 'password_changed'])->name('units.list');

// Route::get('/units-stats', function () {
// //   $stats =  DB::table()
// })->middleware(['auth','password_changed'])->name('units.stats');

Route::controller(InstitutionStatusController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/institution/{institution}/status', 'index')->name('institution-status.index');
    Route::get('/institution/{institution}/status/{status}', 'show')->name('institution-status.show');
});

// staff
Route::controller(InstitutionPersonController::class)->middleware(['auth', 'password_changed', 'password_changed'])->group(function () {
    Route::get('/staff', 'index')->name('staff.index');
    Route::get('/staff/create', 'create')->name('staff.create');
    Route::post('/staff', 'store')->name('staff.store');
    Route::get('/staff/{staff}', 'show')->name('staff.show');
    Route::patch('/staff/{staff}', 'update')->name('staff.update');
    // Route::post('/staff/{staff}/promote', 'promote')->name('staff.promote');
    // Route::post('/staff/{staff}/promote', 'editPromote')->name('staff.promote.edit');
    Route::get('/staff/{staff}/edit', 'edit')->name('staff.edit');
    Route::get('/staff/{staff}/promotions', 'promotions')->name('staff.promotion-history');
    // Route::post('/staff/{staff}/transfer', 'transfer')->name('staff.transfer');
    Route::post('/staff/{staff}/dependent', 'createDependent')->name('staff.dependent.create');
    Route::delete('/staff/{staff}/dependent/{dependent}', 'deleteDependent')->name('staff.dependent.delete');
    Route::post('/staff/{staff}/write-note', 'writeNote')->name('staff.write-note');
    Route::post('/staff/{staff}/position', 'assignPosition')->name('staff.position.store');
    Route::patch('/staff/{staff}/position', 'updatePosition')->name('staff.position.update');
    Route::delete('/staff/{staff}/position', 'deletePosition')->withTrashed()->name('staff.position.delete');
});

// separation
Route::controller(SeparationController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/separation', 'index')->name('separation.index');
    Route::get('/separation/{staff}', 'show')->name('separation.show');
    // Route::delete('/staff/{staff}/separation/{separation}', 'delete')->name('staff.separation.delete');
});
//  promote
Route::controller(PromoteStaffController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::post('/staff/{staff}/promote', 'store')->name('staff.promote.store');
    Route::patch('/staff/{staff}/promote/{promotion}', 'update')->name('staff.promote.update');
    Route::delete('/staff/{staff}/promote/{job}', 'delete')->name('staff.promote.delete');
});
Route::post('/staff/promote-all', [PromoteAllStaffController::class, 'save'])->middleware(['auth', 'password_changed'])->name('rank-staff.promote-all');

// transfer
Route::controller(TransferController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::post('/staff/{staff}/transfer', 'store')->name('staff.transfer.store');
    Route::patch('/staff/{staff}/unit/{unit}', 'update')->name('staff.transfer.update');
    Route::delete('/staff/{staff}/transfer/{unit}', 'delete')->name('staff.transfer.delete');
    Route::patch('/staff/{staff}/transfer/{unit}', 'approve')->name('staff.transfer.approve');
});

// dependent
Route::controller(DependentController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/dependent', 'index')->name('dependent.index');
    // Route::get('/dependent/create', 'create')->name('dependent.create');
    // Route::get('/dependent/{dependent}', 'show')->name('dependent.show');
    Route::post('/dependent', 'store')->name('dependent.store');
    Route::post('/dependent/{dependent}', 'update')->name('dependent.update');
    Route::delete('/dependent/{dependent}', 'destroy')->name('dependent.delete');
});

Route::post('staff/{staff}/profile-image', [PersonAvatarController::class, 'store'])->middleware(['auth', 'password_changed'])->name('staff.profile-image.store');

Route::controller(JobCategoryController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/job-category', 'index')->name('job-category.index');
    Route::get('/job-category/summary', 'summary')->name('job-category.summary');
    Route::get('/job-category/create', 'create')->name('job-category.create');
    Route::post('/job-category', 'store')->name('job-category.store');
    Route::get('/job-category/{jobCategory}', 'show')->name('job-category.show');
    Route::patch('/job-category/{jobCategory}', 'update')->name('job-category.update');
    Route::delete('/job-category/{jobCategory}', 'delete')->name('job-category.delete');
});

Route::controller(CategoryRanks::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/category/{category}/ranks', 'show')->name('category-ranks.show');
});

Route::get('/rank/{rank}/staff', [RankStaffController::class, 'index'])->middleware(['auth', 'password_changed'])->name('rank-staff.index');
Route::get('/rank/{rank}/promote', [RankStaffController::class, 'promote'])->middleware(['auth', 'password_changed'])->name('rank-staff.promote');
Route::get('/rank/{rank}/active', [RankStaffController::class, 'active'])->middleware(['auth', 'password_changed'])->name('rank-staff.active');
Route::get('/rank/{rank}/all', [RankStaffController::class, 'all'])->middleware(['auth', 'password_changed'])->name('rank-staff.all');
Route::get('/rank/{rank}/export', [RankStaffController::class, 'exportRank'])->middleware(['auth', 'password_changed'])->name('rank-staff.export-rank');
Route::get('/rank/{rank}/export/promotion-list', [RankStaffController::class, 'exportPromotion'])->middleware(['auth', 'password_changed'])->name('rank-staff.export-rank-promote');
Route::get('/rank/{rank}/export/all-time', [RankStaffController::class, 'exportAll'])->middleware(['auth', 'password_changed'])->name('rank-staff.export-rank-all');

Route::controller(JobController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/rank', 'index')->name('job.index');
    Route::get('/rank-summary', 'summary')->name('job.summary');
    Route::get('/rank/create', 'create')->name('job.create');
    Route::get('/rank/{job}', 'show')->name('job.show');
    Route::patch('/rank/{job}', 'update')->name('job.update');
    Route::delete('/rank/{job}', 'delete')->name('job.delete');
    Route::post('/rank', 'store')->name('job.store');
    Route::get('rank/{job}/stats', 'stats')->name('job.stats');
    Route::get('rank/{job}/unitStats', 'unitStats')->name('job.unit-stats');
    Route::get('rank/{job}/age-distribution', 'staffAgeDistribution')->name('job.age-distribution');
});

Route::get('/rank/{rank}/category', function (Job $rank) {
    $rank->load('category');

    return [
        'id' => $rank->category->id,
        'name' => $rank->category->name,
        'level' => $rank->category->level,
        'short_name' => $rank->category->short_name,
    ];
})->middleware(['auth', 'password_changed'])->name('rank.category');

Route::get('rank/{rank}/next', function (Job $rank) {
    $nextCategoryId = $rank->job_category_id - 1;
    if ($nextCategoryId < 1) {
        return null;
    }

    return Job::where('job_category_id', $nextCategoryId)
        ->get()
        ->map(fn($rank) => [
            'value' => $rank->id,
            'label' => $rank->name,
        ]);
    //->where('id', '>', $rank->id)->first();
})->middleware(['auth', 'password_changed'])->name('rank.next');

// Route::get('rank/{rank}/previous', function (JobCategory $rank) {
//     $previousCategoryId =  $rank->job_category_id + 1 ;
//     return $rank->previous;
// })->middleware(['auth','password_changed'])->name('rank.previous');

Route::get('/document-types', function () {
    foreach (DocumentTypeEnum::cases() as $type) {
        $types[] = [
            'value' => $type->value,
            'label' => $type->getDocumentType(),
        ];
    }

    return $types;
})->middleware(['auth', 'password_changed'])->name('document-types');

Route::get('/document-statuses', function () {
    foreach (DocumentStatusEnum::cases() as $status) {
        $types[] = [
            'value' => $status->value,
            'label' => $status->getDocumentStatus(),
        ];
    }

    return $types;
})->middleware(['auth', 'password_changed'])->name('document-statuses');

Route::controller(QualificationDocumentController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::post('/qualification/{qualification}/document', 'update')->name('qualification-document.update');
    Route::delete('/qualification/{qualification}/document', 'delete')->name('qualification-document.delete');
});

// report
Route::get('/report', [RecruitmentController::class, 'index'])->middleware(['auth', 'password_changed'])->name('report.index');
Route::get('/report/recruitment', [RecruitmentController::class, 'recruitment'])->middleware(['auth', 'password_changed'])->name('report.recruitment');
Route::get('/report/recruitment/chart', [RecruitmentController::class, 'recruitmentChart'])->middleware(['auth', 'password_changed'])->name('report.recruitment.chart');
Route::get('/report/recruitment/details', [RecruitmentController::class, 'detail'])->middleware(['auth', 'password_changed'])->name('report.recruitment.details');

// staff
Route::get('/report-staff/', [StaffReportController::class, 'export'])->middleware(['auth', 'password_changed'])->name('report.staff');
Route::get('/report-staff-details/', [StaffReportController::class, 'details'])->middleware(['auth', 'password_changed'])->name('report.staff-details');
Route::get('/report-staff-retirement/', [StaffReportController::class, 'retirement'])->middleware(['auth', 'password_changed'])->name('report.staff-retirement');
Route::get('/report-staff-pending-transfer/', [StaffReportController::class, 'pending'])->middleware(['auth', 'password_changed'])->name('report.staff-pending-transfer');
Route::get('/report-staff-positions/', [StaffReportController::class, 'positions'])->middleware(['auth', 'password_changed'])->name('report.staff-positions');

// retires exports

Route::get('/report-retirements/', [StaffReportController::class, 'retirements'])->middleware(['auth', 'password_changed'])->name('report.retirements');
Route::get('/report-all-retirements/', [StaffReportController::class, 'allRetirements'])->middleware(['auth', 'password_changed'])->name('report.retirements-all');
Route::get('/report-deceased-retirements/', [StaffReportController::class, 'deceased'])->middleware(['auth', 'password_changed'])->name('report.retirements-deceased');
Route::get('/report-terminated-retirements/', [StaffReportController::class, 'terminated'])->middleware(['auth', 'password_changed'])->name('report.retirements-terminated');
Route::get('/report-resignation/', [StaffReportController::class, 'resignation'])->middleware(['auth', 'password_changed'])->name('report.resignation');
Route::get('/report-suspended/', [StaffReportController::class, 'suspended'])->middleware(['auth', 'password_changed'])->name('report.suspended');
Route::get('/report-vol-retirement/', [StaffReportController::class, 'volRetirement'])->middleware(['auth', 'password_changed'])->name('report.vol-retirement');
Route::get('/report-dismissed/', [StaffReportController::class, 'dismissed'])->middleware(['auth', 'password_changed'])->name('report.dismissed');
Route::get('/report-vacation-of-post/', [StaffReportController::class, 'vacatedPost'])->middleware(['auth', 'password_changed'])->name('report.vacation-of-post');
Route::get('/report-leave-with-pay/', [StaffReportController::class, 'leaveWithPay'])->middleware(['auth', 'password_changed'])->name('report.leave-pay');
Route::get('/report-leave-without-pay/', [StaffReportController::class, 'leaveWithoutPay'])->middleware(['auth', 'password_changed'])->name('report.leave-without-pay');
Route::get('report/recruitment/export/all', [RecruitmentController::class, 'exportAll'])->middleware(['auth', 'password_changed'])->name('report.recruitment.export-data');
Route::get('report/recruitment/export/summary', [RecruitmentController::class, 'exportSummary'])->middleware(['auth', 'password_changed'])->name('report.recruitment.export-summary');

// promotion report
Route::get('/export/promotion', [PromotionExportController::class, 'show'])->middleware(['auth', 'password_changed'])->name('export.promotion');
Route::get('/export/promotion/list', [PromotionExportController::class, 'list'])->middleware(['auth', 'password_changed'])->name('export.promotion-list');

Route::get('/report/promotion', [PromotionController::class, 'index'])->middleware(['auth', 'password_changed'])->name('report.promotion');
Route::get('/report/promotion/{year}', [PromotionBatchController::class, 'index'])->middleware(['auth', 'password_changed'])->name('report.promotion.year');

Route::controller(PromotionController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/past-promotion', 'index')->name('promotion.index');
    Route::get('/past-promotion/{year}', 'show')->name('promotion.show');
    Route::get('/past-promotion/{year}/rank', 'byRanks')->name('promotion.ranks');
    Route::get('/past-promotion/{promotion}/export', 'export')->name('promotion.export');
});

Route::controller(PromotionBatchController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/next-promotions', 'index')->name('promotion.batch.index');
    Route::get('/next-promotions/{rank}/{year?}', 'show')->name('promotion.batch.show');
});

Route::controller(QualificationController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/qualification', 'index')->name('qualification.index');
    Route::post('/qualification', 'store')->name('qualification.store');
    Route::patch('/qualification/{qualification}', 'update')->name('qualification.update');
    Route::delete('/qualification/{qualification}', 'delete')->name('qualification.delete');
});

Route::get('/contact-type', [ContactTypeController::class, 'index'])->middleware(['auth', 'password_changed'])->name('contact-type.index');
Route::get('/identities', [IdentityController::class, 'index'])->middleware(['auth', 'password_changed'])->name('identity.index');

Route::get('/marital-status', [MaritalStatusController::class, 'index'])->middleware(['auth', 'password_changed'])->name('marital-status.index');
Route::get('/gender', [GenderController::class, 'index'])->middleware(['auth', 'password_changed'])->name('gender.index');

Route::get('/nationality', [NationalityController::class, 'index'])->middleware(['auth', 'password_changed'])->name('nationality.index');

Route::get('/country', function () {
    $nationality = null;
    foreach (CountryEnum::cases() as $county) {
        $newNation = new \stdClass;
        $newNation->value = $county->value;
        $newNation->label = $county->label();
        $nationality[] = $newNation;
    }

    return $nationality;
})->middleware(['auth', 'password_changed'])->name('country.index');

Route::controller(StaffStatusController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/staff-status', 'index')->name('staff-status.index');
    Route::get('/staff-status/create', 'create')->name('staff-status.create');
    Route::post('/staff-status', 'store')->name('staff-status.store');
    Route::get('/staff-status/{staffStatus}', 'show')->name('staff-status.show');
    Route::patch('/staff-status/{staffStatus}', 'update')->name('staff-status.update');
    Route::delete('/staff-status/{staffStatus}', 'delete')->name('staff-status.delete');
});
// Route::post('/staff-status.save', [StaffStatusController::class, 'store'])->middleware(['auth','password_changed'])->name('staff-status.save');

Route::controller(StaffTypeController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/staff-type', 'index')->name('staff-type.index');
    Route::get('/staff-type/create', 'create')->name('staff-type.create');
    Route::post('/staff-type', 'store')->name('staff-type.store');
    Route::get('/staff-type/{staffType}', 'show')->name('staff-type.show');
    Route::patch('/staff-type/{staffType}', 'update')->name('staff-type.update');
    Route::delete('/staff-type/{staffType}', 'delete')->name('staff-type.delete');
});
// Route::post('/staff-type/{`staff}', [StaffTypeController::class, 'store'])->middleware(['auth','password_changed'])->name('staff-type.save');

// Route::patch('/staff-type/{staff}/{type}', [StaffTypeController::class, 'update'])->middleware(['auth','password_changed'])->name('staff-type.update');

Route::get('/unit-type', [UnitTypeController::class, 'index'])->middleware(['auth', 'password_changed'])->name('unit-type.index');

Route::controller(NoteController::class)->middleware(['auth', 'password_changed'])->group(function () {
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
})->middleware(['auth', 'password_changed'])->name('note-types');

Route::controller(PositionController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/position', 'index')->name('position.index');
    Route::get('/position/create', 'create')->name('position.create');
    Route::post('/position', 'store')->name('position.store');
    Route::get('/position/{position}', 'show')->name('position.show');
    Route::patch('/position/{position}', 'update')->name('position.update');
    Route::delete('/position/{position}', 'delete')->withTrashed()->name('position.delete');
    Route::get('/position-list', 'list')->name('position.list');
    Route::get('/position/{position}/stat', 'stat')->name('position.stat');
});

Route::get('staff-list', StaffListController::class)->middleware(['auth'])->name('staff-list');
// Route::get('/test', [AgeController::class, 'staffAgeDistribution']);
Route::get('/settings', SettingsController::class)->middleware(['auth', 'password_changed'])->name('settings.index');

Route::post('/role', [RoleController::class, 'store'])->middleware(['auth', 'password_changed'])->name('role.store');
Route::post('/role/{role}/add-permission', [RoleController::class, 'addPermission'])->middleware(['auth', 'password_changed'])->name('role.add.permissions');
Route::get('/role/{role}/permissions', RolePermissionController::class)->middleware(['auth', 'password_changed'])->name('role.permissions');
Route::controller(AuditLogController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/audit-log', 'index')->name('audit-log.index');
    Route::get('/audit-log/{auditLog}', 'show')->name('audit-log.show');
    Route::delete('/audit-log/{auditLog}', 'delete')->name('audit-log.delete');
});

Route::get('/rank-staff-stats/{job}', RankStaffStatsController::class)->name('rank-staff-stats');

Route::controller(RegionController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/region', 'index')->name('region.index');
    Route::get('/region/create', 'create')->name('region.create');
    Route::post('/region', 'store')->name('region.store');
    Route::get('/region/{region}', 'show')->name('region.show');
    Route::patch('/region/{region}', 'update')->name('region.update');
    Route::delete('/region/{region}', 'delete')->name('region.delete');
});

Route::controller(DistrictController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/district', 'index')->name('district.index');
    Route::get('/district/create', 'create')->name('district.create');
    Route::post('/district', 'store')->name('district.store');
    Route::get('/district/{district}', 'show')->name('district.show');
    Route::patch('/district/{district}', 'update')->name('district.update');
    Route::delete('/district/{district}', 'delete')->name('district.delete');
});

Route::controller(OfficeController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/office', 'index')->name('office.index');
    Route::get('/office/create', 'create')->name('office.create');
    Route::post('/office', 'store')->name('office.store');
    Route::get('/office/{office}', 'show')->name('office.show');
    Route::patch('/office/{office}', 'update')->name('office.update');
    Route::delete('/office/{office}', 'delete')->name('office.delete');
});
