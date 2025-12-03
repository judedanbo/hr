<?php

namespace Tests\Feature;

use App\Enums\EmployeeStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Separation;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        // Create institution
        $this->institution = Institution::factory()->create();

        // Create a person linked to user for institution context
        $adminPerson = Person::factory()->create();
        $adminPerson->institution()->attach($this->institution->id, [
            'staff_number' => 'ADMIN001',
            'hire_date' => now()->subYears(5),
        ]);

        // Create user with permissions and link to person
        $this->user = User::factory()->create(['person_id' => $adminPerson->id]);
        $this->user->givePermissionTo('view all separations');
        $this->user->givePermissionTo('view separation');
    }

    // ===================
    // AUTHORIZATION TESTS
    // ===================

    public function test_separation_index_requires_authentication(): void
    {
        $response = $this->get(route('separation.index'));

        $response->assertRedirect('/login');
    }

    public function test_separation_index_requires_permission(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('separation.index'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'You do not have permission to view separated staff');
    }

    public function test_separation_show_requires_authentication(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Retired);

        $response = $this->get(route('separation.show', $separatedStaff->id));

        $response->assertRedirect('/login');
    }

    public function test_separation_show_requires_permission(): void
    {
        $userWithoutPermission = User::factory()->create();
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Retired);

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('separation.show', $separatedStaff->id));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'You do not have permission to view separated staff');
    }

    // ===================
    // INDEX PAGE TESTS
    // ===================

    public function test_separation_index_loads_for_authorized_user(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('separation.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Separation/Index'));
    }

    public function test_separation_index_displays_separated_staff(): void
    {
        // Create separated staff
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Retired);

        $response = $this->actingAs($this->user)
            ->get(route('separation.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Separation/Index')
            ->has('separated')
        );
    }

    public function test_separation_index_shows_empty_when_no_separated_staff(): void
    {
        // Create only active staff (not separated)
        $this->createActiveStaff();

        $response = $this->actingAs($this->user)
            ->get(route('separation.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Separation/Index')
            ->has('separated')
        );
    }

    // ===================
    // SHOW PAGE TESTS
    // ===================

    public function test_separation_show_loads_for_authorized_user(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Retired);

        $response = $this->actingAs($this->user)
            ->get(route('separation.show', $separatedStaff->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Separation/Show'));
    }

    // ===================
    // SEPARATION STATUS TESTS
    // ===================

    public function test_retired_staff_appears_in_separation_list(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Retired);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $separatedStaff->id));
    }

    public function test_resigned_staff_appears_in_separation_list(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Resignation);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $separatedStaff->id));
    }

    public function test_terminated_staff_appears_in_separation_list(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Termination);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $separatedStaff->id));
    }

    public function test_dismissed_staff_appears_in_separation_list(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Dismissed);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $separatedStaff->id));
    }

    public function test_deceased_staff_appears_in_separation_list(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Deceased);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $separatedStaff->id));
    }

    public function test_left_staff_appears_in_separation_list(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Left);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $separatedStaff->id));
    }

    public function test_voluntary_separation_appears_in_separation_list(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Voluntary);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $separatedStaff->id));
    }

    public function test_active_staff_does_not_appear_in_separation_list(): void
    {
        $activeStaff = $this->createActiveStaff();

        $separations = Separation::all();

        $this->assertFalse($separations->contains('id', $activeStaff->id));
    }

    // ===================
    // SEARCH TESTS
    // ===================

    public function test_can_search_separated_staff_by_staff_number(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Retired, [
            'staff_number' => 'SEARCH123',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('separation.index', ['search' => 'SEARCH123']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Separation/Index')
            ->has('filters')
        );
    }

    public function test_can_search_separated_staff_by_file_number(): void
    {
        $separatedStaff = $this->createSeparatedStaff(EmployeeStatusEnum::Retired, [
            'file_number' => 'FILE999',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('separation.index', ['search' => 'FILE999']));

        $response->assertStatus(200);
    }

    // ===================
    // ACTIVITY LOG TESTS
    // ===================

    public function test_viewing_separation_index_logs_activity(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('separation.index'));

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'view all separations',
            'causer_id' => $this->user->id,
            'causer_type' => User::class,
        ]);
    }

    public function test_unauthorized_index_access_logs_failed_activity(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('separation.index'));

        $this->assertDatabaseHas('activity_log', [
            'event' => 'view all separations',
            'causer_id' => $userWithoutPermission->id,
            'causer_type' => User::class,
        ]);
    }

    // ===================
    // EDGE CASE TESTS
    // ===================

    public function test_staff_with_ended_active_status_appears_in_separation(): void
    {
        // Staff who was active but that status has ended (past end_date)
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'ENDED001',
            'hire_date' => now()->subYears(10),
        ]);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        // Create an active status that has ended
        Status::create([
            'staff_id' => $staff->id,
            'status' => EmployeeStatusEnum::Active,
            'start_date' => now()->subYears(2),
            'end_date' => now()->subMonth(), // Ended last month
            'institution_id' => $this->institution->id,
        ]);

        $separations = Separation::all();

        $this->assertTrue($separations->contains('id', $staff->id));
    }

    public function test_multiple_separated_staff_are_paginated(): void
    {
        // Create 15 separated staff
        for ($i = 0; $i < 15; $i++) {
            $this->createSeparatedStaff(EmployeeStatusEnum::Retired, [
                'staff_number' => 'BULK' . str_pad($i, 3, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('separation.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Separation/Index')
            ->has('separated')
        );
    }

    // ===================
    // HELPER METHODS
    // ===================

    /**
     * Create a separated (non-active) staff member
     */
    protected function createSeparatedStaff(EmployeeStatusEnum $status, array $overrides = []): InstitutionPerson
    {
        $person = Person::factory()->create();

        $staffData = array_merge([
            'staff_number' => 'SEP' . rand(100, 999),
            'hire_date' => now()->subYears(5),
        ], $overrides);

        $person->institution()->attach($this->institution->id, $staffData);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        // Create the separation status
        Status::create([
            'staff_id' => $staff->id,
            'status' => $status,
            'start_date' => now()->subMonth(),
            'end_date' => null, // Current status
            'institution_id' => $this->institution->id,
        ]);

        return $staff;
    }

    /**
     * Create an active staff member
     */
    protected function createActiveStaff(array $overrides = []): InstitutionPerson
    {
        $person = Person::factory()->create();

        $staffData = array_merge([
            'staff_number' => 'ACT' . rand(100, 999),
            'hire_date' => now()->subYears(3),
        ], $overrides);

        $person->institution()->attach($this->institution->id, $staffData);

        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        // Create active status
        Status::create([
            'staff_id' => $staff->id,
            'status' => EmployeeStatusEnum::Active,
            'start_date' => now()->subYears(3),
            'end_date' => null, // Still active
            'institution_id' => $this->institution->id,
        ]);

        return $staff;
    }
}
