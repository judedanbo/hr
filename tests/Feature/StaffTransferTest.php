<?php

namespace Tests\Feature;

use App\Enums\TransferStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institution $institution;

    protected InstitutionPerson $staff;

    protected Unit $currentUnit;

    protected Unit $newUnit;

    protected function setUp(): void
    {
        parent::setUp();

        // Create institution
        $this->institution = Institution::factory()->create();

        // Create units
        $this->currentUnit = Unit::factory()->create([
            'name' => 'Finance Department',
            'institution_id' => $this->institution->id,
        ]);
        $this->newUnit = Unit::factory()->create([
            'name' => 'Human Resources',
            'institution_id' => $this->institution->id,
        ]);

        // Create person with staff record
        $person = Person::factory()->create();
        $person->institution()->attach($this->institution->id, [
            'staff_number' => 'STF001',
            'hire_date' => now()->subYears(3),
        ]);
        $this->staff = InstitutionPerson::where('person_id', $person->id)->first();

        // Attach current unit
        $this->staff->units()->attach($this->currentUnit->id, [
            'start_date' => now()->subYears(2),
            'status' => TransferStatusEnum::Approved,
        ]);

        // Create authorized user
        $adminPerson = Person::factory()->create();
        $adminPerson->institution()->attach($this->institution->id, [
            'staff_number' => 'ADMIN001',
            'hire_date' => now()->subYears(5),
        ]);

        $this->user = User::factory()->create([
            'person_id' => $adminPerson->id,
            'password_change_at' => now(),
        ]);
        $this->user->givePermissionTo('create staff transfers');
        $this->user->givePermissionTo('update staff transfers');
        $this->user->givePermissionTo('delete staff transfers');
        $this->user->givePermissionTo('view all staff');
    }

    // ===================
    // AUTHORIZATION TESTS
    // ===================

    public function test_transfer_requires_authentication(): void
    {
        $response = $this->post(route('staff.transfer.store', $this->staff), [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'start_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect('/login');
    }

    public function test_transfer_requires_permission(): void
    {
        $userWithoutPermission = User::factory()->create([
            'password_change_at' => now(),
        ]);

        $response = $this->actingAs($userWithoutPermission)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'unit_id' => $this->newUnit->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertForbidden();
    }

    // ===================
    // TRANSFER STORE TESTS
    // ===================

    public function test_can_transfer_staff_with_valid_data(): void
    {
        $transferData = [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'start_date' => now()->format('Y-m-d'),
            'remarks' => 'Transfer due to departmental needs',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), $transferData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the new unit is attached
        $this->assertDatabaseHas('staff_unit', [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
        ]);
    }

    public function test_transfer_with_start_date_sets_pending_status(): void
    {
        $transferData = [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), $transferData);

        $response->assertRedirect();

        // Verify the status is Approved
        $transfer = $this->staff->units()->where('staff_unit.unit_id', $this->newUnit->id)->first();
        $this->assertEquals(TransferStatusEnum::Pending, $transfer->pivot->status);
    }

    public function test_transfer_closes_previous_unit_assignment(): void
    {
        $startDate = now()->format('Y-m-d');

        $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'unit_id' => $this->newUnit->id,
                'start_date' => $startDate,
            ])->assertRedirect();

        // Approval is what closes previous assignments under the pending-first workflow.
        $this->actingAs($this->user)
            ->patch(route('staff.transfer.approve', [$this->staff, $this->newUnit]), [
                'staff_id' => $this->staff->id,
                'unit_id' => $this->newUnit->id,
                'start_date' => $startDate,
            ])->assertRedirect();

        $previousUnit = $this->staff->units()
            ->where('staff_unit.unit_id', $this->currentUnit->id)
            ->first();

        $this->assertNotNull($previousUnit->pivot->end_date);
    }

    public function test_transfer_without_start_date_creates_pending_record(): void
    {
        $transferData = [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'remarks' => 'Pending approval',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), $transferData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // ===================
    // VALIDATION TESTS
    // ===================

    public function test_transfer_requires_staff_id(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'unit_id' => $this->newUnit->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('staff_id');
    }

    public function test_transfer_requires_unit_id(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('unit_id');
    }

    public function test_transfer_requires_valid_staff_id(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => 99999,
                'unit_id' => $this->newUnit->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('staff_id');
    }

    public function test_transfer_requires_valid_unit_id(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'unit_id' => 99999,
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('unit_id');
    }

    public function test_transfer_end_date_must_be_after_start_date(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'unit_id' => $this->newUnit->id,
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->subMonth()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    // ===================
    // TRANSFER UPDATE TESTS
    // ===================

    public function test_can_update_transfer_record(): void
    {
        // First create a transfer
        $this->staff->units()->attach($this->newUnit->id, [
            'start_date' => now()->subMonth(),
        ]);

        $updateData = [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'start_date' => now()->format('Y-m-d'),
            'remarks' => 'Updated transfer record',
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('staff.transfer.update', [$this->staff, $this->newUnit]), $updateData);

        $response->assertRedirect();
    }

    public function test_update_fails_if_staff_id_mismatch(): void
    {
        $otherPerson = Person::factory()->create();
        $otherPerson->institution()->attach($this->institution->id, [
            'staff_number' => 'STF002',
            'hire_date' => now()->subYear(),
        ]);
        $otherStaff = InstitutionPerson::where('person_id', $otherPerson->id)->first();

        $this->staff->units()->attach($this->newUnit->id, [
            'start_date' => now()->subMonth(),
        ]);

        $updateData = [
            'staff_id' => $otherStaff->id, // Different staff ID
            'unit_id' => $this->newUnit->id,
            'start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('staff.transfer.update', [$this->staff, $this->newUnit]), $updateData);

        $response->assertSessionHas('error');
    }

    // ===================
    // TRANSFER APPROVE TESTS
    // ===================

    public function test_can_approve_pending_transfer(): void
    {
        // Create a pending transfer
        $this->staff->units()->attach($this->newUnit->id, [
            'status' => TransferStatusEnum::Pending,
        ]);

        $approvalData = [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'start_date' => now()->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('staff.transfer.approve', [$this->staff, $this->newUnit]), $approvalData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the status is now Approved
        $transfer = $this->staff->units()->where('staff_unit.unit_id', $this->newUnit->id)->first();
        $this->assertEquals(TransferStatusEnum::Approved, $transfer->pivot->status);
    }

    // ===================
    // TRANSFER DELETE TESTS
    // ===================

    public function test_can_delete_transfer_record(): void
    {
        // First create a transfer to delete
        $this->staff->units()->attach($this->newUnit->id, [
            'start_date' => now()->subMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('staff.transfer.delete', [$this->staff, $this->newUnit]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the unit is detached
        $this->assertDatabaseMissing('staff_unit', [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
        ]);
    }

    // ===================
    // EDGE CASE TESTS
    // ===================

    public function test_staff_can_have_multiple_unit_history(): void
    {
        // Create third unit
        $thirdUnit = Unit::factory()->create([
            'name' => 'IT Department',
            'institution_id' => $this->institution->id,
        ]);

        // First transfer
        $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'unit_id' => $this->newUnit->id,
                'start_date' => now()->subMonth()->format('Y-m-d'),
            ]);

        // Second transfer
        $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'unit_id' => $thirdUnit->id,
                'start_date' => now()->format('Y-m-d'),
            ]);

        // Staff should have 3 units in history (original + 2 transfers)
        $this->assertEquals(3, $this->staff->units()->count());
    }

    public function test_transfer_with_remarks_stores_correctly(): void
    {
        $remarks = 'Strategic reallocation of resources';

        $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), [
                'staff_id' => $this->staff->id,
                'unit_id' => $this->newUnit->id,
                'start_date' => now()->format('Y-m-d'),
                'remarks' => $remarks,
            ]);

        $this->assertDatabaseHas('staff_unit', [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'remarks' => $remarks,
        ]);
    }

    public function test_transfer_with_end_date_is_valid(): void
    {
        $transferData = [
            'staff_id' => $this->staff->id,
            'unit_id' => $this->newUnit->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addYear()->format('Y-m-d'),
            'remarks' => 'Temporary assignment',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('staff.transfer.store', $this->staff), $transferData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
