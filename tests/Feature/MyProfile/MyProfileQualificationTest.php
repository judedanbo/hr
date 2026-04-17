<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyProfileQualificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_user_does_not_receive_approve_permission_in_page_props(): void
    {
        $user = $this->staffUserWithProfile();

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page
                ->where(
                    'auth.permissions',
                    fn ($permissions) => ! collect($permissions)->contains('approve staff qualification'),
                )
            );
    }

    public function test_staff_role_user_can_edit_own_pending_qualification(): void
    {
        $user = $this->staffUserWithProfile();
        $qualification = Qualification::factory()->pending()->create([
            'person_id' => $user->person_id,
        ]);

        $this->actingAs($user)
            ->patch(route('qualification.update', ['qualification' => $qualification->id]), [
                'course' => 'Updated course title',
                'institution' => $qualification->institution,
                'qualification' => $qualification->qualification,
                'qualification_number' => $qualification->qualification_number,
                'level' => $qualification->level,
                'year' => $qualification->year,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('Updated course title', $qualification->fresh()->course);
    }

    public function test_staff_role_user_can_delete_own_pending_qualification(): void
    {
        $user = $this->staffUserWithProfile();
        $qualification = Qualification::factory()->pending()->create([
            'person_id' => $user->person_id,
        ]);

        $this->actingAs($user)
            ->delete(route('qualification.delete', ['qualification' => $qualification->id]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Qualification::find($qualification->id));
    }

    private function staffUserWithProfile(): User
    {
        $staff = InstitutionPerson::factory()->create();
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->assignRole('staff');

        return $user;
    }
}
