<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlanItem;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Status;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\LeavePlanSubmittedNotification;
use App\Notifications\LeaveRequestDecidedNotification;
use App\Notifications\LeaveRequestSubmittedNotification;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeaveManagementJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $hrUser;

    protected User $requesterUser;

    protected InstitutionPerson $requester;

    protected User $headUser;

    protected InstitutionPerson $head;

    protected User $colleagueUser;

    protected InstitutionPerson $colleague;

    protected Unit $unit;

    protected ?LeaveYear $year = null;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2030-06-01');

        $this->hrUser = User::factory()->create(['password_change_at' => now()]);
        $this->hrUser->assignRole('hr-user');

        $this->unit = Unit::factory()->create();

        [$this->requesterUser, $this->requester] = $this->makeStaff();
        [$this->headUser, $this->head] = $this->makeStaff();
        [$this->colleagueUser, $this->colleague] = $this->makeStaff();

        $this->unit->update(['head_staff_id' => $this->head->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{0: User, 1: InstitutionPerson}
     */
    private function makeStaff(): array
    {
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'hire_date' => now()->subYears(10),
        ]);
        Status::factory()->active()->create([
            'staff_id' => $staff->id,
            'institution_id' => $staff->institution_id,
        ]);
        $staff->units()->attach($this->unit->id, ['start_date' => now()->toDateString(), 'end_date' => null]);

        $user = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $user->assignRole('staff');

        return [$user, $staff];
    }

    private function activeYear2030(): LeaveYear
    {
        return $this->year ??= LeaveYear::factory()->active()->create([
            'year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2030-12-31',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeWorkingDayType(array $overrides = []): LeaveType
    {
        return LeaveType::factory()->create(array_merge([
            'counts_weekends' => false,
            'counts_holidays' => false,
            'min_notice_days' => 3,
            'is_active' => true,
            'requires_evidence' => false,
        ], $overrides));
    }

    private function entitle(LeaveType $type, int $days): LeaveEntitlement
    {
        return LeaveEntitlement::factory()->create([
            'leave_year_id' => $this->activeYear2030()->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => $days,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function storeRequest(LeaveType $type, array $overrides = []): LeaveRequest
    {
        $this->actingAs($this->requesterUser)->post(route('leave-request.store'), array_merge([
            'leave_type_id' => $type->id,
            'start_date' => '2030-07-08',
            'end_date' => '2030-07-12',
            'address_during_leave' => 'Home',
            'contact_during_leave' => '0200000000',
            'relieving_officer_id' => $this->colleague->id,
        ], $overrides))->assertRedirect()->assertSessionHasNoErrors();

        return LeaveRequest::latest('id')->firstOrFail();
    }

    public function test_seeded_roles_grant_the_journey_permissions(): void
    {
        $this->actingAs($this->hrUser)->get(route('leave-year.index'))->assertOk();
        $this->actingAs($this->hrUser)->get(route('leave-reports.index'))->assertOk();
        $this->actingAs($this->requesterUser)->get(route('leave-request.index'))->assertOk();
        $this->actingAs($this->requesterUser)->get(route('leave-plan.index'))->assertOk();
        $this->actingAs($this->headUser)->get(route('leave-approvals.index'))->assertOk();

        $this->actingAs($this->requesterUser)->get(route('leave-year.index'))->assertForbidden();
        $this->actingAs($this->requesterUser)->get(route('leave-balance-adjustment.index'))->assertForbidden();
    }

    public function test_golden_path_journey(): void
    {
        // -- Structural config (leave year/type/entitlement/holiday) is created by
        // an admin-user, not hr-user: per RolesAndPermissionsSeeder, 'create leave
        // year' / 'create leave type' / 'create leave entitlement' / 'create
        // holiday' are only granted to admin-user. hr-user only has the *view*
        // permissions for those four plus 'manage leave planning windows' — see
        // the report for this task for why that split looks like a real gap.
        $adminUser = User::factory()->create(['password_change_at' => now()]);
        $adminUser->assignRole('admin-user');

        $this->actingAs($adminUser)->post(route('leave-year.store'), [
            'year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2030-12-31', 'is_active' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->year = LeaveYear::where('year', 2030)->firstOrFail();

        $this->actingAs($adminUser)->post(route('leave-type.store'), [
            'name' => 'Annual Leave', 'code' => 'ANNUAL', 'requires_evidence' => false,
            'gender_restriction' => null, 'counts_weekends' => false, 'counts_holidays' => false,
            'min_notice_days' => 3, 'max_consecutive_days' => 20, 'max_concurrent_per_unit' => null,
            'color' => '#22c55e', 'is_active' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $type = LeaveType::where('code', 'ANNUAL')->firstOrFail();

        $this->actingAs($adminUser)->post(route('leave-entitlement.store'), [
            'leave_year_id' => $this->year->id, 'leave_type_id' => $type->id,
            'job_category_id' => null, 'days_allowed' => 20,
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Holiday on Wed 2030-07-10, inside the planned leave range.
        $this->actingAs($adminUser)->post(route('holiday.store'), [
            'leave_year_id' => $this->year->id, 'date' => '2030-07-10',
            'name' => 'Founders Day', 'is_recurring' => false,
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Planning windows ARE within hr-user's remit ('manage leave planning windows').

        $this->actingAs($this->hrUser)->post(route('leave-planning-window.store'), [
            'leave_year_id' => $this->year->id, 'opens_at' => '2030-01-01 09:00',
            'closes_at' => '2030-12-31 17:00', 'allow_after_close' => false, 'require_full_plan' => false,
        ])->assertRedirect()->assertSessionHasNoErrors();

        // -- Staff plans: Mon 2030-07-08 .. Fri 2030-07-12 (holiday excluded => 4 days)
        // Verified against LeaveDayCalculator::calculateDays(): the range is five
        // weekdays (Mon-Fri, no Sat/Sun in range) minus the Wed 07-10 holiday
        // (counts_holidays=false on this type) = 4 countable days.
        $this->actingAs($this->requesterUser)->get(route('leave-plan.index'))->assertOk();
        $this->actingAs($this->requesterUser)->post(route('leave-plan.items.store'), [
            'leave_type_id' => $type->id, 'start_date' => '2030-07-08', 'end_date' => '2030-07-12',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $item = LeavePlanItem::firstOrFail();
        $this->assertSame(4, $item->proposed_days);

        $this->actingAs($this->requesterUser)->post(route('leave-plan.submit'))->assertRedirect();
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->hrUser->id,
            'notifiable_type' => User::class,
            'type' => LeavePlanSubmittedNotification::class,
        ]);

        $balance = app(LeaveBalanceService::class);
        $this->assertSame(4, $balance->plannedDays($this->requester, $type->id, $this->year));

        // -- Staff raises the request from the plan item ---------------------------
        $this->actingAs($this->requesterUser)->post(route('leave-request.store'), [
            'leave_type_id' => $type->id, 'start_date' => '2030-07-08', 'end_date' => '2030-07-12',
            'address_during_leave' => 'Home', 'contact_during_leave' => '0200000000',
            'relieving_officer_id' => $this->colleague->id, 'leave_plan_item_id' => $item->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $leaveRequest = LeaveRequest::firstOrFail();
        $this->assertSame(4, $leaveRequest->requested_days);
        $this->assertSame($this->head->id, $leaveRequest->approver_id);
        $this->assertSame(LeaveRequestStatusEnum::Pending, $leaveRequest->status);
        $this->assertSame($leaveRequest->id, $item->fresh()->converted_request_id);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->headUser->id,
            'notifiable_type' => User::class,
            'type' => LeaveRequestSubmittedNotification::class,
        ]);

        // -- Unit head (staff role only, resolved-approver policy path) approves reduced
        $this->actingAs($this->headUser)->get(route('leave-approvals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('LeaveApproval/Index')->has('requests.data', 1));

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $leaveRequest), ['approved_days' => 3])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequestStatusEnum::Approved->value,
            'approved_days' => 3,
            'decided_by' => $this->headUser->id,
        ]);
        $this->assertDatabaseHas('leave_request_status_histories', [
            'leave_request_id' => $leaveRequest->id, 'to_status' => 'Approved', 'reason' => 'reduced',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->requesterUser->id,
            'notifiable_type' => User::class,
            'type' => LeaveRequestDecidedNotification::class,
        ]);

        // -- Calendar shows the approved leave (HR is unit-privileged) -------------
        $this->actingAs($this->hrUser)
            ->get(route('leave-calendar.index', ['month' => '2030-07', 'unit_id' => $this->unit->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('LeaveCalendar/Index')->has('entries', 1));

        // -- Staff returns early on Wed 2030-07-10: Mon+Tue used => 2 actual days --
        // Verified against LeaveRequestController::resume(): actual days are the
        // countable days from start_date (07-08) to the day before the return date
        // (07-09), i.e. Mon+Tue = 2 weekdays, no holiday in that sub-range.
        $this->actingAs($this->requesterUser)
            ->post(route('leave-request.resume', $leaveRequest), ['actual_return_date' => '2030-07-10'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => LeaveRequestStatusEnum::Completed->value,
            'actual_days' => 2,
        ]);

        // -- Everything reconciles --------------------------------------------------
        $this->requester->refresh();
        $this->assertSame(20, $balance->assignedDays($this->requester, $type->id, $this->year));
        $this->assertSame(2, $balance->takenDays($this->requester, $type->id, $this->year));
        $this->assertSame(18, $balance->remaining($this->requester, $type->id, $this->year));

        $ledgerRow = collect($balance->ledger($this->requester, $this->year))
            ->firstWhere('leave_type_id', $type->id);
        $this->assertSame(['assigned' => 20, 'planned' => 4, 'taken' => 2, 'remaining' => 18], [
            'assigned' => $ledgerRow['assigned'], 'planned' => $ledgerRow['planned'],
            'taken' => $ledgerRow['taken'], 'remaining' => $ledgerRow['remaining'],
        ]);

        $this->actingAs($this->requesterUser)->get(route('leave-balance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('LeaveBalance/Index')->has('ledger'));

        $this->actingAs($this->hrUser)->get(route('leave-reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Leave/Reports/Index')
                ->where('kpis.total_taken', 2)
                ->has('utilisationByType')
                ->has('compliance'));
    }

    public function test_decline_and_rerequest_journey(): void
    {
        Notification::fake();
        $this->activeYear2030();
        $type = $this->makeWorkingDayType();
        $this->entitle($type, 20);
        $balance = app(LeaveBalanceService::class);

        $first = $this->storeRequest($type);
        $this->assertSame(5, $first->requested_days);
        $this->assertSame(5, $balance->committedRequestDays($this->requester, $type->id, $this->year));

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.decline', $first), ['decline_reason' => 'Peak period, resubmit later'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $first->id,
            'status' => LeaveRequestStatusEnum::Declined->value,
            'decline_reason' => 'Peak period, resubmit later',
        ]);
        Notification::assertSentTo($this->requesterUser, LeaveRequestDecidedNotification::class);

        // Declined days are freed — the same window can be re-requested without overlap error.
        $this->requester->refresh();
        $this->assertSame(0, $balance->committedRequestDays($this->requester, $type->id, $this->year));

        $second = $this->storeRequest($type);
        $this->assertNotSame($first->id, $second->id);

        $this->actingAs($this->headUser)
            ->post(route('leave-approvals.approve', $second))
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $second->id,
            'status' => LeaveRequestStatusEnum::Approved->value,
            'approved_days' => 5,
        ]);
    }

    public function test_cancel_and_amend_journey(): void
    {
        Notification::fake();
        $this->activeYear2030();
        $type = $this->makeWorkingDayType();
        $this->entitle($type, 20);
        $balance = app(LeaveBalanceService::class);

        // Two approved requests: Aug 5-9 (Mon-Fri) and Sep 2-6 (Mon-Fri), 5 days each.
        $august = $this->storeRequest($type, ['start_date' => '2030-08-05', 'end_date' => '2030-08-09']);
        $september = $this->storeRequest($type, ['start_date' => '2030-09-02', 'end_date' => '2030-09-06']);
        foreach ([$august, $september] as $leaveRequest) {
            $this->actingAs($this->headUser)
                ->post(route('leave-approvals.approve', $leaveRequest))
                ->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->requester->refresh();
        $this->assertSame(10, $balance->takenDays($this->requester, $type->id, $this->year));

        // Cancel August before it starts (today is frozen at 2030-06-01) — days re-credited.
        $this->actingAs($this->requesterUser)
            ->post(route('leave-request.cancel', $august))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $august->id, 'status' => LeaveRequestStatusEnum::Cancelled->value,
        ]);
        $this->requester->refresh();
        $this->assertSame(5, $balance->takenDays($this->requester, $type->id, $this->year));

        // Amend September to Sep 9-11 (Mon-Wed, 3 days): original cancelled, new Pending linked.
        $this->actingAs($this->requesterUser)
            ->post(route('leave-request.amend', $september), ['start_date' => '2030-09-09', 'end_date' => '2030-09-11'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $september->id, 'status' => LeaveRequestStatusEnum::Cancelled->value,
        ]);
        $amended = LeaveRequest::where('amended_from_id', $september->id)->firstOrFail();
        $this->assertSame(LeaveRequestStatusEnum::Pending, $amended->status);
        $this->assertSame(3, $amended->requested_days);
        $this->assertSame($this->head->id, $amended->approver_id);
    }
}
