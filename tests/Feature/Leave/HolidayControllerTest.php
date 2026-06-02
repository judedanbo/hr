<?php

namespace Tests\Feature\Leave;

use App\Models\Holiday;
use App\Models\LeaveYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayControllerTest extends TestCase
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
        $this->actingAs($this->guestUser)->get(route('holiday.index'))->assertForbidden();
    }

    public function test_index_displays_holidays(): void
    {
        Holiday::factory()->count(2)->create();

        $this->actingAs($this->superAdmin)
            ->get(route('holiday.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Holiday/Index')->has('holidays.data', 2)->has('leaveYears'));
    }

    public function test_index_filters_by_search(): void
    {
        $year = LeaveYear::factory()->create();
        Holiday::factory()->create(['leave_year_id' => $year->id, 'name' => 'Christmas Day', 'date' => '2030-12-25']);
        Holiday::factory()->create(['leave_year_id' => $year->id, 'name' => 'New Year', 'date' => '2030-01-01']);

        $this->actingAs($this->superAdmin)
            ->get(route('holiday.index', ['search' => 'Christmas']))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Holiday/Index')->has('holidays.data', 1));
    }

    public function test_store_creates_a_holiday(): void
    {
        $year = LeaveYear::factory()->create();

        $this->actingAs($this->superAdmin)
            ->post(route('holiday.store'), [
                'leave_year_id' => $year->id,
                'date' => '2030-12-25',
                'name' => 'Christmas Day',
                'is_recurring' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('holidays', [
            'leave_year_id' => $year->id,
            'name' => 'Christmas Day',
            'is_recurring' => true,
        ]);
        $this->assertSame('2030-12-25', Holiday::query()->latest('id')->first()->date->format('Y-m-d'));
    }

    public function test_store_rejects_duplicate_date_in_same_year(): void
    {
        $year = LeaveYear::factory()->create();
        Holiday::factory()->create(['leave_year_id' => $year->id, 'date' => '2030-01-01']);

        $this->actingAs($this->superAdmin)
            ->post(route('holiday.store'), [
                'leave_year_id' => $year->id,
                'date' => '2030-01-01',
                'name' => 'New Year',
                'is_recurring' => false,
            ])
            ->assertSessionHasErrors('date');
    }

    public function test_update_modifies_a_holiday(): void
    {
        $holiday = Holiday::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->superAdmin)
            ->patch(route('holiday.update', $holiday), [
                'leave_year_id' => $holiday->leave_year_id,
                'date' => $holiday->date->format('Y-m-d'),
                'name' => 'New Name',
                'is_recurring' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('holidays', ['id' => $holiday->id, 'name' => 'New Name', 'is_recurring' => true]);
    }

    public function test_delete_soft_deletes_a_holiday(): void
    {
        $holiday = Holiday::factory()->create();

        $this->actingAs($this->superAdmin)
            ->delete(route('holiday.delete', $holiday))
            ->assertRedirect();

        $this->assertSoftDeleted('holidays', ['id' => $holiday->id]);
    }
}
