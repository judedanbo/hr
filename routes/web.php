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
use App\Http\Controllers\CategoryRanksController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactTypeController;
use App\Http\Controllers\DataIntegrityController;
use App\Http\Controllers\DependentController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\HelpController;
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
use App\Http\Controllers\QualificationLevelController;
use App\Http\Controllers\RankStaffController;
use App\Http\Controllers\RankStaffStatsController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\Reports\RecruitmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SeparationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffListController;
use App\Http\Controllers\StaffReportController;
use App\Http\Controllers\StaffSearchOptionsController;
use App\Http\Controllers\StaffStatusController;
use App\Http\Controllers\StaffTypeController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitOfficeController;
use App\Http\Controllers\UnitTypeController;
use App\Http\Controllers\UserController;
use App\Models\Dependent;
use App\Models\Institution;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Unit;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
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

Route::controller(ChangePasswordController::class)->middleware(['auth'])->group(function () {
    Route::get('/change-password', 'index')->name('change-password.index');
    Route::post('/change-password', 'store')->name('change-password.store');
});
// Route::get('/change-password', [ChangePasswordController::class, 'index'])->middleware(['auth'])->name('change-password');

Route::controller(UserController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/user', 'index')->middleware('can:view all users')->name('user.index');
    Route::get('/users-list', 'list')->middleware('can:view all users')->name('users.list');
    Route::get('/user/{user}', 'show')->middleware('can:view user')->name('user.show');
    Route::post('/user/', 'store')->middleware('can:create user')->name('user.store');
    Route::patch('/user/{user}', 'update')->middleware('can:update user')->name('user.update');
    Route::delete('/user/{user}', 'delete')->middleware('can:delete user')->name('user.delete');
    Route::post('/user/{user}', 'resetPassword')->middleware('can:reset user password')->name('user.reset-password');
    Route::get('/user/{user}/roles', 'roles')->middleware('can:view user roles')->name('user.roles');
    Route::get('/user/{user}/permissions', 'permissions')->middleware('can:view user permissions')->name('user.permissions');
    Route::get('/user/{user}/roles-permissions', 'rolesPermissions')->middleware('can:view user roles')->name('user.roles-permissions');
});
Route::controller(RoleController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/role', 'index')->middleware('can:view roles')->name('role.index');
    Route::get('/roles-list', 'list')->middleware('can:view roles')->name('roles.list');
    Route::get('/role/{role}', 'show')->middleware('can:view roles')->name('role.show');
    Route::post('/user/{user}/add-role', 'addRole')->middleware('can:update user roles')->name('user.add.roles');
    Route::patch('/user/{user}/revoke-role', 'revokeRole')->middleware('can:update user roles')->name('user.revoke.roles');
    Route::post('/role/{role}/add-users', 'addUsers')->middleware('can:update role')->name('role.add.users');
    Route::patch('/role/{role}/remove-user', 'removeUser')->middleware('can:update role')->name('role.remove.user');
});
Route::controller(PermissionController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/permission', 'index')->middleware('can:view permissions')->name('permission.index');
    Route::get('/permission-list', 'list')->middleware('can:view permissions')->name('permission.list');
    Route::get('/permission/{permission}', 'show')->middleware('can:view permissions')->name('permission.show');
    Route::post('/permission', 'store')->middleware('can:create permission')->name('permission.store');
    Route::put('/permission/{permission}', 'update')->middleware('can:update permission')->name('permission.update');
    Route::delete('/permission/{permission}', 'destroy')->middleware('can:delete permission')->name('permission.destroy');
    Route::post('/user/{user}/add-permission', 'addPermission')->middleware('can:update user permissions')->name('user.add.permissions');
    Route::patch('/user/{user}/revoke-permission', 'revokePermission')->middleware('can:update user permissions')->name('user.revoke.permissions');
});

