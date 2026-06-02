<?php

namespace Tests\Feature\Leave;

use App\Models\Holiday;
use App\Models\LeaveEntitlement;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveYearControllerTest extends TestCase
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

    public function test_index_requires_authentication(): void
    {
        $this->get(route('leave-year.index'))->assertRedirect(route('login'));
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('leave-year.index'))->assertForbidden();
    }

    public function test_index_displays_leave_years(): void
    {
        LeaveYear::factory()->count(3)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('leave-year.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveYear/Index')->has('leaveYears.data', 3));
    }

    public function test_index_filters_by_search(): void
    {
        LeaveYear::factory()->create(['year' => 2040]);
        LeaveYear::factory()->create(['year' => 2041]);

        $this->actingAs($this->superAdmin)
            ->get(route('leave-year.index', ['search' => '2040']))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveYear/Index')->has('leaveYears.data', 1));
    }

    public function test_store_creates_a_leave_year(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('leave-year.store'), [
            'year' => 2030,
            'start_date' => '2030-01-01',
            'end_date' => '2030-12-31',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_years', ['year' => 2030, 'is_active' => true]);
    }

    public function test_store_rejects_duplicate_year(): void
    {
        LeaveYear::factory()->create(['year' => 2031]);

        $this->actingAs($this->superAdmin)
            ->post(route('leave-year.store'), [
                'year' => 2031,
                'start_date' => '2031-01-01',
                'end_date' => '2031-12-31',
                'is_active' => false,
            ])
            ->assertSessionHasErrors('year');
    }

    public function test_store_rejects_end_date_before_start_date(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('leave-year.store'), [
                'year' => 2032,
                'start_date' => '2032-12-31',
                'end_date' => '2032-01-01',
                'is_active' => false,
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_update_modifies_a_leave_year(): void
    {
        $year = LeaveYear::factory()->create(['year' => 2033, 'is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->patch(route('leave-year.update', $year), [
                'year' => 2033,
                'start_date' => '2033-01-01',
                'end_date' => '2033-12-31',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_years', ['id' => $year->id, 'is_active' => true]);
    }

    public function test_delete_soft_deletes_a_leave_year(): void
    {
        $year = LeaveYear::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('leave-year.delete', $year))
            ->assertRedirect();

        $this->assertSoftDeleted('leave_years', ['id' => $year->id]);
    }

    public function test_clone_copies_entitlements_and_recurring_holidays(): void
    {
        $source = LeaveYear::factory()->create(['year' => 2040]);
        $target = LeaveYear::factory()->create(['year' => 2041]);
        $type = LeaveType::factory()->create();

        LeaveEntitlement::factory()->create([
            'leave_year_id' => $source->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => 18,
        ]);
        Holiday::factory()->recurring()->create([
            'leave_year_id' => $source->id,
            'date' => '2040-12-25',
            'name' => 'Christmas Day',
        ]);
        Holiday::factory()->create([
            'leave_year_id' => $source->id,
            'date' => '2040-06-15',
            'name' => 'One-off Holiday',
            'is_recurring' => false,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('leave-year.clone', $target), ['source_leave_year_id' => $source->id])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_entitlements', [
            'leave_year_id' => $target->id,
            'leave_type_id' => $type->id,
            'days_allowed' => 18,
        ]);
        $clonedHoliday = $target->holidays()->where('name', 'Christmas Day')->first();
        $this->assertNotNull($clonedHoliday);
        $this->assertSame('2041-12-25', $clonedHoliday->date->format('Y-m-d'));
        $this->assertDatabaseMissing('holidays', [
            'leave_year_id' => $target->id,
            'name' => 'One-off Holiday',
        ]);
    }
}
