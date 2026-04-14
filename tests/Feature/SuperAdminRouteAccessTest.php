<?php

namespace Tests\Feature;

use App\Enums\DistrictTypeEnum;
use App\Enums\OfficeTypeEnum;
use App\Models\Contact;
use App\Models\District;
use App\Models\Document;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Note;
use App\Models\Office;
use App\Models\Person;
use App\Models\Position;
use App\Models\Region;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Institution $institution;

    protected User $testUser;

    protected InstitutionPerson $staff;

    protected Person $person;

    protected Unit $unit;

    protected Job $job;

    protected JobCategory $jobCategory;

    protected Role $role;

    protected Permission $permission;

    protected Region $region;

    protected District $district;

    protected Office $office;

    protected Position $position;

    protected Contact $contact;

    protected Document $document;

    protected Note $note;

    protected Activity $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createSuperAdmin();
        $this->createTestModels();
    }

    protected function createSuperAdmin(): void
    {
        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');
    }

    protected function createTestModels(): void
    {
        // Geographic hierarchy (created directly - no factories, using unguarded)
        Region::unguard();
        District::unguard();
        Office::unguard();

        $this->region = Region::create([
            'name' => 'Test Region',
            'capital' => 'Test Capital',
        ]);
        $this->district = District::create([
            'name' => 'Test District',
            'region_id' => $this->region->id,
            'capital' => 'Test District Capital',
            'district_type' => DistrictTypeEnum::METROPOLITAN,
        ]);
        $this->office = Office::create([
            'name' => 'Test Office',
            'district_id' => $this->district->id,
            'type' => OfficeTypeEnum::HEADQUARTERS,
        ]);

        Region::reguard();
        District::reguard();
        Office::reguard();

        // Institution and Unit
        $this->institution = Institution::factory()->create();
        $this->unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        // Job hierarchy
        $this->jobCategory = JobCategory::factory()->create();
        $this->job = Job::factory()->create(['job_category_id' => $this->jobCategory->id]);

        // Person and Staff
        $this->person = Person::factory()->create();
        $this->staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'person_id' => $this->person->id,
        ]);

        // Test user (different from super admin)
        $testPerson = Person::factory()->create();
        $this->testUser = User::factory()->create([
            'person_id' => $testPerson->id,
            'password_change_at' => now(),
        ]);

        // Position (created directly - factory is empty)
        $this->position = Position::create(['name' => 'Test Position']);

        // Contact
        $this->contact = Contact::factory()->create(['person_id' => $this->person->id]);

        // Document (polymorphic - documentable)
        $this->document = Document::factory()->create([
            'documentable_type' => InstitutionPerson::class,
            'documentable_id' => $this->staff->id,
        ]);

        // Note (polymorphic - notable)
        $this->note = Note::factory()->create([
            'notable_type' => InstitutionPerson::class,
            'notable_id' => $this->staff->id,
            'created_by' => $this->superAdmin->id,
        ]);

        // Role and Permission (get existing from seeder)
        $this->role = Role::first() ?? Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $this->permission = Permission::first() ?? Permission::create(['name' => 'test-permission', 'guard_name' => 'web']);

        // Create an audit log entry
        activity()
            ->causedBy($this->superAdmin)
            ->log('Test activity for audit log');
        $this->auditLog = Activity::latest()->first();
    }

    /**
     * Helper method to assert multiple routes are accessible
     */
    protected function assertRoutesAccessible(array $routes): void
    {
        foreach ($routes as $routeName => $params) {
            $response = $this->actingAs($this->superAdmin)
                ->get(route($routeName, $params));

            $this->assertTrue(
                in_array($response->getStatusCode(), [200, 302]),
                "Route '{$routeName}' returned status {$response->getStatusCode()}, expected 200 or 302"
            );
        }
    }

    public function test_super_admin_can_access_dashboard_routes(): void
    {
        $routes = [
            'dashboard' => [],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_staff_routes(): void
    {
        $routes = [
            'staff.index' => [],
            'staff.create' => [],
            'staff.show' => ['staff' => $this->staff->id],
            'staff.edit' => ['staff' => $this->staff->id],
            'staff.promotion-history' => ['staff' => $this->staff->id],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_user_routes(): void
    {
        $routes = [
            'user.index' => [],
            'user.show' => ['user' => $this->testUser->id],
            'user.permissions' => ['user' => $this->testUser->id],
            'user.roles' => ['user' => $this->testUser->id],
            'user.roles-permissions' => ['user' => $this->testUser->id],
            'users.list' => [],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_institution_routes(): void
    {
        $routes = [
            'institution.index' => [],
            'institution.create' => [],
            'institution.show' => ['institution' => $this->institution->id],
            'institution.staffs' => ['institution' => $this->institution->id],
            'institution.staff-filter' => ['institution' => $this->institution->id, 'filter' => 'active'],
            'institution.unit-list' => ['institution' => $this->institution->id],
            'institution.job-list' => ['institution' => $this->institution->id],
            'institution.staff-types' => ['institution' => $this->institution->id],
            'institution.statuses' => ['institution' => $this->institution->id],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_unit_routes(): void
    {
        $routes = [
            'unit.index' => [],
            'unit.show' => ['unit' => $this->unit->id],
            'unit.details' => ['unit' => $this->unit->id],
            'units.list' => [],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_job_routes(): void
    {
        $routes = [
            'job.index' => [],
            'job.create' => [],
            'job.show' => ['job' => $this->job->id],
            'job.summary' => [],
            'job.stats' => ['job' => $this->job->id],
            'job-category.index' => [],
            'job-category.create' => [],
            'job-category.show' => ['jobCategory' => $this->jobCategory->id],
            'job-category.summary' => [],
            'category-ranks.show' => ['category' => $this->jobCategory->id],
            'rank-staff.index' => ['rank' => $this->job->id],
            'rank-staff.active' => ['rank' => $this->job->id],
            'rank-staff.all' => ['rank' => $this->job->id],
            'rank-staff.promote' => ['rank' => $this->job->id],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_report_routes(): void
    {
        $routes = [
            'report.index' => [],
            'report.recruitment' => [],
            'report.recruitment.details' => [],
            'promotion.batch.index' => [],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_data_integrity_routes(): void
    {
        $routes = [
            'data-integrity.index' => [],
            'data-integrity.multiple-unit-assignments' => [],
            'data-integrity.staff-without-units' => [],
            'data-integrity.staff-without-pictures' => [],
            'data-integrity.staff-without-ranks' => [],
            'data-integrity.staff-without-gender' => [],
            'data-integrity.multiple-ranks' => [],
            'data-integrity.invalid-date-ranges' => [],
            'data-integrity.separated-but-active' => [],
            'data-integrity.expired-active-status' => [],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_permission_and_role_routes(): void
    {
        $routes = [
            'permission.index' => [],
            'permission.show' => ['permission' => $this->permission->id],
            'permission.list' => [],
            'role.index' => [],
            'role.show' => ['role' => $this->role->id],
            'role.permissions' => ['role' => $this->role->id],
            'roles.list' => [],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_geographic_routes(): void
    {
        $routes = [
            'region.index' => [],
            // 'region.create' => [], // Controller method not implemented
            'region.show' => ['region' => $this->region->id],
            'district.index' => [],
            // 'district.create' => [], // Controller method not implemented
            'district.show' => ['district' => $this->district->id],
            'office.index' => [],
            // 'office.create' => [], // Controller method not implemented
            'office.show' => ['office' => $this->office->id],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_person_routes(): void
    {
        $routes = [
            'person.index' => [],
            'person.show' => ['person' => $this->person->id],
            'person.edit' => ['person' => $this->person->id],
            'person.avatar' => ['person' => $this->person->id],
            'person-roles.show' => ['person' => $this->person->id],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_other_resource_routes(): void
    {
        $routes = [
            'position.index' => [],
            'position.create' => [],
            'position.show' => ['position' => $this->position->id],
            'position.list' => [],
            'contact.index' => [],
            'contact.create' => [],
            'contact.show' => ['contact' => $this->contact->id],
            'document.index' => [],
            'document.create' => [],
            'document.show' => ['document' => $this->document->id],
            'notes.index' => [],
            'notes.create' => [],
            'notes.show' => ['note' => $this->note->id],
            'dependent.index' => [],
            'qualification.index' => [],
            'separation.index' => [],
            'audit-log.index' => [],
            'audit-log.show' => ['auditLog' => $this->auditLog->id],
        ];

        $this->assertRoutesAccessible($routes);
    }

    public function test_super_admin_can_access_reference_data_routes(): void
    {
        $routes = [
            'country.index' => [],
            'gender.index' => [],
            'marital-status.index' => [],
            'nationality.index' => [],
            'identity.index' => [],
            'staff-type.index' => [],
            // 'staff-status.index' => [], // Controller method not implemented
            'unit-type.index' => [],
            'contact-type.index' => [],
            'document-types' => [],
            'document-statuses' => [],
            'note-types' => [],
        ];

        $this->assertRoutesAccessible($routes);
    }
}