Route::get('/dashboard', function () {
    request()->session()->reflash();

    $user = auth()->user();

    // Staff role: redirect to their own staff page
    if ($user->hasRole('staff')) {
        if ($user->person) {
            return redirect()
                ->route('staff.show', [$user->person->institution->first()->staff->id]);
        }

        return redirect()->route('staff.index');
    }

    // Admin roles with dashboard access: show institution dashboard
    if ($user->hasRole('super-administrator') || $user->can('view dashboard')) {
        if (Institution::count() < 1) {
            session()->flash('info', 'No institution found. Please create an institution to proceed');

            return redirect()->route('institution.index');
        }

        return redirect()->route('institution.show', [1]);
    }

    // All other authenticated users: redirect to staff list
    return redirect()->route('staff.index');
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
    Route::get('/institution/{institution}', 'show')->middleware('can:view dashboard')->name('institution.show');
    Route::get('/institution/{institution}/staff-filter', 'staffFilter')->name('institution.staff-filter');
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

    return $institution->allUnits->map(fn ($unit) => [
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
    Route::get('/unit', 'index')->middleware('can:view all units')->name('unit.index');
    Route::post('/unit', 'store')->middleware('can:create unit')->name('unit.store');
    Route::get('/unit/{unit}', 'show')->middleware('can:view unit')->name('unit.show');
    Route::delete('/unit/{unit}', 'delete')->middleware('can:delete unit')->name('unit.delete');
    Route::patch('/unit/{unit}', 'update')->middleware('can:edit unit')->name('unit.update');
    Route::get('/unit/{unit}/details', 'details')->middleware('can:view unit')->name('unit.details');
    Route::post('/unit/{unit}/details', 'addSub')->middleware('can:create unit')->name('unit.add-sub');
    Route::get('/unit/{unit}/download', 'download')->middleware('can:download unit staff')->name('export.unit.staff');
});

// Unit Office Management
Route::controller(UnitOfficeController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::post('/unit/{unit}/office', 'store')->middleware('can:edit unit')->name('unit.office.store');
    Route::post('/unit/{unit}/office/create', 'storeNew')->middleware('can:edit unit')->name('unit.office.create');
    Route::delete('/unit/{unit}/office', 'destroy')->middleware('can:edit unit')->name('unit.office.destroy');
    Route::get('/unit/{unit}/office/history', 'history')->middleware('can:view unit')->name('unit.office.history');
});

// Office dropdown data endpoints
Route::middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/offices-list', [UnitOfficeController::class, 'availableOffices'])->name('offices.list');
    Route::get('/districts-list', [UnitOfficeController::class, 'districtsList'])->name('districts.list');
    Route::get('/regions-list', [UnitOfficeController::class, 'regionsList'])->name('regions.list');
    Route::get('/office-types', [UnitOfficeController::class, 'officeTypes'])->name('office-types.list');
});

Route::get('/unit-list', function () {
    // return 'all units';
    $units = Unit::all();

    return $units->map(fn ($unit) => [
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
Route::controller(InstitutionPersonController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/staff', 'index')->middleware('can:view all staff')->name('staff.index');
    Route::get('/staff/create', 'create')->middleware('can:create staff')->name('staff.create');
    Route::post('/staff', 'store')->middleware('can:create staff')->name('staff.store');
    Route::get('/staff/{staff}', 'show')->middleware('can:view staff')->name('staff.show');
    Route::patch('/staff/{staff}', 'update')->middleware('can:update staff')->name('staff.update');
    Route::get('/staff/{staff}/edit', 'edit')->middleware('can:update staff')->name('staff.edit');
    Route::get('/staff/{staff}/promotions', 'promotions')->middleware('can:view staff promotion')->name('staff.promotion-history');
    Route::post('/staff/{staff}/dependent', 'createDependent')->middleware('can:update staff')->name('staff.dependent.create');
    Route::delete('/staff/{staff}/dependent/{dependent}', 'deleteDependent')->middleware('can:update staff')->name('staff.dependent.delete');
    Route::post('/staff/{staff}/write-note', 'writeNote')->middleware('can:create staff notes')->name('staff.write-note');
    Route::post('/staff/{staff}/position', 'assignPosition')->middleware('can:create staff position')->name('staff.position.store');
    Route::patch('/staff/{staff}/position', 'updatePosition')->middleware('can:update staff position')->name('staff.position.update');
    Route::delete('/staff/{staff}/position', 'deletePosition')->withTrashed()->middleware('can:delete staff position')->name('staff.position.delete');
});

// separation
Route::controller(SeparationController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/separation', 'index')->middleware('can:view separated staff')->name('separation.index');
    Route::get('/separation/{staff}', 'show')->middleware('can:view separated staff')->name('separation.show');
});
//  promote
Route::controller(PromoteStaffController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::post('/staff/{staff}/promote', 'store')->middleware('can:create staff promotion')->name('staff.promote.store');
    Route::patch('/staff/{staff}/promote/{promotion}', 'update')->middleware('can:update staff promotion')->name('staff.promote.update');
    Route::delete('/staff/{staff}/promote/{job}', 'delete')->middleware('can:delete staff promotion')->name('staff.promote.delete');
});
Route::post('/staff/promote-all', [PromoteAllStaffController::class, 'save'])->middleware(['auth', 'password_changed', 'can:create staff promotion'])->name('rank-staff.promote-all');

// transfer
Route::controller(TransferController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::post('/staff/{staff}/transfer', 'store')->middleware('can:create staff transfers')->name('staff.transfer.store');
    Route::patch('/staff/{staff}/unit/{unit}', 'update')->middleware('can:update staff transfers')->name('staff.transfer.update');
    Route::delete('/staff/{staff}/transfer/{unit}', 'delete')->middleware('can:delete staff transfers')->name('staff.transfer.delete');
    Route::patch('/staff/{staff}/transfer/{unit}', 'approve')->middleware('can:update staff transfers')->name('staff.transfer.approve');
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
    Route::get('/job-category', 'index')->middleware('can:view all job categories')->name('job-category.index');
    Route::get('/job-category/summary', 'summary')->middleware('can:view all job categories')->name('job-category.summary');
    Route::get('/job-category/create', 'create')->middleware('can:create job category')->name('job-category.create');
    Route::post('/job-category', 'store')->middleware('can:create job category')->name('job-category.store');
    Route::get('/job-category/{jobCategory}', 'show')->middleware('can:view job category')->name('job-category.show');
    Route::patch('/job-category/{jobCategory}', 'update')->middleware('can:edit job category')->name('job-category.update');
    Route::delete('/job-category/{jobCategory}', 'delete')->middleware('can:delete job category')->name('job-category.delete');
});

