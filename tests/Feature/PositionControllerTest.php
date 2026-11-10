<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['password_change_at' => now()]);
        $this->admin->givePermissionTo([
            'view all positions',
            'view position',
            'create position',
            'update position',
            'delete position',
        ]);
    }

    private function unprivilegedUser(): User
    {
        return User::factory()->create(['password_change_at' => now()]);
    }

    // ===================
    // AUTHORIZATION
    // ===================

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->unprivilegedUser())
            ->get(route('position.index'))
            ->assertForbidden();
    }

    public function test_store_requires_permission(): void
    {
        $this->actingAs($this->unprivilegedUser())
            ->post(route('position.store'), ['name' => 'Auditor'])
            ->assertForbidden();
    }

    public function test_delete_requires_permission(): void
    {
        $position = Position::factory()->create();

        $this->actingAs($this->unprivilegedUser())
            ->delete(route('position.delete', ['position' => $position->id]))
            ->assertForbidden();
    }

    // ===================
    // CRUD
    // ===================

    public function test_can_list_positions(): void
    {
        Position::factory()->create(['name' => 'Regional Auditor']);

        $this->actingAs($this->admin)
            ->get(route('position.index'))
            ->assertSuccessful();
    }

    public function test_can_create_a_position(): void
    {
        $this->actingAs($this->admin)
            ->post(route('position.store'), ['name' => 'Regional Auditor'])
            ->assertRedirect(route('position.index'));

        $this->assertDatabaseHas('positions', ['name' => 'Regional Auditor']);
    }

    public function test_duplicate_names_are_rejected(): void
    {
        Position::factory()->create(['name' => 'Regional Auditor']);

        $this->actingAs($this->admin)
            ->post(route('position.store'), ['name' => 'Regional Auditor'])
            ->assertSessionHasErrors('name');
    }

    /**
     * The uniqueness rule used to have no ignore(), so saving the edit form
     * without renaming failed validation.
     */
    public function test_a_position_can_be_saved_without_renaming_it(): void
    {
        $position = Position::factory()->create(['name' => 'Regional Auditor']);

        $this->actingAs($this->admin)
            ->patch(route('position.update', ['position' => $position->id]), [
                'name' => 'Regional Auditor',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_can_rename_a_position(): void
    {
        $position = Position::factory()->create(['name' => 'Regional Auditor']);

        $this->actingAs($this->admin)
            ->patch(route('position.update', ['position' => $position->id]), [
                'name' => 'Director of Audit',
            ])
            ->assertRedirect(route('position.index'));

        $this->assertSame('Director of Audit', $position->fresh()->name);
    }

    public function test_a_name_freed_by_a_soft_delete_can_be_reused(): void
    {
        Position::factory()->create(['name' => 'Regional Auditor'])->delete();

        $this->actingAs($this->admin)
            ->post(route('position.store'), ['name' => 'Regional Auditor'])
            ->assertSessionHasNoErrors();
    }

    // ===================
    // DELETION
    // ===================

    /**
     * delete() used to forceDelete(), which cascades through the
     * position_staff foreign key and destroyed employment history.
     */
    public function test_deleting_a_position_soft_deletes_it_and_keeps_its_history(): void
    {
        $institution = Institution::factory()->create();
        $position = Position::factory()->create(['name' => 'Regional Auditor']);

        $person = Person::factory()->create();
        $person->institution()->attach($institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now()->subYears(5),
        ]);
        $staff = InstitutionPerson::where('person_id', $person->id)->first();

        $assignment = $staff->positionAssignments()->create([
            'position_id' => $position->id,
            'start_date' => now()->subYear(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('position.delete', ['position' => $position->id]))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('positions', ['id' => $position->id]);
        $this->assertDatabaseHas('position_staff', [
            'id' => $assignment->id,
            'position_id' => $position->id,
        ]);
    }

    /**
     * Deleting from the position's own page must not bounce back to a URL
     * whose route binding no longer resolves.
     */
    public function test_deleting_redirects_to_the_index_not_back_to_the_deleted_page(): void
    {
        $position = Position::factory()->create();

        $this->actingAs($this->admin)
            ->from(route('position.show', ['position' => $position->id]))
            ->delete(route('position.delete', ['position' => $position->id]))
            ->assertRedirect(route('position.index'));
    }

    // ===================
    // FILTERS
    // ===================

    public function test_the_search_filter_is_applied(): void
    {
        Position::factory()->create(['name' => 'Regional Auditor']);
        Position::factory()->create(['name' => 'Chief Accountant']);

        $response = $this->actingAs($this->admin)
            ->get(route('position.index', ['search' => 'Auditor']));

        $response->assertSuccessful();

        $names = collect($response->viewData('page')['props']['positions']['data'])
            ->pluck('name');

        $this->assertContains('Regional Auditor', $names);
        $this->assertNotContains('Chief Accountant', $names);
    }

    public function test_soft_deleted_positions_are_hidden_unless_requested(): void
    {
        Position::factory()->create(['name' => 'Regional Auditor'])->delete();

        $default = $this->actingAs($this->admin)->get(route('position.index'));
        $this->assertNotContains(
            'Regional Auditor',
            collect($default->viewData('page')['props']['positions']['data'])->pluck('name')
        );

        $withTrashed = $this->actingAs($this->admin)
            ->get(route('position.index', ['trashed' => 'with']));
        $this->assertContains(
            'Regional Auditor',
            collect($withTrashed->viewData('page')['props']['positions']['data'])->pluck('name')
        );
    }
}
