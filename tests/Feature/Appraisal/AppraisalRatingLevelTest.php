<?php

namespace Tests\Feature\Appraisal;

use App\Models\AppraisalRatingLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalRatingLevelTest extends TestCase
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
        $this->actingAs($this->guestUser)->get(route('appraisal-rating-level.index'))->assertForbidden();
    }

    public function test_index_displays_seeded_levels(): void
    {
        // The default 5-level scale is seeded by AppraisalRatingLevelSeeder.
        $this->actingAs($this->superAdmin)
            ->get(route('appraisal-rating-level.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('AppraisalRatingLevel/Index')->has('levels', 5));
    }

    public function test_store_creates_level(): void
    {
        AppraisalRatingLevel::query()->delete();

        $this->actingAs($this->superAdmin)
            ->post(route('appraisal-rating-level.store'), [
                'value' => 1,
                'label' => 'Poor',
                'min_score' => 0,
                'max_score' => 49.99,
            ])
            ->assertRedirect(route('appraisal-rating-level.index'));

        $this->assertDatabaseHas('appraisal_rating_levels', ['value' => 1, 'label' => 'Poor']);
    }

    public function test_store_rejects_max_below_min(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('appraisal-rating-level.store'), [
                'value' => 9,
                'label' => 'Bad band',
                'min_score' => 80,
                'max_score' => 50,
            ])
            ->assertSessionHasErrors('max_score');
    }

    public function test_band_for_resolves_correct_level(): void
    {
        $level = AppraisalRatingLevel::bandFor(85.0);

        $this->assertNotNull($level);
        $this->assertSame('Exceeds Expectations', $level->label);
    }
}
