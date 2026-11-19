<?php

namespace Tests\Feature;

use App\Enums\ContactTypeEnum;
use App\Mail\UserCreated;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ImportOfficialEmailsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_it_records_official_emails_against_staff_numbers(): void
    {
        $ama = $this->staffMember('GAS10001');
        $kofi = $this->staffMember('GAS10002');

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'GAS10001,Ama.Mensah@Audit.gov.gh',
            ' GAS10002 , kofi.boateng@audit.gov.gh ',
        ])])
            ->expectsOutputToContain('Recorded: 2')
            ->expectsOutputToContain('Skipped: 0')
            ->assertExitCode(0);

        $this->assertSame(['ama.mensah@audit.gov.gh'], $this->officialEmailsOf($ama));
        $this->assertSame(['kofi.boateng@audit.gov.gh'], $this->officialEmailsOf($kofi));
    }

    public function test_an_imported_address_lets_that_member_of_staff_register(): void
    {
        Mail::fake();

        $staff = $this->staffMember('GAS10001', ['first_name' => 'Ama', 'surname' => 'Mensah']);

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'GAS10001,ama.mensah@audit.gov.gh',
        ])])->assertExitCode(0);

        $this->post('/register', [
            'staff_number' => 'GAS10001',
            'first_name' => 'Ama',
            'surname' => 'Mensah',
            'email' => 'ama.mensah',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'person_id' => $staff->person_id,
            'email' => 'ama.mensah@audit.gov.gh',
        ]);
        Mail::assertSent(UserCreated::class, fn (UserCreated $mail): bool => $mail->hasTo('ama.mensah@audit.gov.gh'));
    }

    public function test_a_dry_run_reports_without_saving(): void
    {
        $staff = $this->staffMember('GAS10001');

        $this->artisan('app:import-official-emails', [
            'file' => $this->csv([
                'staff_number,email',
                'GAS10001,ama.mensah@audit.gov.gh',
                'NOSUCH01,nobody@audit.gov.gh',
            ]),
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('Would record: 1')
            ->expectsOutputToContain('Skipped: 1')
            ->expectsOutputToContain('Dry run: nothing was saved.')
            ->assertExitCode(0);

        $this->assertSame([], $this->officialEmailsOf($staff));
        $this->assertSame(0, Contact::count());
    }

    public function test_rows_that_cannot_be_matched_to_one_member_of_staff_are_skipped(): void
    {
        $known = $this->staffMember('GAS10001');
        $this->staffMember('GAS10009');
        $this->staffMember('GAS10009');

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'NOSUCH01,nobody@audit.gov.gh',
            'GAS10009,shared.number@audit.gov.gh',
            ',missing.number@audit.gov.gh',
            'GAS10001,',
        ])])
            ->expectsOutputToContain('staff number not found')
            ->expectsOutputToContain('staff number matches more than one person')
            ->expectsOutputToContain('staff number or email is missing')
            ->expectsOutputToContain('Recorded: 0')
            ->expectsOutputToContain('Skipped: 4')
            ->assertExitCode(0);

        $this->assertSame([], $this->officialEmailsOf($known));
        $this->assertSame(0, Contact::count());
    }

    public function test_addresses_outside_the_official_domain_are_skipped(): void
    {
        $staff = $this->staffMember('GAS10001');

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'GAS10001,ama.mensah@gmail.com',
            'GAS10001,ama.mensah@sub.audit.gov.gh',
            'GAS10001,ama.mensah@audit.gov.gh.example.com',
            'GAS10001,not-an-address',
            'GAS10001,ama mensah@audit.gov.gh',
        ])])
            ->expectsOutputToContain('not an @audit.gov.gh address')
            ->expectsOutputToContain('Recorded: 0')
            ->expectsOutputToContain('Skipped: 5')
            ->assertExitCode(0);

        $this->assertSame(0, Contact::where('person_id', $staff->person_id)->count());
    }

    public function test_an_address_already_on_the_record_is_not_duplicated(): void
    {
        $staff = $this->staffMember('GAS10001');
        $this->recordEmail($staff, 'Ama.Mensah@audit.gov.gh');

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'GAS10001,ama.mensah@audit.gov.gh',
            'GAS10001,ama.mensah@audit.gov.gh',
        ])])
            ->expectsOutputToContain('Recorded: 0')
            ->expectsOutputToContain('Already on record: 2')
            ->expectsOutputToContain('Skipped: 0')
            ->assertExitCode(0);

        $this->assertSame(1, Contact::where('person_id', $staff->person_id)->count());
    }

    public function test_an_address_that_is_no_longer_valid_on_the_record_is_recorded_again(): void
    {
        $staff = $this->staffMember('GAS10001');
        $this->recordEmail($staff, 'ama.mensah@audit.gov.gh', ['valid_end' => now()->subMonth()]);

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'GAS10001,ama.mensah@audit.gov.gh',
        ])])
            ->expectsOutputToContain('Recorded: 1')
            ->assertExitCode(0);

        $this->assertSame(['ama.mensah@audit.gov.gh'], $this->officialEmailsOf($staff));
    }

    public function test_an_address_is_never_given_to_a_second_person(): void
    {
        $ama = $this->staffMember('GAS10001');
        $kofi = $this->staffMember('GAS10002');
        $esi = $this->staffMember('GAS10003');
        $yaw = $this->staffMember('GAS10004');

        $this->recordEmail($ama, 'ama.mensah@audit.gov.gh');
        User::factory()->create(['person_id' => $esi->person_id, 'email' => 'esi.owusu@audit.gov.gh']);

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'GAS10002,ama.mensah@audit.gov.gh',
            'GAS10002,esi.owusu@audit.gov.gh',
            'GAS10002,kofi.boateng@audit.gov.gh',
            'GAS10004,kofi.boateng@audit.gov.gh',
        ])])
            ->expectsOutputToContain("address is on another person's record")
            ->expectsOutputToContain("address is another user's login")
            ->expectsOutputToContain('address is given to more than one person in this file')
            ->expectsOutputToContain('Recorded: 1')
            ->expectsOutputToContain('Skipped: 3')
            ->assertExitCode(0);

        $this->assertSame(['kofi.boateng@audit.gov.gh'], $this->officialEmailsOf($kofi));
        $this->assertSame([], $this->officialEmailsOf($yaw));
        $this->assertSame(['ama.mensah@audit.gov.gh'], $this->officialEmailsOf($ama));
    }

    public function test_a_persons_own_login_address_can_be_recorded(): void
    {
        $staff = $this->staffMember('GAS10001');
        User::factory()->create(['person_id' => $staff->person_id, 'email' => 'ama.mensah@audit.gov.gh']);

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'staff_number,email',
            'GAS10001,ama.mensah@audit.gov.gh',
        ])])
            ->expectsOutputToContain('Recorded: 1')
            ->assertExitCode(0);

        $this->assertSame(['ama.mensah@audit.gov.gh'], $this->officialEmailsOf($staff));
    }

    public function test_columns_may_come_in_any_order_among_others_and_after_a_byte_order_mark(): void
    {
        $staff = $this->staffMember('GAS10001');

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            "\u{FEFF}Name,Email,Staff Number",
            '"Mensah, Ama",ama.mensah@audit.gov.gh,GAS10001',
            '',
        ])])
            ->expectsOutputToContain('Recorded: 1')
            ->assertExitCode(0);

        $this->assertSame(['ama.mensah@audit.gov.gh'], $this->officialEmailsOf($staff));
    }

    public function test_it_fails_when_the_file_cannot_be_read(): void
    {
        $this->artisan('app:import-official-emails', ['file' => sys_get_temp_dir() . '/no-such-official-emails.csv'])
            ->expectsOutputToContain('Cannot read')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_the_columns_are_not_named(): void
    {
        $staff = $this->staffMember('GAS10001');

        $this->artisan('app:import-official-emails', ['file' => $this->csv([
            'GAS10001,ama.mensah@audit.gov.gh',
        ])])
            ->expectsOutputToContain('must name the columns and include staff_number and email')
            ->assertExitCode(1);

        $this->assertSame([], $this->officialEmailsOf($staff));
    }

    /**
     * @param  array<string, mixed>  $personAttributes
     */
    private function staffMember(string $staffNumber, array $personAttributes = []): InstitutionPerson
    {
        return InstitutionPerson::factory()->create([
            'person_id' => Person::factory()->create($personAttributes)->id,
            'staff_number' => $staffNumber,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function recordEmail(InstitutionPerson $staff, string $email, array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'person_id' => $staff->person_id,
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => $email,
        ], $attributes));
    }

    /**
     * The current official email contacts on the staff member's record.
     *
     * @return array<int, string>
     */
    private function officialEmailsOf(InstitutionPerson $staff): array
    {
        return Contact::query()
            ->where('person_id', $staff->person_id)
            ->where('contact_type', ContactTypeEnum::EMAIL)
            ->whereNull('valid_end')
            ->where('contact', 'like', '%@audit.gov.gh')
            ->orderBy('id')
            ->pluck('contact')
            ->all();
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function csv(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'official-emails-');
        file_put_contents($path, implode("\n", $lines) . "\n");

        return $this->files[] = $path;
    }
}
