<?php

namespace Tests\Feature\QualificationReports;

use App\DataTransferObjects\QualificationReportFilter;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use App\Services\QualificationReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnitScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
    }

    public function test_view_all_user_keeps_filter_unchanged(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        $filter = new QualificationReportFilter(unitId: 7);
        $scoped = app(QualificationReportService::class)->applyUnitScope($filter, $user->fresh());

        $this->assertSame(7, $scoped->unitId);
    }

    public function test_user_without_any_scope_permission_keeps_filter_unchanged(): void
    {
        $user = User::factory()->create();

        $filter = new QualificationReportFilter(unitId: 3);
        $scoped = app(QualificationReportService::class)->applyUnitScope($filter, $user);

        $this->assertSame(3, $scoped->unitId);
    }

    public function test_own_unit_user_gets_unit_injected_into_filter(): void
    {
        $unit = Unit::factory()->create();
        $person = Person::factory()->create();
        $user = User::factory()->create();
        $user->forceFill(['person_id' => $person->id])->save();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.own_unit']);

        $instPerson = InstitutionPerson::factory()->for($person)->create(['end_date' => null]);

        DB::table('staff_unit')->insert([
            'staff_id' => $instPerson->id,
            'unit_id' => $unit->id,
            'start_date' => now()->subYear(),
            'end_date' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $scoped = app(QualificationReportService::class)->applyUnitScope(
            new QualificationReportFilter,
            $user->fresh(),
        );

        $this->assertSame($unit->id, $scoped->unitId);
    }

    public function test_own_unit_user_with_no_resolvable_unit_keeps_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.own_unit']);

        $scoped = app(QualificationReportService::class)->applyUnitScope(
            new QualificationReportFilter(level: 'masters'),
            $user->fresh(),
        );

        $this->assertNull($scoped->unitId);
        $this->assertSame('masters', $scoped->level);
    }
}
