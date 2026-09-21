<?php

namespace Tests\Feature;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MatchStaffListCommandTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;

    private string $listPath;

    private string $reportPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        $this->listPath = tempnam(sys_get_temp_dir(), 'list') . '.xlsx';
        $this->reportPath = tempnam(sys_get_temp_dir(), 'report') . '.csv';
    }

    protected function tearDown(): void
    {
        foreach ([$this->listPath, $this->reportPath] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    /**
     * Create a staff record, optionally with an organisational email address.
     */
    private function staff(string $surname, string $firstName, string $otherNames, string $staffNumber, ?string $email = null, ?string $maidenName = null): InstitutionPerson
    {
        $person = Person::factory()->create([
            'surname' => $surname,
            'first_name' => $firstName,
            'other_names' => $otherNames,
            'maiden_name' => $maidenName,
        ]);

        if ($email !== null) {
            Contact::factory()->create([
                'person_id' => $person->id,
                'contact_type' => ContactTypeEnum::EMAIL,
                'contact' => $email,
            ]);
        }

        return InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'person_id' => $person->id,
            'staff_number' => $staffNumber,
        ]);
    }

    /**
     * Write a promotion-list style sheet: a rank heading, a header row, then rows.
     *
     * @param  list<array{0: string, 1: string, 2: string}>  $rows  name, unit, email
     */
    private function writeList(string $rankGroup, array $rows): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('C2', $rankGroup);
        $sheet->fromArray(['No', 'Full Name', 'Current Unit', 'Retirement Date', 'current rank Start Date', 'Contact', 'Email'], null, 'A3');

        $rowNumber = 4;

        foreach ($rows as $index => [$name, $unit, $email]) {
            $sheet->fromArray([$index + 1, $name, $unit, '03 Oct 2028', '01 October, 2020', '0244000000', $email], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        (new Xlsx($spreadsheet))->save($this->listPath);
    }

    /**
     * Run the command and return the report keyed by the name as it appears on the list.
     *
     * @return array<string, array<string, string>>
     */
    private function runCommand(): array
    {
        $this->artisan('app:match-staff-list', [
            'file' => $this->listPath,
            '--output' => $this->reportPath,
            '--institution' => $this->institution->id,
        ])->assertSuccessful();

        $handle = fopen($this->reportPath, 'r');
        $header = fgetcsv($handle);
        $report = [];

        while (($row = fgetcsv($handle)) !== false) {
            $keyed = array_combine($header, $row);
            $report[$keyed['list_full_name']] = $keyed;
        }

        fclose($handle);

        return $report;
    }

    public function test_resolves_a_staff_number_by_email_and_takes_the_name_split_from_the_database(): void
    {
        $this->staff('Nunoo', 'Micah', 'Thomas', 'AUD0001', 'thomas.nunoo@audit.gov.gh');
        $this->writeList('ASSISTANT DIRECTOR', [
            ['Micah Thomas Nunoo', 'IT Audit', 'thomas.nunoo@audit.gov.gh'],
        ]);

        $report = $this->runCommand();
        $row = $report['Micah Thomas Nunoo'];

        $this->assertSame('AUD0001', $row['staff_number']);
        $this->assertSame('email', $row['match_method']);
        $this->assertSame('1', $row['match_candidates']);
        $this->assertSame('high', $row['confidence']);
        $this->assertSame('database', $row['name_source']);
        $this->assertSame('Nunoo', $row['surname']);
        $this->assertSame('Micah', $row['first_name']);
        $this->assertSame('Thomas', $row['other_names']);
        $this->assertSame('ASSISTANT DIRECTOR', $row['list_rank_group']);
    }

    public function test_matches_on_name_when_the_email_is_absent_from_the_database(): void
    {
        $this->staff('Tetteh', 'Seth', 'Joe', 'AUD0002');
        $this->writeList('DIRECTOR', [
            ['Seth Joe Tetteh', 'CAD', 'tetteh.seth@audit.gov.gh'],
        ]);

        $report = $this->runCommand();
        $row = $report['Seth Joe Tetteh'];

        $this->assertSame('AUD0002', $row['staff_number']);
        $this->assertSame('full_name', $row['match_method']);
        $this->assertSame('Tetteh', $row['surname']);
        $this->assertSame('Seth', $row['first_name']);
    }

    public function test_matches_on_name_regardless_of_word_order(): void
    {
        $this->staff('Owusu Afram', 'Gloria', '', 'AUD0003');
        $this->writeList('DIRECTOR', [
            ['Gloria Owusu Afram', 'CGAD/Health', 'gloria.owusu-afram@audit.gov.gh'],
        ]);

        $report = $this->runCommand();
        $row = $report['Gloria Owusu Afram'];

        $this->assertSame('AUD0003', $row['staff_number']);
        $this->assertSame('Owusu Afram', $row['surname']);
        $this->assertSame('Gloria', $row['first_name']);
    }

    public function test_falls_back_to_surname_and_first_name_when_other_names_differ(): void
    {
        $this->staff('Nangyele', 'Hayford', 'Suglo Kofi', 'AUD0004');
        $this->writeList('ASSISTANT DIRECTOR', [
            ['Hayford Suglo Nangyele', 'Wa', 'hayford.suglo@audit.gov.gh'],
        ]);

        $report = $this->runCommand();
        $row = $report['Hayford Suglo Nangyele'];

        $this->assertSame('AUD0004', $row['staff_number']);
        $this->assertSame('surname_and_first_name', $row['match_method']);
        $this->assertSame('medium', $row['confidence']);
        $this->assertSame('Suglo Kofi', $row['other_names']);
    }

    public function test_matches_a_staff_member_recorded_under_a_maiden_name(): void
    {
        $this->staff('Awuku', 'Anita', 'Makafui', 'AUD0005', null, 'Hudo');
        $this->writeList('ASSISTANT DIRECTOR', [
            ['Anita Makafui Hudo', 'Ho', 'anita.awuku@audit.gov.gh'],
        ]);

        $report = $this->runCommand();
        $row = $report['Anita Makafui Hudo'];

        $this->assertSame('AUD0005', $row['staff_number']);
        $this->assertSame('Awuku', $row['surname'], 'the current surname should win over the maiden name');
        $this->assertSame('Hudo', $row['db_maiden_name']);
    }

    public function test_flags_a_row_that_matches_more_than_one_staff_member(): void
    {
        $this->staff('Boateng', 'Kwame', 'Kofi', 'AUD0006');
        $this->staff('Boateng', 'Kwame', 'Kofi', 'AUD0007');
        $this->writeList('DIRECTOR', [
            ['Kwame Kofi Boateng', 'CAD', 'kwame.boateng@audit.gov.gh'],
        ]);

        $report = $this->runCommand();
        $row = $report['Kwame Kofi Boateng'];

        $this->assertSame('', $row['staff_number']);
        $this->assertSame('ambiguous_full_name', $row['match_method']);
        $this->assertSame('2', $row['match_candidates']);
        $this->assertStringContainsString('AUD0006', $row['review_note']);
    }

    public function test_derives_the_name_split_for_a_row_with_no_match(): void
    {
        $this->writeList('ASSISTANT DIRECTOR', [
            ['Patricia Naa Aku Pappoe-Pabi', 'Tema', 'patricia.pappoe-pabi@audit.gov.gh'],
        ]);

        $report = $this->runCommand();
        $row = $report['Patricia Naa Aku Pappoe-Pabi'];

        $this->assertSame('', $row['staff_number']);
        $this->assertSame('none', $row['match_method']);
        $this->assertSame('derived', $row['name_source']);
        $this->assertSame('Pappoe-Pabi', $row['surname']);
        $this->assertSame('Patricia', $row['first_name']);
        $this->assertSame('Naa Aku', $row['other_names']);
        $this->assertSame('no staff found with this surname', $row['review_note']);
    }

    public function test_reports_every_row_of_the_list(): void
    {
        $this->staff('Nunoo', 'Micah', 'Thomas', 'AUD0001', 'thomas.nunoo@audit.gov.gh');
        $this->writeList('ASSISTANT DIRECTOR', [
            ['Micah Thomas Nunoo', 'IT Audit', 'thomas.nunoo@audit.gov.gh'],
            ['Ebow Debrah Fynn', 'Cape Coast', ''],
            ['Kofi Opoku', 'Half Assini', 'kofi.opoku@audit.gov.gh'],
        ]);

        $report = $this->runCommand();

        $this->assertCount(3, $report);
        $this->assertSame('Fynn', $report['Ebow Debrah Fynn']['surname']);
        $this->assertSame('4', $report['Micah Thomas Nunoo']['sheet_row']);
        $this->assertSame('6', $report['Kofi Opoku']['sheet_row']);
    }

    public function test_fails_when_the_file_does_not_exist(): void
    {
        $this->artisan('app:match-staff-list', ['file' => '/does/not/exist.xlsx'])
            ->expectsOutputToContain('File not found')
            ->assertFailed();
    }
}
