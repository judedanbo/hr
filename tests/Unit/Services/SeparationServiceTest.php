<?php

namespace Tests\Unit\Services;

use App\Contracts\Services\SeparationServiceInterface;
use App\Enums\EmployeeStatusEnum;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Status;
use App\Models\User;
use App\Services\Staff\SeparationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeparationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SeparationServiceInterface $service;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SeparationService;
        $this->institution = Institution::factory()->create();

        // Create and authenticate a user for activity logging
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_change_status_creates_new_status_record(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'end_date' => null,
        ]);

        // Create initial active status
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => '2020-01-01',
            'institution_id' => $this->institution->id,
        ]);

        $result = $this->service->changeStatus($staff, 'R', [
            'start_date' => '2024-01-15',
            'description' => 'Resigned',
        ]);

        $this->assertInstanceOf(Status::class, $result);
        $this->assertEquals('R', $result->status->value);
        $this->assertEquals('Resigned', $result->description);
    }

    public function test_change_status_to_non_active_sets_staff_end_date(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'end_date' => null,
        ]);

        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => '2020-01-01',
            'institution_id' => $this->institution->id,
        ]);

        $this->service->changeStatus($staff, 'E', [
            'start_date' => '2024-01-15',
            'description' => 'Statutory Retirement',
        ]);

        $staff->refresh();
        $this->assertNotNull($staff->end_date);
        $this->assertEquals('2024-01-15', $staff->end_date->format('Y-m-d'));
    }

    public function test_change_status_to_active_clears_staff_end_date(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'end_date' => '2023-12-31',
        ]);

        $staff->statuses()->create([
            'status' => 'R',
            'start_date' => '2023-12-31',
            'institution_id' => $this->institution->id,
        ]);

        $this->service->changeStatus($staff, 'A', [
            'start_date' => '2024-01-15',
            'description' => 'Reinstated',
        ]);

        $staff->refresh();
        $this->assertNull($staff->end_date);
    }

    public function test_change_status_closes_previous_open_status(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $oldStatus = $staff->statuses()->create([
            'status' => EmployeeStatusEnum::Active->value,
            'start_date' => '2020-01-01',
            'end_date' => null,
            'institution_id' => $this->institution->id,
        ]);

        $this->service->changeStatus($staff, EmployeeStatusEnum::Suspended->value, [
            'start_date' => '2024-01-15',
            'description' => 'Suspended',
        ]);

        $oldStatus->refresh();
        $this->assertNotNull($oldStatus->end_date);
    }

    public function test_update_status_modifies_existing_status(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $status = $staff->statuses()->create([
            'status' => 'L',
            'start_date' => '2024-01-01',
            'description' => 'Leave',
            'institution_id' => $this->institution->id,
        ]);

        $result = $this->service->updateStatus($status, [
            'description' => 'Updated leave description',
        ]);

        $this->assertEquals('Updated leave description', $result->description);
    }

    public function test_delete_status_removes_status_record(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $status = $staff->statuses()->create([
            'status' => 'P',
            'start_date' => '2024-01-01',
            'institution_id' => $this->institution->id,
        ]);

        $this->service->deleteStatus($status);

        $this->assertSoftDeleted('status', [
            'id' => $status->id,
        ]);
    }

    public function test_get_separated_staff_returns_non_active_staff(): void
    {
        // Active staff
        $activeStaff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $activeStaff->statuses()->create([
            'status' => 'A',
            'start_date' => '2020-01-01',
            'end_date' => null,
            'institution_id' => $this->institution->id,
        ]);

        // Separated staff
        $separatedStaff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $separatedStaff->statuses()->create([
            'status' => 'E',
            'start_date' => '2024-01-01',
            'end_date' => null,
            'institution_id' => $this->institution->id,
        ]);

        $result = $this->service->getSeparatedStaff();

        $staffIds = $result->pluck('id')->toArray();
        $this->assertContains($separatedStaff->id, $staffIds);
        $this->assertNotContains($activeStaff->id, $staffIds);
    }

    public function test_get_status_history_returns_all_statuses(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => '2020-01-01',
            'end_date' => '2022-12-31',
            'institution_id' => $this->institution->id,
        ]);

        $staff->statuses()->create([
            'status' => 'P',
            'start_date' => '2023-01-01',
            'end_date' => '2023-03-31',
            'description' => 'Leave with pay',
            'institution_id' => $this->institution->id,
        ]);

        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => '2023-04-01',
            'end_date' => null,
            'institution_id' => $this->institution->id,
        ]);

        $history = $this->service->getStatusHistory($staff);

        $this->assertCount(3, $history);
    }

    public function test_get_status_history_indicates_current_status(): void
    {
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => '2020-01-01',
            'end_date' => '2023-12-31',
            'institution_id' => $this->institution->id,
        ]);

        $staff->statuses()->create([
            'status' => 'E',
            'start_date' => '2024-01-01',
            'end_date' => null,
            'institution_id' => $this->institution->id,
        ]);

        $history = $this->service->getStatusHistory($staff);

        $currentStatus = $history->firstWhere('is_current', true);
        $this->assertEquals('E', $currentStatus['status']->value);
    }
}
