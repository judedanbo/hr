<?php

namespace Tests\Feature\Unit;

use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
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

    private function makeActiveStaff(Unit $unit, string $gender, ?Job $rank = null): InstitutionPerson
    {
        $person = Person::factory()->create(['gender' => $gender]);
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

    public function test_stats_total_male_female_span_all_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);

        $this->makeActiveStaff($root, 'M');
        $this->makeActiveStaff($child, 'M');
        $this->makeActiveStaff($child, 'F');
        $this->makeActiveStaff($grand, 'F');
        $this->makeActiveStaff($grand, 'M');

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('stats.total', 5)
            ->where('stats.male', 3)
            ->where('stats.female', 2)
            ->where('stats.direct_subs', 1)
            ->where('stats.total_descendants', 2)
        );
    }

    public function test_sub_unit_cards_show_recursive_counts(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);

        $this->makeActiveStaff($child, 'M');
        $this->makeActiveStaff($grand, 'F');
        $this->makeActiveStaff($grand, 'F');

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->has('subs', 1)
            ->where('subs.0.staff_count', 3)
            ->where('subs.0.male_staff', 1)
            ->where('subs.0.female_staff', 2)
        );
    }

    public function test_rank_distribution_aggregates_across_all_descendants(): void
    {
        $root = Unit::factory()->create(['unit_id' => null]);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id]);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id]);
        $rank = Job::factory()->create(['name' => 'Officer']);

        $this->makeActiveStaff($root, 'M', $rank);
        $this->makeActiveStaff($child, 'F', $rank);
        $this->makeActiveStaff($grand, 'M', $rank);

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $root->id]));

        $response->assertInertia(fn ($page) => $page
            ->has('rank_distribution', 1)
            ->where('rank_distribution.0.name', 'Officer')
            ->where('rank_distribution.0.count', 3)
        );
    }

    public function test_initial_staff_page_uses_paginator_shape(): void
    {
        $unit = Unit::factory()->create();
        for ($i = 0; $i < 18; $i++) {
            $this->makeActiveStaff($unit, 'M');
        }

        $response = $this->actingAs($this->user)->get(route('unit.show', ['unit' => $unit->id]));

        $response->assertInertia(fn ($page) => $page
            ->where('staff.meta.per_page', 15)
            ->where('staff.meta.total', 18)
            ->has('staff.data', 15)
            ->has('filter_options.genders', 2)
        );
    }
}
