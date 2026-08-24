<?php

namespace Tests\Feature\Unit;

use App\Exports\StaffDetailsExport;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class StaffDetailsExportTest extends TestCase
{
    use RefreshDatabase;

    private function makeActiveStaff(Unit $unit, ?Job $rank = null, ?Carbon $unitStart = null, ?Carbon $rankStart = null): InstitutionPerson
    {
        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $unit->institution_id,
            'person_id' => $person->id,
        ]);
        $staff->statuses()->create([
            'status' => 'A',
            'start_date' => now()->subYears(10),
            'institution_id' => $unit->institution_id,
        ]);
        $staff->units()->attach($unit->id, ['start_date' => $unitStart ?? now()->subYear()]);
        if ($rank) {
            $staff->ranks()->attach($rank->id, ['start_date' => $rankStart ?? now()->subYear()]);
        }

        return $staff;
    }

    private function makeRank(string $name, ?JobCategory $category): Job
    {
        return Job::factory()->create([
            'name' => $name,
            'job_category_id' => $category?->id,
        ]);
    }

    /**
     * Run the export's own query so the currentRank/currentUnit scopes apply, then map the row.
     *
     * @return array<int, mixed>
     */
    private function mapFirstRow(): array
    {
        $export = new StaffDetailsExport;

        return $export->map($export->query()->firstOrFail());
    }

    public function test_headings_group_start_date_and_duration_with_their_rank_and_unit(): void
    {
        $this->assertSame(
            [
                'File Number',
                'Staff Number',
                'Full Name',
                'Date of Birth',
                'Age',
                'Ghana Card Number',
                'Contact',
                'Appointment Date',
                'Years served',
                'Current Rank',
                'Current Rank Start Date',
                'Duration at Current Rank',
                'Current Unit',
                'Current Unit Start Date',
                'Duration at Current Unit',
                'Retirement Date',
                'Rank Level',
            ],
            (new StaffDetailsExport)->headings()
        );
    }

    public function test_row_reports_start_date_and_duration_for_current_rank_and_unit(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15'));

        $unit = Unit::factory()->create(['unit_id' => null, 'name' => 'Audit Department']);
        $rank = $this->makeRank('Director', JobCategory::factory()->create(['name' => 'Managerial', 'level' => 4]));

        $this->makeActiveStaff(
            $unit,
            $rank,
            unitStart: Carbon::parse('2024-12-15'),
            rankStart: Carbon::parse('2021-01-15'),
        );

        $row = $this->mapFirstRow();

        $this->assertSame('Director', $row[9]);
        $this->assertSame('15 January, 2021', $row[10]);
        $this->assertSame('5 years 5 months', $row[11]);
        $this->assertSame('Audit Department', $row[12]);
        $this->assertSame('15 December, 2024', $row[13]);
        $this->assertSame('1 year 6 months', $row[14]);
    }

    public function test_rank_level_column_reports_the_category_name_not_its_level_number(): void
    {
        $unit = Unit::factory()->create(['unit_id' => null]);
        $rank = $this->makeRank('Director', JobCategory::factory()->create(['name' => 'Managerial', 'level' => 4]));

        $this->makeActiveStaff($unit, $rank);

        $this->assertSame('Managerial', $this->mapFirstRow()[16]);
    }

    public function test_row_is_blank_rather_than_failing_when_rank_unit_or_category_are_missing(): void
    {
        $unit = Unit::factory()->create(['unit_id' => null]);
        $staff = $this->makeActiveStaff($unit, $this->makeRank('Unclassified', null));
        $staff->units()->detach();

        $row = $this->mapFirstRow();

        $this->assertSame('Unclassified', $row[9]);
        $this->assertNull($row[12]);
        $this->assertNull($row[13]);
        $this->assertNull($row[14]);
        $this->assertNull($row[16]);
    }

    public function test_route_downloads_the_export(): void
    {
        Excel::fake();

        $this->actingAs(User::factory()->create())
            ->get(route('report.staff-details'))
            ->assertOk();

        Excel::assertDownloaded('staff-details.xlsx');
    }
}
