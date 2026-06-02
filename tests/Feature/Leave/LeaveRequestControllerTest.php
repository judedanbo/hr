<?php

namespace Tests\Feature\Leave;

use App\Enums\LeaveRequestStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\LeaveEntitlement;
use App\Models\LeavePlan;
use App\Models\LeavePlanItem;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveYear;
use App\Models\Person;
use App\Models\Status;
use App\Models\User;
use App\Notifications\LeaveRequestSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;

    protected InstitutionPerson $staff;

    protected LeaveYear $year;

    protected LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2030-06-01');

        $person = Person::factory()->create(['gender' => 'F']);
        $this->staff = InstitutionPerson::factory()->create([
            'person_id' => $person->id,
            'hire_date' => now()->subYears(10),
        ]);
        Status::factory()->active()->create([
            'staff_id' => $this->staff->id,
            'institution_id' => $this->staff->institution_id,
        ]);

        $this->staffUser = User::factory()->create(['person_id' => $person->id, 'password_change_at' => now()]);
        $this->staffUser->givePermissionTo(['view leave requests', 'create leave request', 'update leave request', 'cancel leave request']);

        $this->year = LeaveYear::factory()->active()->create([
            'year' => 2030, 'start_date' => '2030-01-01', 'end_date' => '2030-12-31',
        ]);
        $this->type = LeaveType::factory()->calendarDays()->create(['is_active' => true, 'requires_evidence' => false]);
        $this->entitle($this->type, 20);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function entitle(LeaveType $type, int $days): LeaveEntitlement
    {
        return LeaveEntitlement::factory()->create([
            'leave_year_id' => $this->year->id,
            'leave_type_id' => $type->id,
            'job_category_id' => null,
            'days_allowed' => $days,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'leave_type_id' => $this->type->id,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
            'address_during_leave' => 'Home address',
            'contact_during_leave' => '0200000000',
        ], $overrides);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('leave-request.index'))->assertRedirect(route('login'));
    }

    public function test_index_forbidden_without_permission(): void
    {
        $guest = User::factory()->create(['password_change_at' => now()]);
        $this->actingAs($guest)->get(route('leave-request.index'))->assertForbidden();
    }

    public function test_index_lists_own_requests(): void
    {
        LeaveRequest::factory()->create(['staff_id' => $this->staff->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id]);

        $this->actingAs($this->staffUser)
            ->get(route('leave-request.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('LeaveRequest/Index')->has('requests.data', 1));
    }

    public function test_store_creates_a_pending_request_and_notifies_hr(): void
    {
        Notification::fake();
        $hr = User::factory()->create(['password_change_at' => now()]);
        $hr->givePermissionTo('view all leave requests');

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'staff_id' => $this->staff->id,
            'leave_type_id' => $this->type->id,
            'requested_days' => 5,
            'status' => LeaveRequestStatusEnum::Pending->value,
        ]);
        $this->assertDatabaseHas('leave_request_status_histories', ['to_status' => 'Pending']);
        Notification::assertSentTo($hr, LeaveRequestSubmittedNotification::class);
    }

    public function test_store_blocked_on_overlap(): void
    {
        LeaveRequest::factory()->create([
            'staff_id' => $this->staff->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id,
            'start_date' => '2030-06-10', 'end_date' => '2030-06-14',
        ]);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload(['start_date' => '2030-06-12', 'end_date' => '2030-06-16']))
            ->assertSessionHasErrors('start_date');
    }

    public function test_store_blocked_when_exceeding_remaining(): void
    {
        $small = LeaveType::factory()->calendarDays()->create(['is_active' => true]);
        $this->entitle($small, 3);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload(['leave_type_id' => $small->id]))
            ->assertSessionHasErrors('leave_type_id');
    }

    public function test_store_blocked_under_min_notice(): void
    {
        $type = LeaveType::factory()->calendarDays()->create(['is_active' => true, 'min_notice_days' => 30]);
        $this->entitle($type, 20);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload(['leave_type_id' => $type->id]))
            ->assertSessionHasErrors('start_date');
    }

    public function test_store_blocked_over_max_consecutive(): void
    {
        $type = LeaveType::factory()->calendarDays()->create(['is_active' => true, 'max_consecutive_days' => 3]);
        $this->entitle($type, 20);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload(['leave_type_id' => $type->id]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_store_blocked_for_wrong_gender(): void
    {
        $type = LeaveType::factory()->calendarDays()->create(['is_active' => true, 'gender_restriction' => 'M']);
        $this->entitle($type, 20);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload(['leave_type_id' => $type->id]))
            ->assertSessionHasErrors('leave_type_id');
    }

    public function test_store_requires_evidence_when_type_demands_it(): void
    {
        $type = LeaveType::factory()->calendarDays()->create(['is_active' => true, 'requires_evidence' => true]);
        $this->entitle($type, 20);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload(['leave_type_id' => $type->id]))
            ->assertSessionHasErrors('file_name');
    }

    public function test_store_with_evidence_upload(): void
    {
        Storage::fake('leave-documents');
        $type = LeaveType::factory()->calendarDays()->create(['is_active' => true, 'requires_evidence' => true]);
        $this->entitle($type, 20);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload([
                'leave_type_id' => $type->id,
                'file_name' => [UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf')],
            ]))
            ->assertRedirect();

        $this->assertDatabaseCount('leave_documents', 1);
        $document = \App\Models\LeaveDocument::first();
        Storage::disk('leave-documents')->assertExists($document->file_name);
    }

    public function test_document_can_be_downloaded_and_removed(): void
    {
        Storage::fake('leave-documents');
        $leaveRequest = LeaveRequest::factory()->create(['staff_id' => $this->staff->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id]);
        Storage::disk('leave-documents')->put('sample.pdf', 'data');
        $document = $leaveRequest->documents()->create(['title' => 'Doc', 'file_name' => 'sample.pdf', 'file_type' => 'application/pdf']);

        $this->actingAs($this->staffUser)
            ->get(route('leave-request.documents.download', ['leaveRequest' => $leaveRequest->id, 'document' => $document->id]))
            ->assertOk();

        $this->actingAs($this->staffUser)
            ->delete(route('leave-request.documents.destroy', ['leaveRequest' => $leaveRequest->id, 'document' => $document->id]))
            ->assertRedirect();
        $this->assertSoftDeleted('leave_documents', ['id' => $document->id]);
    }

    public function test_cancel_marks_request_cancelled(): void
    {
        $leaveRequest = LeaveRequest::factory()->create(['staff_id' => $this->staff->id, 'leave_type_id' => $this->type->id, 'leave_year_id' => $this->year->id]);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.cancel', $leaveRequest))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => LeaveRequestStatusEnum::Cancelled->value]);
    }

    public function test_converting_a_plan_item_links_both_records(): void
    {
        $plan = LeavePlan::factory()->submitted()->create(['staff_id' => $this->staff->id, 'leave_year_id' => $this->year->id]);
        $item = LeavePlanItem::factory()->create([
            'leave_plan_id' => $plan->id,
            'leave_type_id' => $this->type->id,
            'start_date' => '2030-06-10',
            'end_date' => '2030-06-14',
        ]);

        $this->actingAs($this->staffUser)
            ->post(route('leave-request.store'), $this->payload(['leave_plan_item_id' => $item->id]))
            ->assertRedirect();

        $leaveRequest = LeaveRequest::where('leave_plan_item_id', $item->id)->first();
        $this->assertNotNull($leaveRequest);
        $this->assertSame($leaveRequest->id, $item->fresh()->converted_request_id);
    }
}