Route::controller(CategoryRanksController::class)->middleware(['auth', 'password_changed'])->group(function () {
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
    Route::get('/rank', 'index')->middleware('can:view all jobs')->name('job.index');
    Route::get('/rank-summary', 'summary')->middleware('can:view all jobs')->name('job.summary');
    Route::get('/rank/create', 'create')->middleware('can:create job')->name('job.create');
    Route::get('/rank/{job}', 'show')->middleware('can:view job')->name('job.show');
    Route::patch('/rank/{job}', 'update')->middleware('can:edit job')->name('job.update');
    Route::delete('/rank/{job}', 'delete')->middleware('can:delete job')->name('job.delete');
    Route::post('/rank', 'store')->middleware('can:create job')->name('job.store');
    Route::get('rank/{job}/stats', 'stats')->middleware('can:view job')->name('job.stats');
    Route::get('rank/{job}/unitStats', 'unitStats')->middleware('can:view job')->name('job.unit-stats');
    Route::get('rank/{job}/age-distribution', 'staffAgeDistribution')->middleware('can:view job')->name('job.age-distribution');
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
        ->map(fn ($rank) => [
            'value' => $rank->id,
            'label' => $rank->name,
        ]);
    // ->where('id', '>', $rank->id)->first();
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

// qualification reports
Route::middleware(['auth', 'password_changed'])->prefix('qualifications/reports')->name('qualifications.reports.')->group(function () {
    Route::get('/', [\App\Http\Controllers\QualificationReportController::class, 'index'])
        ->name('index')
        ->middleware('can:qualifications.reports.view');
    Route::get('/export/excel', [\App\Http\Controllers\QualificationReportController::class, 'exportExcel'])
        ->name('export.excel')
        ->middleware('can:qualifications.reports.export');
});

Route::controller(QualificationController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/qualification', 'index')->middleware('can:view staff qualification')->name('qualification.index');
    Route::post('/qualification', 'store')->middleware('can:create staff qualification')->name('qualification.store');
    Route::patch('/qualification/{qualification}', 'update')->middleware('can:edit staff qualification')->name('qualification.update');
    Route::delete('/qualification/{qualification}', 'delete')->middleware('can:edit staff qualification')->name('qualification.delete');
    Route::patch('/qualification/{qualification}/approve', 'approve')->middleware('can:approve staff qualification')->name('qualification.approve');
    Route::patch('/qualification/{qualification}/reject', 'reject')->middleware('can:approve staff qualification')->name('qualification.reject');
});

Route::get('/contact-type', [ContactTypeController::class, 'index'])->middleware(['auth', 'password_changed'])->name('contact-type.index');
Route::get('/identities', [IdentityController::class, 'index'])->middleware(['auth', 'password_changed'])->name('identity.index');

Route::get('/marital-status', [MaritalStatusController::class, 'index'])->middleware(['auth', 'password_changed'])->name('marital-status.index');
Route::get('/gender', [GenderController::class, 'index'])->middleware(['auth', 'password_changed'])->name('gender.index');
Route::get('/qualification-level', [QualificationLevelController::class, 'index'])->middleware(['auth', 'password_changed'])->name('qualification-level.index');

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
    Route::get('/staff-status', 'index')->middleware('can:view staff status')->name('staff-status.index');
    Route::get('/staff-status/create', 'create')->middleware('can:create staff status')->name('staff-status.create');
    Route::post('/staff-status', 'store')->middleware('can:create staff status')->name('staff-status.store');
    Route::get('/staff-status/{staffStatus}', 'show')->middleware('can:view staff status')->name('staff-status.show');
    Route::patch('/staff-status/{staffStatus}', 'update')->middleware('can:edit staff status')->name('staff-status.update');
    Route::delete('/staff-status/{staffStatus}', 'delete')->middleware('can:delete staff status')->name('staff-status.delete');
});

