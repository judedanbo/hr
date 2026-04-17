<?php

namespace Tests\Feature\Unit;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\UnitsPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('view unit');
    }

    /**
     * Create an active staff record attached to $unit with optional gender and rank.
     */
    private function makeActiveStaff(Unit $unit, ?string $gender = null, ?Job $rank = null): InstitutionPerson
    {
        $person = Person::factory()->create(
            $gender !== null ? ['gender' => $gender] : []
        );
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $unit->institution_id,
            'person_id' => $person->id,
        ]);
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $unit->institution_id,
        ]);
        $staff->units()->attach($unit->id, ['start_date' => now()->subYear()]);
        if ($rank) {
            $staff->ranks()->attach($rank->id, ['start_date' => now()->subYear()]);
        }

        return $staff;
    }

    public function test_staff_endpoint_returns_staff_from_all_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);

        $this->makeActiveStaff($root);
        $this->makeActiveStaff($child);
        $this->makeActiveStaff($grand);

        $response = $this->actingAs($this->user)->get(route('unit.staff', ['unit' => $root->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Unit/Show')
            ->has('staff.data', 3)
            ->has('staff.meta')
        );
    }

    public function test_staff_endpoint_paginates_at_fifteen_per_page(): void
    {
        $unit = Unit::factory()->create();
        for ($i = 0; $i < 20; $i++) {
            $this->makeActiveStaff($unit);
        }

        $response = $this->actingAs($this->user)->get(route('unit.staff', ['unit' => $unit->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.per_page', 15)
            ->where('staff.meta.total', 20)
            ->has('staff.data', 15)
        );
    }

    public function test_staff_endpoint_filters_by_gender(): void
    {
        $unit = Unit::factory()->create();
        $this->makeActiveStaff($unit, 'M');
        $this->makeActiveStaff($unit, 'M');
        $this->makeActiveStaff($unit, 'F');

        $response = $this->actingAs($this->user)
            ->get(route('unit.staff', ['unit' => $unit->id, 'gender' => 'F']));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.total', 1)
        );
    }

    public function test_staff_endpoint_filters_by_rank(): void
    {
        $unit = Unit::factory()->create();
        $rank = Job::factory()->create();
        $other = Job::factory()->create();

        $this->makeActiveStaff($unit, null, $rank);
        $this->makeActiveStaff($unit, null, $other);

        $response = $this->actingAs($this->user)
            ->get(route('unit.staff', ['unit' => $unit->id, 'rank_id' => $rank->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.total', 1)
        );
    }

    public function test_rank_filter_matches_only_current_rank_holders(): void
    {
        $unit = Unit::factory()->create();
        $pastRank = Job::factory()->create();
        $currentRank = Job::factory()->create();

        $promoted = $this->makeActiveStaff($unit, null, $currentRank);
        $promoted->ranks()->updateExistingPivot($currentRank->id, [], false);
        $promoted->ranks()->attach($pastRank->id, [
            'start_date' => now()->subYears(3),
            'end_date' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('unit.staff', ['unit' => $unit->id, 'rank_id' => $pastRank->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.total', 0)
        );
    }

    public function test_category_filter_matches_only_current_rank_category(): void
    {
        $unit = Unit::factory()->create();
        $pastCategory = \App\Models\JobCategory::factory()->create();
        $currentCategory = \App\Models\JobCategory::factory()->create();
        $pastRank = Job::factory()->create(['job_category_id' => $pastCategory->id]);
        $currentRank = Job::factory()->create(['job_category_id' => $currentCategory->id]);

        $promoted = $this->makeActiveStaff($unit, null, $currentRank);
        $promoted->ranks()->attach($pastRank->id, [
            'start_date' => now()->subYears(3),
            'end_date' => now()->subYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('unit.staff', ['unit' => $unit->id, 'job_category_id' => $pastCategory->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.total', 0)
        );
    }

    public function test_filter_options_include_ranks_from_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $rank = Job::factory()->create(['name' => 'Director']);

        $this->makeActiveStaff($child, null, $rank);

        $response = $this->actingAs($this->user)->get(route('unit.staff', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->has('filter_options.ranks', 1)
            ->where('filter_options.ranks.0.label', 'Director')
        );
    }

    public function test_filters_prop_has_integer_types_for_id_fields(): void
    {
        $unit = Unit::factory()->create();
        $rank = Job::factory()->create();

        $response = $this->actingAs($this->user)->get(
            route('unit.show', ['unit' => $unit->id, 'rank_id' => $rank->id, 'age_from' => 25])
        );

        $response->assertInertia(fn ($page) => $page
            ->where('filters.rank_id', $rank->id)
            ->where('filters.age_from', 25)
        );
    }

    public function test_unauthorized_user_is_redirected(): void
    {
        $unit = Unit::factory()->create();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)->get(route('unit.staff', ['unit' => $unit->id]));

        $response->assertStatus(403);
    }
}
