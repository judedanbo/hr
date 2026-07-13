<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffShowAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
    }

    private function makeActiveStaff(): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'person_id' => Person::factory()->create()->id,
        ]);

        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $this->institution->id,
        ]);

        return $staff;
    }

    /**
     * @param  array<string>  $permissions
     */
    private function userFor(InstitutionPerson $staff, array $permissions): User
    {
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    public function test_user_with_view_all_staff_who_is_also_employee_can_view_other_staff(): void
    {
        $adminStaff = $this->makeActiveStaff();
        $admin = $this->userFor($adminStaff, ['view staff', 'view all staff']);

        $otherStaff = $this->makeActiveStaff();

        $response = $this->actingAs($admin)
            ->get(route('staff.show', ['staff' => $otherStaff->id]));

        $response->assertStatus(200);
    }

    public function test_staff_only_user_can_view_their_own_record(): void
    {
        $staff = $this->makeActiveStaff();
        $user = $this->userFor($staff, ['view staff']);

        $response = $this->actingAs($user)
            ->get(route('staff.show', ['staff' => $staff->id]));

        $response->assertStatus(200);
    }

    public function test_staff_only_user_cannot_view_another_staff(): void
    {
        $staff = $this->makeActiveStaff();
        $user = $this->userFor($staff, ['view staff']);

        $otherStaff = $this->makeActiveStaff();

        $response = $this->actingAs($user)
            ->get(route('staff.show', ['staff' => $otherStaff->id]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'You do not have permission to view details of this staff');
    }

    public function test_user_without_view_staff_permission_is_forbidden(): void
    {
        $staff = $this->makeActiveStaff();
        $user = $this->userFor($staff, []);

        $response = $this->actingAs($user)
            ->get(route('staff.show', ['staff' => $staff->id]));

        $response->assertForbidden();
    }
}