Route::controller(StaffTypeController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/staff-type', 'index')->middleware('can:view all staff')->name('staff-type.index');
    Route::get('/staff-type/create', 'create')->middleware('can:create staff')->name('staff-type.create');
    Route::post('/staff-type', 'store')->middleware('can:create staff')->name('staff-type.store');
    Route::get('/staff-type/{staffType}', 'show')->middleware('can:view staff')->name('staff-type.show');
    Route::patch('/staff-type/{staffType}', 'update')->middleware('can:update staff')->name('staff-type.update');
    Route::delete('/staff-type/{staffType}', 'delete')->middleware('can:delete staff')->name('staff-type.delete');
});
// Route::post('/staff-type/{`staff}', [StaffTypeController::class, 'store'])->middleware(['auth','password_changed'])->name('staff-type.save');

// Route::patch('/staff-type/{staff}/{type}', [StaffTypeController::class, 'update'])->middleware(['auth','password_changed'])->name('staff-type.update');

Route::get('/unit-type', [UnitTypeController::class, 'index'])->middleware(['auth', 'password_changed'])->name('unit-type.index');

Route::controller(NoteController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/notes', 'index')->middleware('can:view staff notes')->name('notes.index');
    Route::get('/notes/create', 'create')->middleware('can:create staff notes')->name('notes.create');
    Route::post('/notes', 'store')->middleware('can:create staff notes')->name('notes.store');
    Route::get('/notes/{note}', 'show')->middleware('can:view staff notes')->name('notes.show');
    Route::get('/notes/{note}/edit', 'edit')->middleware('can:edit staff notes')->name('notes.edit');
    Route::patch('/notes/{note}', 'update')->middleware('can:edit staff notes')->name('notes.update');
    Route::delete('/notes/{note}', 'delete')->middleware('can:edit staff notes')->name('notes.delete');
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
    Route::get('/position', 'index')->middleware('can:view all positions')->name('position.index');
    Route::get('/position/create', 'create')->middleware('can:create position')->name('position.create');
    Route::post('/position', 'store')->middleware('can:create position')->name('position.store');
    Route::get('/position/{position}', 'show')->middleware('can:view position')->name('position.show');
    Route::patch('/position/{position}', 'update')->middleware('can:update position')->name('position.update');
    Route::delete('/position/{position}', 'delete')->withTrashed()->middleware('can:delete position')->name('position.delete');
    Route::get('/position-list', 'list')->middleware('can:view all positions')->name('position.list');
    Route::get('/position/{position}/stat', 'stat')->middleware('can:view position')->name('position.stat');
});

Route::get('staff-list', StaffListController::class)->middleware(['auth'])->name('staff-list');
// Route::get('/test', [AgeController::class, 'staffAgeDistribution']);
Route::get('/settings', SettingsController::class)->middleware(['auth', 'password_changed'])->name('settings.index');
Route::get('/help', [HelpController::class, 'index'])->middleware(['auth', 'password_changed'])->name('help.index');

Route::post('/role', [RoleController::class, 'store'])->middleware(['auth', 'password_changed', 'can:create role'])->name('role.store');
Route::post('/role/{role}/add-permission', [RoleController::class, 'addPermission'])->middleware(['auth', 'password_changed', 'can:assign permissions to role'])->name('role.add.permissions');
Route::patch('/role/{role}/remove-permission', [RoleController::class, 'removePermission'])->middleware(['auth', 'password_changed', 'can:assign permissions to role'])->name('role.remove.permission');
Route::get('/role/{role}/permissions', RolePermissionController::class)->middleware(['auth', 'password_changed', 'can:view roles'])->name('role.permissions');
Route::controller(AuditLogController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/audit-log', 'index')->middleware('can:view user activity')->name('audit-log.index');
    Route::get('/audit-log/{auditLog}', 'show')->middleware('can:view user activity')->name('audit-log.show');
    Route::delete('/audit-log/{auditLog}', 'delete')->middleware('can:view user activity')->name('audit-log.delete');
});

