<?php

namespace Tests\Unit\Services;

use App\Contracts\Services\TransferServiceInterface;
use App\Enums\TransferStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\StaffUnit;
use App\Models\Unit;
use App\Services\Staff\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransferServiceInterface $service;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransferService;
        $this->institution = Institution::factory()->create();
    }

    public function test_transfer_creates_new_unit_assignment_with_pending_status(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        $result = $this->service->transfer($staff, $unit->id, [
            'start_date' => '2024-01-01',
            'remarks' => 'Transfer to new unit',
        ]);

        $this->assertInstanceOf(StaffUnit::class, $result);
        $this->assertEquals($unit->id, $result->unit_id);
        $this->assertEquals($staff->id, $result->staff_id);
        $this->assertEquals(TransferStatusEnum::Pending, $result->status);
    }

    public function test_transfer_without_start_date_creates_pending_transfer(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        $result = $this->service->transfer($staff, $unit->id, [
            'start_date' => null,
            'remarks' => 'Pending transfer',
        ]);

        $this->assertEquals(TransferStatusEnum::Pending, $result->status);
        $this->assertNull($result->start_date);
    }

    public function test_transfer_does_not_close_previous_open_unit_assignments(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $oldUnit = Unit::factory()->create(['institution_id' => $this->institution->id]);
        $newUnit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        // Assign initial unit
        $staff->units()->attach($oldUnit->id, [
            'start_date' => '2020-01-01',
            'end_date' => null,
            'status' => TransferStatusEnum::Approved,
        ]);

        // Creating a new transfer should NOT close previous assignments
        // (that only happens when the transfer is approved)
        $this->service->transfer($staff, $newUnit->id, [
            'start_date' => '2024-01-01',
        ]);

        $staff->refresh();

        $oldUnitPivot = $staff->units()->where('staff_unit.unit_id', $oldUnit->id)->first();
        $newUnitPivot = $staff->units()->where('staff_unit.unit_id', $newUnit->id)->first();

        // Previous assignment should remain open until transfer is approved
        $this->assertNull($oldUnitPivot->pivot->end_date);
        $this->assertNull($newUnitPivot->pivot->end_date);
        $this->assertEquals(TransferStatusEnum::Pending, $newUnitPivot->pivot->status);
    }

    public function test_update_transfer_modifies_existing_transfer(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        $staff->units()->attach($unit->id, [
            'start_date' => '2023-01-01',
            'remarks' => 'Original',
            'status' => TransferStatusEnum::Pending,
        ]);

        $result = $this->service->updateTransfer($staff, $unit->id, [
            'unit_id' => $unit->id,
            'start_date' => '2023-06-01',
            'remarks' => 'Updated',
        ]);

        $this->assertEquals('Updated', $result->remarks);
        $this->assertEquals('2023-06-01', $result->start_date->format('Y-m-d'));
    }

    public function test_delete_transfer_removes_unit_assignment(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        $staff->units()->attach($unit->id, [
            'start_date' => '2023-01-01',
        ]);

        $this->service->deleteTransfer($staff, $unit->id);

        $staff->refresh();
        $this->assertFalse($staff->units->contains('id', $unit->id));
    }

    public function test_approve_transfer_changes_status_to_approved(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $unit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        $staff->units()->attach($unit->id, [
            'start_date' => null,
            'status' => TransferStatusEnum::Pending,
        ]);

        $result = $this->service->approveTransfer($staff, $unit->id, [
            'start_date' => '2024-01-15',
        ]);

        $this->assertEquals(TransferStatusEnum::Approved, $result->status);
        $this->assertEquals('2024-01-15', $result->start_date->format('Y-m-d'));
    }

    public function test_approve_transfer_closes_other_open_assignments(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $currentUnit = Unit::factory()->create(['institution_id' => $this->institution->id]);
        $newUnit = Unit::factory()->create(['institution_id' => $this->institution->id]);

        // Current active unit
        $staff->units()->attach($currentUnit->id, [
            'start_date' => '2020-01-01',
            'end_date' => null,
            'status' => TransferStatusEnum::Approved,
        ]);

        // Pending transfer to new unit
        $staff->units()->attach($newUnit->id, [
            'start_date' => null,
            'end_date' => null,
            'status' => TransferStatusEnum::Pending,
        ]);

        $this->service->approveTransfer($staff, $newUnit->id, [
            'start_date' => '2024-01-15',
        ]);

        $staff->refresh();

        $currentUnitPivot = $staff->units()->where('staff_unit.unit_id', $currentUnit->id)->first();
        $this->assertNotNull($currentUnitPivot->pivot->end_date);
    }

    public function test_get_transfer_history_returns_all_unit_assignments(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $unit1 = Unit::factory()->create(['institution_id' => $this->institution->id, 'name' => 'Unit A']);
        $unit2 = Unit::factory()->create(['institution_id' => $this->institution->id, 'name' => 'Unit B']);

        $staff->units()->attach($unit1->id, [
            'start_date' => '2020-01-01',
            'end_date' => '2022-12-31',
            'status' => TransferStatusEnum::Approved,
        ]);
        $staff->units()->attach($unit2->id, [
            'start_date' => '2023-01-01',
            'end_date' => null,
            'status' => TransferStatusEnum::Approved,
        ]);

        $history = $this->service->getTransferHistory($staff);

        $this->assertCount(2, $history);
        $this->assertTrue($history->pluck('unit_name')->contains('Unit A'));
        $this->assertTrue($history->pluck('unit_name')->contains('Unit B'));
    }
}
