<?php

namespace Tests\Feature\Leave;

use App\Models\InstitutionPerson;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitHeadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('unit-head.index'))->assertForbidden();
    }

    public function test_index_displays_units(): void
    {
        Unit::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('unit-head.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('UnitHead/Index')->has('units.data', 2)->has('staffOptions'));
    }

    public function test_update_sets_the_unit_head(): void
    {
        $unit = Unit::factory()->create(['head_staff_id' => null]);
        $head = InstitutionPerson::factory()->create();

        $this->actingAs($this->superAdmin)
            ->patch(route('unit-head.update', $unit), ['head_staff_id' => $head->id])
            ->assertRedirect();

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'head_staff_id' => $head->id]);
    }

    public function test_update_can_clear_the_unit_head(): void
    {
        $head = InstitutionPerson::factory()->create();
        $unit = Unit::factory()->create(['head_staff_id' => $head->id]);

        $this->actingAs($this->superAdmin)
            ->patch(route('unit-head.update', $unit), ['head_staff_id' => null])
            ->assertRedirect();

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'head_staff_id' => null]);
    }
}
