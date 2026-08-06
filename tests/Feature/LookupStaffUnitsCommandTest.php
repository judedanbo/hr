<?php

namespace Tests\Feature;

use App\Enums\ContactTypeEnum;
use App\Enums\UnitType;
use App\Models\Contact;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Job;
use App\Models\Person;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LookupStaffUnitsCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $workingDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingDirectory = storage_path('framework/testing/staff-unit-lookup');
        File::ensureDirectoryExists($this->workingDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->workingDirectory);

        parent::tearDown();
    }

    public function test_it_reports_the_department_and_unit_of_a_staff_matched_by_email(): void
    {
        $institution = Institution::factory()->create();
        $department = $this->unit($institution, 'Audit Service Head Office', UnitType::DEPARTMENT);
        $unit = $this->unit($institution, 'IT Unit', UnitType::UNIT, $department);

        $person = Person::factory()->create([
            'title' => 'Mr',
            'first_name' => 'Benjamin',
            'other_names' => 'Tietaa',
            'surname' => 'Sumabe',
        ]);
        Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'Benjamin.Sumabe@audit.gov.gh',
        ]);

        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $institution->id,
            'person_id' => $person->id,
            'staff_number' => 'STAFF-001',
            'file_number' => 'FILE-001',
        ]);
        $this->assign($staff, $unit, '2023-01-01');
        $this->rank($staff, $institution, 'Auditor', '2022-01-01');

        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['benjamin.sumabe@audit.gov.gh', 'Benjamin Sumabe'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('Email', $result['Match']);
        $this->assertSame('STAFF-001', $result['Staff Number']);
        $this->assertSame('FILE-001', $result['File Number']);
        $this->assertSame('Audit Service Head Office', $result['Department']);
        $this->assertSame('IT Unit', $result['Unit']);
        $this->assertSame('Management unit', $result['Unit Type']);
        $this->assertSame('Audit Service Head Office > IT Unit', $result['Unit Hierarchy']);
        $this->assertSame('Auditor', $result['Current Rank']);
        $this->assertSame('Current', $result['Assignment Status']);
    }

    public function test_it_matches_on_the_user_login_email_and_ignores_word_order(): void
    {
        $institution = Institution::factory()->create();
        $department = $this->unit($institution, 'Finance Department', UnitType::DEPARTMENT);
        $division = $this->unit($institution, 'Treasury Division', UnitType::DIVISION, $department);
        $unit = $this->unit($institution, 'Payments Unit', UnitType::UNIT, $division);

        $person = Person::factory()->create([
            'first_name' => 'Isabella',
            'other_names' => null,
            'surname' => 'Adamkwor-Mensah',
        ]);
        User::factory()->create([
            'person_id' => $person->id,
            'email' => 'isabella.adamkwor-mensah@audit.gov.gh',
        ]);

        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $institution->id,
            'person_id' => $person->id,
        ]);
        $this->assign($staff, $unit, '2024-05-01');

        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['Isabella.Adamkwor-Mensah@audit.gov.gh', 'Adamkwor Mensah Isabella'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('Email', $result['Match']);
        $this->assertSame('Finance Department', $result['Department']);
        $this->assertSame('Payments Unit', $result['Unit']);
        $this->assertSame('Finance Department > Treasury Division > Payments Unit', $result['Unit Hierarchy']);
    }

    public function test_it_falls_back_to_a_name_match_when_the_email_is_unknown(): void
    {
        $institution = Institution::factory()->create();
        $department = $this->unit($institution, 'Legal Department', UnitType::DEPARTMENT);

        $person = Person::factory()->create([
            'first_name' => 'Kwesi',
            'other_names' => 'Adu',
            'surname' => 'Mensah',
        ]);
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $institution->id,
            'person_id' => $person->id,
        ]);
        $this->assign($staff, $department, '2020-03-01');

        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['unknown.address@audit.gov.gh', 'Mr Kwesi Adu Mensah'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('Name', $result['Match']);
        $this->assertSame('Legal Department', $result['Department']);
        $this->assertSame('Legal Department', $result['Unit']);
    }

    public function test_it_flags_names_that_match_more_than_one_person(): void
    {
        $institution = Institution::factory()->create();
        $department = $this->unit($institution, 'Operations Department', UnitType::DEPARTMENT);

        foreach (range(1, 2) as $ignored) {
            $person = Person::factory()->create([
                'first_name' => 'Kofi',
                'other_names' => null,
                'surname' => 'Owusu',
            ]);
            $staff = InstitutionPerson::factory()->create([
                'institution_id' => $institution->id,
                'person_id' => $person->id,
            ]);
            $this->assign($staff, $department, '2021-01-01');
        }

        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['kofi.owusu@audit.gov.gh', 'Kofi Owusu'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('Ambiguous name', $result['Match']);
        $this->assertNull($result['Department']);
    }

    public function test_it_reports_people_it_cannot_find(): void
    {
        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['nobody@audit.gov.gh', 'Nobody At All'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('Not found', $result['Match']);
        $this->assertSame('nobody@audit.gov.gh', $result['Email (from file)']);
        $this->assertNull($result['Unit']);
    }

    public function test_it_uses_the_current_assignment_when_a_staff_has_transferred(): void
    {
        $institution = Institution::factory()->create();
        $oldDepartment = $this->unit($institution, 'Old Department', UnitType::DEPARTMENT);
        $newDepartment = $this->unit($institution, 'New Department', UnitType::DEPARTMENT);
        $newUnit = $this->unit($institution, 'New Unit', UnitType::UNIT, $newDepartment);

        $person = Person::factory()->create(['first_name' => 'Ama', 'other_names' => null, 'surname' => 'Boateng']);
        Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'ama.boateng@audit.gov.gh',
        ]);
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $institution->id,
            'person_id' => $person->id,
        ]);
        $this->assign($staff, $oldDepartment, '2018-01-01', '2023-12-31');
        $this->assign($staff, $newUnit, '2024-01-01');

        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['ama.boateng@audit.gov.gh', 'Ama Boateng'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('New Department', $result['Department']);
        $this->assertSame('New Unit', $result['Unit']);
        $this->assertSame('Current', $result['Assignment Status']);
    }

    public function test_it_reports_a_staff_member_without_any_unit_assignment(): void
    {
        $institution = Institution::factory()->create();

        $person = Person::factory()->create(['first_name' => 'Yaw', 'other_names' => null, 'surname' => 'Antwi']);
        Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'yaw.antwi@audit.gov.gh',
        ]);
        InstitutionPerson::factory()->create([
            'institution_id' => $institution->id,
            'person_id' => $person->id,
        ]);

        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['yaw.antwi@audit.gov.gh', 'Yaw Antwi'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('Email', $result['Match']);
        $this->assertSame('No unit assignment on record', $result['Assignment Status']);
        $this->assertNull($result['Unit']);
    }

    public function test_it_tolerates_survey_export_headings_and_non_breaking_spaces(): void
    {
        $institution = Institution::factory()->create();
        $department = $this->unit($institution, 'Volta Regional Office', UnitType::REGION);
        $district = $this->unit($institution, "Ho Dist. 'B'", UnitType::DISTRICT, $department);

        $person = Person::factory()->create(['first_name' => 'Selase', 'other_names' => null, 'surname' => 'Tordzeagbo']);
        Contact::factory()->create([
            'person_id' => $person->id,
            'contact_type' => ContactTypeEnum::EMAIL->value,
            'contact' => 'selase.tordzeagbo@audit.gov.gh',
        ]);
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $institution->id,
            'person_id' => $person->id,
        ]);
        $this->assign($staff, $district, '2022-09-01');

        $source = $this->sourceFile([
            ["Email\u{00A0}", ' Name '],
            ["\u{00A0}selase.tordzeagbo@audit.gov.gh ", 'Selase Tordzeagbo'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(0);

        $result = $this->firstResultRow($source);

        $this->assertSame('Email', $result['Match']);
        $this->assertSame('Volta Regional Office', $result['Department']);
        $this->assertSame("Ho Dist. 'B'", $result['Unit']);
        $this->assertSame('District Office', $result['Unit Type']);
    }

    public function test_it_writes_to_the_requested_output_path(): void
    {
        $source = $this->sourceFile([
            ['Email', 'Name'],
            ['nobody@audit.gov.gh', 'Nobody At All'],
        ]);
        $output = $this->workingDirectory . '/custom-name.xlsx';

        $this->artisan('app:lookup-staff-units', ['file' => $source, '--output' => $output])->assertExitCode(0);

        $this->assertTrue(File::isFile($output));
    }

    public function test_it_fails_when_the_file_does_not_exist(): void
    {
        $this->artisan('app:lookup-staff-units', ['file' => $this->workingDirectory . '/missing.xlsx'])
            ->assertExitCode(1);
    }

    public function test_it_fails_when_neither_column_is_present(): void
    {
        $source = $this->sourceFile([
            ['Reference', 'Rank'],
            ['ABC', 'Auditor'],
        ]);

        $this->artisan('app:lookup-staff-units', ['file' => $source])->assertExitCode(1);
    }

    private function unit(Institution $institution, string $name, UnitType $type, ?Unit $parent = null): Unit
    {
        return Unit::factory()->create([
            'institution_id' => $institution->id,
            'name' => $name,
            'type' => $type,
            'unit_id' => $parent?->id,
            'end_date' => null,
        ]);
    }

    private function assign(InstitutionPerson $staff, Unit $unit, string $startDate, ?string $endDate = null): void
    {
        DB::table('staff_unit')->insert([
            'staff_id' => $staff->id,
            'unit_id' => $unit->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function rank(InstitutionPerson $staff, Institution $institution, string $jobName, string $startDate): void
    {
        $job = Job::factory()->create(['name' => $jobName, 'institution_id' => $institution->id]);

        DB::table('job_staff')->insert([
            'staff_id' => $staff->id,
            'job_id' => $job->id,
            'start_date' => $startDate,
            'end_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, array<int, string|null>>  $rows
     */
    private function sourceFile(array $rows): string
    {
        $path = $this->workingDirectory . '/source-' . uniqid() . '.csv';

        $handle = fopen($path, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }

    /**
     * @return array<string, string|null>
     */
    private function firstResultRow(string $sourcePath): array
    {
        $resultPath = File::dirname($sourcePath) . '/' . File::name($sourcePath) . '-units.xlsx';

        $this->assertTrue(File::isFile($resultPath), "Expected a result file at {$resultPath}.");

        $rows = Excel::toArray(new \App\Imports\SpreadsheetRowsImport, $resultPath)[0];

        return array_combine($rows[0], $rows[1]);
    }
}
