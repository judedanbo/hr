<?php

namespace Tests\Feature;

use App\Models\Dependent;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * These JSON lookups return staff details (names, staff and file numbers,
 * photos) and are only called from signed-in pages, so they need a session.
 *
 * Which signed-in users may read them is covered by
 * PersonReadAuthorizationTest and StaffListAuthorizationTest.
 */
class SignedOutLookupAccessTest extends TestCase
{
    use RefreshDatabase;

    private InstitutionPerson $staff;

    private Person $dependentPerson;

    private Job $rank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = InstitutionPerson::factory()->create();
        $this->staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $this->staff->institution_id,
        ]);

        $this->dependentPerson = Person::factory()->create();
        Dependent::create([
            'staff_id' => $this->staff->id,
            'person_id' => $this->dependentPerson->id,
            'relation' => 'Child',
        ]);

        $this->rank = Job::factory()->create(['institution_id' => $this->staff->institution_id]);
        $this->staff->ranks()->attach($this->rank->id, ['start_date' => now()->subYear()]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function lookups(): array
    {
        return [
            'person avatar' => ['person.avatar'],
            'person roles' => ['person-roles.show'],
            'staff member a person depends on' => ['person-roles.dependent'],
            'staff of a rank' => ['rank-staff-stats'],
        ];
    }

    #[DataProvider('lookups')]
    public function test_guest_is_sent_to_login(string $route): void
    {
        $this->get($this->urlFor($route))->assertRedirect(route('login'));
    }

    #[DataProvider('lookups')]
    public function test_user_who_has_not_changed_their_password_is_sent_to_change_it(string $route): void
    {
        $user = User::factory()->create(['password_change_at' => null]);

        $this->actingAs($user)
            ->get($this->urlFor($route))
            ->assertRedirect(route('change-password.index'));
    }

    #[DataProvider('lookups')]
    public function test_a_user_allowed_to_see_the_record_still_gets_the_lookup(string $route): void
    {
        $this->actingAs($this->userAllowedToSeeTheRecords())
            ->get($this->urlFor($route))
            ->assertOk();
    }

    public function test_guest_response_carries_no_staff_details(): void
    {
        $staffOfRank = $this->get($this->urlFor('rank-staff-stats'));
        $dependentOf = $this->get($this->urlFor('person-roles.dependent'));

        $this->assertStringNotContainsString($this->staff->staff_number, (string) $staffOfRank->getContent());
        $this->assertStringNotContainsString($this->staff->staff_number, (string) $dependentOf->getContent());
    }

    public function test_signed_in_lookups_return_the_same_data_as_before(): void
    {
        $user = $this->userAllowedToSeeTheRecords();

        $this->actingAs($user)
            ->get($this->urlFor('rank-staff-stats'))
            ->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('staff.0.staff_number', $this->staff->staff_number);

        $this->actingAs($user)
            ->get($this->urlFor('person-roles.dependent'))
            ->assertOk()
            ->assertJsonPath('staff_number.0', $this->staff->staff_number);
    }

    /**
     * The person lookups follow view all staff; the rank lookup follows the
     * Ranks screen it is shown on.
     */
    private function userAllowedToSeeTheRecords(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['view all staff', 'view job']);

        return $user;
    }

    private function urlFor(string $route): string
    {
        return match ($route) {
            'rank-staff-stats' => route($route, ['job' => $this->rank->id]),
            'person-roles.dependent' => route($route, ['person' => $this->dependentPerson->id]),
            default => route($route, ['person' => $this->staff->person_id]),
        };
    }
}
