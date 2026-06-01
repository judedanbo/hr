<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTypeControllerTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Annual Leave',
            'code' => 'ANNUAL',
            'requires_evidence' => false,
            'gender_restriction' => null,
            'counts_weekends' => false,
            'counts_holidays' => false,
            'min_notice_days' => 3,
            'max_consecutive_days' => 20,
            'max_concurrent_per_unit' => null,
            'color' => '#22c55e',
            'is_active' => true,
        ], $overrides);
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('leave-type.index'))->assertForbidden();
    }

    public function test_index_displays_leave_types(): void
    {
        LeaveType::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-type.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveType/Index')->has('leaveTypes.data', 2)->has('genders'));
    }

    public function test_store_creates_a_leave_type(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('leave-type.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('leave_types', ['code' => 'ANNUAL', 'name' => 'Annual Leave']);
    }

    public function test_store_persists_day_counting_flags(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('leave-type.store'), $this->validPayload([
                'name' => 'Maternity Leave',
                'code' => 'MAT',
                'counts_weekends' => true,
                'counts_holidays' => true,
                'gender_restriction' => 'F',
                'requires_evidence' => true,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_types', [
            'code' => 'MAT',
            'counts_weekends' => true,
            'counts_holidays' => true,
            'gender_restriction' => 'F',
            'requires_evidence' => true,
        ]);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        LeaveType::factory()->create(['code' => 'DUP']);

        $this->actingAs($this->superAdmin)
            ->post(route('leave-type.store'), $this->validPayload(['code' => 'DUP']))
            ->assertSessionHasErrors('code');
    }

    public function test_store_rejects_invalid_gender_restriction(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('leave-type.store'), $this->validPayload(['gender_restriction' => 'X']))
            ->assertSessionHasErrors('gender_restriction');
    }

    public function test_update_modifies_a_leave_type(): void
    {
        $type = LeaveType::factory()->create(['code' => 'EDIT', 'is_active' => true]);

        $this->actingAs($this->superAdmin)
            ->patch(route('leave-type.update', $type), $this->validPayload(['code' => 'EDIT', 'is_active' => false]))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_types', ['id' => $type->id, 'is_active' => false]);
    }

    public function test_delete_soft_deletes_a_leave_type(): void
    {
        $type = LeaveType::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('leave-type.delete', $type))
            ->assertRedirect();

        $this->assertSoftDeleted('leave_types', ['id' => $type->id]);
    }
}
