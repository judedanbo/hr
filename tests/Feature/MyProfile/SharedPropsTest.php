<?php

namespace Tests\Feature\MyProfile;

use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_props_include_person_id_has_photo_and_qualifications_count(): void
    {
        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.person_id', $staff->person_id)
                ->where('auth.has_photo', false)
                ->where('auth.qualifications_count', 0)
            );
    }

    public function test_has_photo_flips_true_when_person_image_is_set(): void
    {
        $staff = $this->createActiveStaff();
        $staff->person->update(['image' => 'avatars/example.jpg']);
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page->where('auth.has_photo', true));
    }

    public function test_qualifications_count_reflects_stored_qualifications(): void
    {
        $staff = $this->createActiveStaff();
        Qualification::factory()->count(3)->create(['person_id' => $staff->person_id]);
        $user = User::factory()->create(['person_id' => $staff->person_id]);

        $this->actingAs($user)
            ->get(route('my-profile.show'))
            ->assertInertia(fn ($page) => $page->where('auth.qualifications_count', 3));
    }

    public function test_props_are_null_for_users_without_person_id(): void
    {
        $user = User::factory()->create(['person_id' => null]);

        // my-profile.show 403s for users without a person_id (no Inertia response).
        // Use the change-password page which is always reachable by any auth'd user.
        $this->actingAs($user)
            ->get(route('change-password.index'))
            ->assertInertia(fn ($page) => $page
                ->where('auth.has_photo', null)
                ->where('auth.qualifications_count', null)
            );
    }

    /**
     * Mirrors the helper from MyProfileShowTest — creates an InstitutionPerson
     * that the `active()` scope returns.
     */
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
