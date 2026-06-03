<?php

namespace Tests\Feature\Appraisal;

use App\Models\AppraisalCompetency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalCompetencyTest extends TestCase
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
        $this->actingAs($this->guestUser)->get(route('appraisal-competency.index'))->assertForbidden();
    }

    public function test_index_displays_competencies(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('appraisal-competency.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('AppraisalCompetency/Index')->has('competencies.data')->has('groups')->has('jobCategories'));
    }

    public function test_store_creates_competency(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('appraisal-competency.store'), [
                'name' => 'Customer Focus',
                'description' => 'Serves the public well.',
                'group' => 'core',
                'default_weight' => 10,
                'job_category_id' => null,
                'is_active' => true,
            ])
            ->assertRedirect(route('appraisal-competency.index'));

        $this->assertDatabaseHas('appraisal_competencies', ['name' => 'Customer Focus', 'group' => 'core']);
    }

    public function test_store_rejects_duplicate_name(): void
    {
        AppraisalCompetency::factory()->create(['name' => 'Job Knowledge']);

        $this->actingAs($this->superAdmin)
            ->post(route('appraisal-competency.store'), [
                'name' => 'Job Knowledge',
                'group' => 'core',
                'default_weight' => 10,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_validates_group_enum(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('appraisal-competency.store'), [
                'name' => 'Bad Group',
                'group' => 'nonsense',
                'default_weight' => 10,
            ])
            ->assertSessionHasErrors('group');
    }

    public function test_update_modifies_competency(): void
    {
        $competency = AppraisalCompetency::factory()->create(['name' => 'Original']);

        $this->actingAs($this->superAdmin)
            ->patch(route('appraisal-competency.update', $competency), [
                'name' => 'Updated',
                'group' => 'leadership',
                'default_weight' => 20,
            ])
            ->assertRedirect(route('appraisal-competency.index'));

        $this->assertDatabaseHas('appraisal_competencies', ['id' => $competency->id, 'name' => 'Updated', 'group' => 'leadership']);
    }

    public function test_delete_soft_deletes_competency(): void
    {
        $competency = AppraisalCompetency::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('appraisal-competency.delete', $competency))
            ->assertRedirect();

        $this->assertSoftDeleted('appraisal_competencies', ['id' => $competency->id]);
    }
}