Route::controller(ContactController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/contact', 'index')->middleware('can:view contacts')->name('contact.index');
    Route::get('/contact/create', 'create')->middleware('can:create contacts')->name('contact.create');
    Route::post('/contact', 'store')->middleware('can:create contacts')->name('contact.store');
    Route::get('/contact/{contact}', 'show')->middleware('can:view contacts')->name('contact.show');
    Route::get('/contact/{contact}/edit', 'edit')->middleware('can:update contacts')->name('contact.edit');
    Route::patch('/contact/{contact}', 'update')->middleware('can:update contacts')->name('contact.update');
    Route::delete('/contact/{contact}', 'destroy')->middleware('can:delete contacts')->name('contact.destroy');
});

Route::controller(DocumentController::class)->middleware(['auth', 'password_changed'])->group(function () {
    Route::get('/document', 'index')->middleware('can:view documents')->name('document.index');
    Route::get('/document/create', 'create')->middleware('can:create documents')->name('document.create');
    Route::post('/document', 'store')->middleware('can:create documents')->name('document.store');
    Route::get('/document/{document}', 'show')->middleware('can:view documents')->name('document.show');
    Route::get('/document/{document}/download', 'download')->middleware('can:view documents')->name('document.download');
    Route::get('/document/{document}/edit', 'edit')->middleware('can:update documents')->name('document.edit');
    Route::patch('/document/{document}', 'update')->middleware('can:update documents')->name('document.update');
    Route::delete('/document/{document}', 'destroy')->middleware('can:delete documents')->name('document.destroy');
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

Route::middleware('auth')->prefix('staff-search')->group(function () {
    Route::get('/options', [StaffSearchOptionsController::class, 'index'])->name('staff-search.options');
    Route::get('/job-categories', [StaffSearchOptionsController::class, 'jobCategories'])->name('staff-search.job-categories');
    Route::get('/jobs', [StaffSearchOptionsController::class, 'jobs'])->name('staff-search.jobs');
    Route::get('/units', [StaffSearchOptionsController::class, 'units'])->name('staff-search.units');
    Route::get('/departments', [StaffSearchOptionsController::class, 'departments'])->name('staff-search.departments');
});

Route::middleware('auth')->prefix('data-integrity')->group(function () {
    Route::get('/', [DataIntegrityController::class, 'index'])->name('data-integrity.index');
    Route::get('/multiple-ranks', [DataIntegrityController::class, 'multipleRanks'])->name('data-integrity.multiple-ranks');
    Route::post('/multiple-ranks/{staff}/fix', [DataIntegrityController::class, 'fixMultipleRanks'])->name('data-integrity.multiple-ranks.fix');
    Route::post('/multiple-ranks/bulk-fix', [DataIntegrityController::class, 'bulkFixMultipleRanks'])->name('data-integrity.multiple-ranks.bulk-fix');

    Route::get('/staff-without-units', [DataIntegrityController::class, 'staffWithoutUnits'])->name('data-integrity.staff-without-units');
    Route::get('/staff-without-ranks', [DataIntegrityController::class, 'staffWithoutRanks'])->name('data-integrity.staff-without-ranks');
    Route::get('/invalid-date-ranges', [DataIntegrityController::class, 'invalidDateRanges'])->name('data-integrity.invalid-date-ranges');
    Route::post('/invalid-date-ranges/{staff}/fix', [DataIntegrityController::class, 'fixInvalidDateRanges'])->name('data-integrity.invalid-date-ranges.fix');
    Route::post('/invalid-date-ranges/bulk-fix', [DataIntegrityController::class, 'bulkFixInvalidDateRanges'])->name('data-integrity.invalid-date-ranges.bulk-fix');
    Route::get('/separated-but-active', [DataIntegrityController::class, 'separatedButActive'])->name('data-integrity.separated-but-active');
    Route::get('/staff-without-pictures', [DataIntegrityController::class, 'staffWithoutPictures'])->name('data-integrity.staff-without-pictures');
    Route::get('/expired-active-status', [DataIntegrityController::class, 'expiredActiveStatus'])->name('data-integrity.expired-active-status');
    Route::get('/multiple-unit-assignments', [DataIntegrityController::class, 'multipleUnitAssignments'])->name('data-integrity.multiple-unit-assignments');
    Route::get('/staff-without-gender', [DataIntegrityController::class, 'staffWithoutGender'])->name('data-integrity.staff-without-gender');
    Route::get('/pending-qualifications', [DataIntegrityController::class, 'pendingQualifications'])->name('data-integrity.pending-qualifications');
});
