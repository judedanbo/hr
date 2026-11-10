<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\Qualification;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Lists that name other members of staff are not open to everyone who is
 * signed in. Each takes the permission of the screen it belongs to; a staff
 * list with no screen permission of its own takes view all staff.
 */
class StaffListAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_open_any_list_of_other_staff(): void
    {
        $user = $this->staffUser();

        foreach ($this->staffLists() as $name => $url) {
            $response = $this->actingAs($user)->get($url);

            $this->assertSame(403, $response->getStatusCode(), "The staff role should not open: {$name}");
        }
    }

    public function test_signed_out_visitors_are_sent_to_the_login_page(): void
    {
        foreach ($this->staffLists() as $name => $url) {
            $response = $this->get($url);

            $this->assertTrue($response->isRedirect(route('login')), "A signed-out visitor should not open: {$name}");
        }
    }

    public function test_view_all_staff_opens_the_institution_staff_list_and_the_recruitment_details(): void
    {
        $user = $this->userWithPermissions(['view all staff']);
        $lists = $this->staffLists();

        $this->actingAs($user)->get($lists['institution staff'])->assertOk();
        $this->actingAs($user)->get($lists['recruitment details'])->assertOk();
    }

    public function test_view_dashboard_opens_the_dashboard_drill_down(): void
    {
        $user = $this->userWithPermissions(['view dashboard']);

        $this->actingAs($user)
            ->get($this->staffLists()['dashboard drill-down'])
            ->assertOk()
            ->assertJsonStructure(['staff', 'filter', 'count']);
    }

    public function test_view_staff_status_opens_the_status_lists(): void
    {
        $user = $this->userWithPermissions(['view staff status']);
        $lists = $this->staffLists();

        $this->actingAs($user)->get($lists['statuses of an institution'])->assertOk();
        $this->actingAs($user)->get($lists['staff with one status'])->assertOk();
    }

    public function test_view_job_opens_the_staff_lists_of_a_rank(): void
    {
        $user = $this->userWithPermissions(['view job']);
        $lists = $this->staffLists();

        foreach (['rank staff', 'rank staff due for promotion', 'rank active staff', 'rank staff of all time', 'rank staff summary'] as $name) {
            $response = $this->actingAs($user)->get($lists[$name]);

            $this->assertSame(200, $response->getStatusCode(), "The view job permission should open: {$name}");
        }
    }

    public function test_each_list_needs_the_permission_of_its_own_screen(): void
    {
        $user = $this->userWithPermissions(['view all staff']);
        $lists = $this->staffLists();

        foreach (['dashboard drill-down', 'statuses of an institution', 'rank staff', 'rank staff summary'] as $name) {
            $response = $this->actingAs($user)->get($lists[$name]);

            $this->assertSame(403, $response->getStatusCode(), "view all staff alone should not open: {$name}");
        }
    }

    // -----------------------------------------------------------------------
    // The qualifications list
    // -----------------------------------------------------------------------

    public function test_staff_see_only_their_own_qualifications_in_the_list(): void
    {
        $user = $this->staffUser();
        $mine = Qualification::factory()->approved()->create(['person_id' => $user->person_id]);
        Qualification::factory()->approved()->create(['person_id' => $this->activeStaff()->person_id]);

        $this->actingAs($user)
            ->get(route('qualification.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Qualification/Index')
                ->has('qualifications.data', 1)
                ->where('qualifications.data.0.id', $mine->id));
    }

    public function test_a_user_with_no_staff_record_sees_no_qualifications(): void
    {
        $user = User::factory()->create(['person_id' => null]);
        $user->assignRole('staff');
        Qualification::factory()->approved()->create(['person_id' => $this->activeStaff()->person_id]);

        $this->actingAs($user)
            ->get(route('qualification.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('qualifications.data', 0));
    }

    public function test_view_all_staff_qualifications_lists_everyones(): void
    {
        $user = $this->userWithPermissions(['view staff qualification', 'view all staff qualifications']);
        $this->twoColleaguesWithApprovedQualifications();

        $this->actingAs($user)
            ->get(route('qualification.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('qualifications.data', 2));
    }

    public function test_view_all_staff_lists_everyones_qualifications(): void
    {
        $user = $this->userWithPermissions(['view staff qualification', 'view all staff']);
        $this->twoColleaguesWithApprovedQualifications();

        $this->actingAs($user)
            ->get(route('qualification.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('qualifications.data', 2));
    }

    public function test_an_approver_still_lists_everyones_qualifications_pending_ones_included(): void
    {
        $user = $this->userWithPermissions(['view staff qualification', 'approve staff qualification']);
        Qualification::factory()->approved()->create(['person_id' => $this->activeStaff()->person_id]);
        Qualification::factory()->pending()->create(['person_id' => $this->activeStaff()->person_id]);

        $this->actingAs($user)
            ->get(route('qualification.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('qualifications.data', 2));
    }

    /**
     * Every route that lists other members of staff, by a readable name.
     *
     * @return array<string, string>
     */
    private function staffLists(): array
    {
        $institution = Institution::factory()->create();
        $rank = Job::factory()->create();
        $status = Status::query()->where('staff_id', $this->activeStaff()->id)->firstOrFail();

        return [
            'institution staff' => route('institution.staffs', ['institution' => $institution->id]),
            'institution staff member' => route('institution.staff', ['institution' => $institution->id, 'staff' => $status->staff_id]),
            'dashboard drill-down' => route('institution.staff-filter', ['institution' => $institution->id]),
            'statuses of an institution' => route('institution-status.index', ['institution' => $institution->id]),
            'staff with one status' => route('institution-status.show', ['institution' => $status->institution_id, 'status' => $status->id]),
            'rank staff' => route('rank-staff.index', ['rank' => $rank->id]),
            'rank staff due for promotion' => route('rank-staff.promote', ['rank' => $rank->id]),
            'rank active staff' => route('rank-staff.active', ['rank' => $rank->id]),
            'rank staff of all time' => route('rank-staff.all', ['rank' => $rank->id]),
            'rank staff summary' => route('rank-staff-stats', ['job' => $rank->id]),
            'recruitment details' => route('report.recruitment.details'),
        ];
    }

    private function twoColleaguesWithApprovedQualifications(): void
    {
        Qualification::factory()->approved()->create(['person_id' => $this->activeStaff()->person_id]);
        Qualification::factory()->approved()->create(['person_id' => $this->activeStaff()->person_id]);
    }

    /**
     * A signed-in member of staff with only the staff role.
     */
    private function staffUser(): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);
        $user->assignRole('staff');

        return $user;
    }

    /**
     * A signed-in member of staff holding the given permissions directly.
     *
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['person_id' => $this->activeStaff()->person_id]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        return $user;
    }

    private function activeStaff(?Person $person = null): InstitutionPerson
    {
        $staff = InstitutionPerson::factory()->create(
            $person ? ['person_id' => $person->id] : []
        );
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $staff->institution_id,
        ]);

        return $staff;
    }
}
