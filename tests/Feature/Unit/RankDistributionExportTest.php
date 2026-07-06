<?php

namespace Tests\Feature\Unit;

use App\Exports\RankDistribution\DepartmentsRankDistributionExport;
use App\Exports\RankDistribution\ServiceRankDistributionExport;
use App\Exports\RankDistribution\UnitRankDistributionExport;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class RankDistributionExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('download unit staff');
    }

    private function makeActiveStaff(Unit $unit, ?Job $rank = null): InstitutionPerson
    {
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $unit->institution_id,
            'person_id' => $person->id,
        ]);
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYear(),
            'institution_id' => $unit->institution_id,
        ]);
        $staff->units()->attach($unit->id, ['start_date' => now()->subYear()]);
        if ($rank) {
            $staff->ranks()->attach($rank->id, ['start_date' => now()->subYear()]);
        }

        return $staff;
    }

    private function makeRank(string $name, int $level): Job
    {
        return Job::factory()->create([
            'name' => $name,
            'job_category_id' => JobCategory::factory()->create(['level' => $level])->id,
        ]);
    }

    public function test_unit_export_route_downloads_file(): void
    {
        Excel::fake();

        $root = Unit::factory()->create(['unit_id' => null, 'name' => 'Finance']);

        $this->actingAs($this->user)
            ->get(route('export.unit.rank-distribution', ['unit' => $root->id]))
            ->assertOk();

        Excel::assertDownloaded('Finance rank distribution.xlsx');
    }

    public function test_unit_matrix_headings_list_units_depth_first_with_zero_staff_columns(): void
    {
        $root = Unit::factory()->create(['unit_id' => null, 'name' => 'Audit Department']);
        $childB = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id, 'name' => 'Beta Division']);
        Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id, 'name' => 'Alpha Division']);
        Unit::factory()->create(['unit_id' => $childB->id, 'institution_id' => $root->institution_id, 'name' => 'Beta Unit One']);

        $export = new UnitRankDistributionExport($root);

        $this->assertSame(
            [
                'Rank',
                'Audit Department',
                '  Alpha Division',
                '  Beta Division',
                '    Beta Unit One',
                'Total',
            ],
            $export->headings()
        );
    }

    public function test_unit_matrix_counts_and_totals(): void
    {
        $root = Unit::factory()->create(['unit_id' => null, 'name' => 'Audit Department']);
        $child = Unit::factory()->create(['unit_id' => $root->id, 'institution_id' => $root->institution_id, 'name' => 'Alpha Division']);
        $grand = Unit::factory()->create(['unit_id' => $child->id, 'institution_id' => $root->institution_id, 'name' => 'Alpha Unit']);

        $director = $this->makeRank('Director', 1);
        $officer = $this->makeRank('Officer', 2);

        $this->makeActiveStaff($root, $director);
        $this->makeActiveStaff($child, $officer);
        $this->makeActiveStaff($grand, $officer);
        $this->makeActiveStaff($grand, $officer);

        $export = new UnitRankDistributionExport($root);

        $this->assertSame(
            [
                ['Director', 1, 0, 0, 1],
                ['Officer', 0, 1, 2, 3],
                ['Total', 1, 1, 2, 4],
            ],
            $export->array()
        );
    }

    public function test_departments_export_aggregates_each_department_subtree(): void
    {
        $alpha = Unit::factory()->department()->create(['name' => 'Alpha Department']);
        $beta = Unit::factory()->department()->create(['name' => 'Beta Department']);
        $alphaSub = Unit::factory()->create(['unit_id' => $alpha->id, 'institution_id' => $alpha->institution_id, 'name' => 'Alpha Division']);

        $officer = $this->makeRank('Officer', 1);

        $this->makeActiveStaff($alphaSub, $officer);
        $this->makeActiveStaff($alphaSub, $officer);
        $this->makeActiveStaff($beta, $officer);

        $export = new DepartmentsRankDistributionExport;

        $this->assertSame(['Rank', 'Alpha Department', 'Beta Department', 'Total'], $export->headings());
        $this->assertSame(
            [
                ['Officer', 2, 1, 3],
                ['Total', 2, 1, 3],
            ],
            $export->array()
        );
    }

    public function test_service_export_lists_ranks_ordered_by_level_with_total(): void
    {
        $unitA = Unit::factory()->create(['unit_id' => null]);
        $unitB = Unit::factory()->create(['unit_id' => null]);

        $director = $this->makeRank('Director', 1);
        $officer = $this->makeRank('Officer', 2);

        $this->makeActiveStaff($unitA, $officer);
        $this->makeActiveStaff($unitB, $officer);
        $this->makeActiveStaff($unitB, $director);

        $export = new ServiceRankDistributionExport;

        $this->assertSame(['Rank', 'Staff Count'], $export->headings());
        $this->assertSame(
            [
                ['Director', 1],
                ['Officer', 2],
                ['Total', 3],
            ],
            $export->array()
        );
    }

    public function test_export_routes_require_download_unit_staff_permission(): void
    {
        $unit = Unit::factory()->create(['unit_id' => null]);
        $userWithoutPermission = User::factory()->create();

        $this->actingAs($userWithoutPermission)
            ->get(route('export.unit.rank-distribution', ['unit' => $unit->id]))
            ->assertForbidden();

        $this->actingAs($userWithoutPermission)
            ->get(route('export.units.rank-distribution.departments'))
            ->assertForbidden();

        $this->actingAs($userWithoutPermission)
            ->get(route('export.units.rank-distribution.service'))
            ->assertForbidden();
    }

    public function test_index_export_routes_download_files(): void
    {
        Excel::fake();

        $this->actingAs($this->user)
            ->get(route('export.units.rank-distribution.departments'))
            ->assertOk();
        $this->actingAs($this->user)
            ->get(route('export.units.rank-distribution.service'))
            ->assertOk();

        Excel::assertDownloaded('departments-rank-distribution.xlsx');
        Excel::assertDownloaded('audit-service-rank-distribution.xlsx');
    }
}
