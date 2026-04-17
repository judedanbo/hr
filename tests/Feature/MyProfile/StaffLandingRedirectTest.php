<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StaffLandingRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('staff');
    }

    public function test_staff_only_user_is_redirected_to_my_profile(): void
    {
        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->assignRole('staff');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('my-profile.show'));
    }

    private function createActiveStaff(): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }
}
