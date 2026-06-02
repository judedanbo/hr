<?php

namespace Tests\Feature\Leave;

use App\Enums\LeavePlanStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlanningWindow;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Status;
use App\Models\User;
use App\Notifications\LeavePlanSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeavePlanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected InstitutionPerson $staff;

    protected LeaveYear $year;

    protected LeaveType $type;

    protected LeavePlanningWindow $window;

    protected function setUp(): void
    {
        parent::setUp();

        $person = Person::factory()->create();
        $this->staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create([
            'staff_id' => $this->staff->id,
            'institution_id' => $this->staff->institution_id,
        ]);

        $this->staffUser = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $this->staffUser->givePermissionTo(['view leave plans', 'submit leave plan']);

        $this->year = LeaveYear::factory()->active()->create([
            'year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2030-12-31',
        ]);
        $this->window = LeavePlanningWindow::factory()->open()->create(['leave_year_id' => $this->year->id]);
        $this->type = LeaveType::factory()->calendarDays()->create(['is_active' => true]);
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $this->year->id,
            'leave_type_id' => $this->type->id,
            'job_category_id' => null,
            'days_allowed' => 20,
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('leave-plan.index'))->assertRedirect(route('login'));
    }

    public function test_index_forbidden_without_permission(): void
    {
        $guest = User::factory()->create(['password_change_at' => now()]);
        $this->actingAs($guest)->get(route('leave-plan.index'))->assertForbidden();
    }

    public function test_index_creates_a_draft_plan_and_renders(): void
    {
        $this->actingAs($this->staffUser)
            ->get(route('leave-plan.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeavePlan/Index')->where('windowOpen', true)->has('ledger'));

        $this->assertDatabaseHas('leave_plans', [
            'staff_id' => $this->staff->id,
            'leave_year_id' => $this->year->id,
            'status' => LeavePlanStatusEnum::Draft->value,
        ]);
    }

    public function test_store_item_adds_within_entitlement(): void
    {
        $this->actingAs($this->staffUser)
            ->post(route('leave-plan.items.store'), [
                'leave_type_id' => $this->type->id,
                'start_date' => '2030-06-10',
                'end_date' => '2030-06-14',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leave_plan_items', [
            'leave_type_id' => $this->type->id,
            'proposed_days' => 5,
        ]);
    }

    public function test_store_item_blocked_when_window_closed(): void
    {
        $this->window->update([
            'opens_at' => now()->subWeeks(2),
            'closes_at' => now()->subDay(),
            'allow_after_close' => false,
        ]);

        $this->actingAs($this->staffUser)
            ->post(route('leave-plan.items.store'), [
                'leave_type_id' => $this->type->id,
                'start_date' => '2030-06-10',
                'end_date' => '2030-06-14',
            ])
            ->assertSessionHasErrors('plan');
    }

    public function test_store_item_blocked_on_overlap(): void
    {
        $this->actingAs($this->staffUser)->post(route('leave-plan.items.store'), [
            'leave_type_id' => $this->type->id,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $this->actingAs($this->staffUser)
            ->post(route('leave-plan.items.store'), [
                'leave_type_id' => $this->type->id,
                'start_date' => '2030-06-12',
                'end_date' => '2030-06-16',
            ])
            ->assertSessionHasErrors('start_date');
    }

    public function test_store_item_blocked_when_exceeding_assigned(): void
    {
        $smallType = LeaveType::factory()->calendarDays()->create(['is_active' => true]);
        LeaveEntitlement::factory()->create([
            'leave_year_id' => $this->year->id,
            'leave_type_id' => $smallType->id,
            'job_category_id' => null,
            'days_allowed' => 3,
        ]);

        $this->actingAs($this->staffUser)
            ->post(route('leave-plan.items.store'), [
                'leave_type_id' => $smallType->id,
                'start_date' => '2030-06-10',
                'end_date' => '2030-06-14',
            ])
            ->assertSessionHasErrors('leave_type_id');
    }

    public function test_preview_days_returns_count(): void
    {
        $this->actingAs($this->staffUser)
            ->getJson(route('leave-plan.preview-days', [
                'leave_type_id' => $this->type->id,
                'start_date' => '2030-06-10',
                'end_date' => '2030-06-14',
            ]))
            ->assertStatus(200)
            ->assertJson(['days' => 5]);
    }

    public function test_submit_marks_submitted_and_notifies_hr(): void
    {
        Notification::fake();
        $hr = User::factory()->create(['password_change_at' => now()]);
        $hr->givePermissionTo('view all leave plans');

        $this->actingAs($this->staffUser)
            ->post(route('leave-plan.submit'))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_plans', [
            'staff_id' => $this->staff->id,
            'leave_year_id' => $this->year->id,
            'status' => LeavePlanStatusEnum::Submitted->value,
        ]);

        Notification::assertSentTo($hr, LeavePlanSubmittedNotification::class);
    }
}
