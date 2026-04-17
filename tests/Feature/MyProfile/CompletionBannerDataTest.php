<?php

namespace Tests\Feature\MyProfile;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the shared-prop contract that the CompletionBanner component
 * reads from. The banner has no server-rendered output, but its
 * `shouldShow` predicate depends entirely on `auth.user.person_id`,
 * `auth.has_photo`, and `auth.qualifications_count` being present on
 * the admin landing page (`institution.show`). If any of those three
 * disappears, the banner silently stops nagging users who haven't
 * completed their profiles — we catch that here.
 */
class CompletionBannerDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_landing_exposes_banner_props_when_profile_incomplete(): void
    {
        $institution = Institution::factory()->create();
        $staff = $this->createActiveStaff();
        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->assignRole('super-administrator');

        $this->actingAs($user)
            ->get(route('institution.show', $institution))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.person_id', $staff->person_id)
                ->where('auth.has_photo', false)
                ->where('auth.qualifications_count', 0)
            );
    }

    public function test_institution_landing_banner_props_reflect_completed_profile(): void
    {
        $institution = Institution::factory()->create();
        $staff = $this->createActiveStaff();
        $staff->person->update(['image' => 'avatars/example.jpg']);
        Qualification::factory()->create(['person_id' => $staff->person_id]);
        $user = User::factory()->create(['person_id' => $staff->person_id]);
        $user->assignRole('super-administrator');

        $this->actingAs($user)
            ->get(route('institution.show', $institution))
            ->assertInertia(fn ($page) => $page
                ->where('auth.has_photo', true)
                ->where('auth.qualifications_count', 1)
            );
    }

    public function test_institution_landing_returns_null_banner_props_for_unlinked_user(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['person_id' => null]);
        $user->assignRole('super-administrator');

        $this->actingAs($user)
            ->get(route('institution.show', $institution))
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.person_id', null)
                ->where('auth.has_photo', null)
                ->where('auth.qualifications_count', null)
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
