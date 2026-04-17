<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileQualificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_user_does_not_receive_approve_permission_in_page_props(): void
    {
        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page
                ->where(
                    'auth.permissions',
                    fn ($permissions) => ! collect($permissions)->contains('approve staff qualification'),
                )
            );
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
