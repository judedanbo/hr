<?php

namespace App\Console\Commands;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportOfficialEmails extends Command
{
    protected $signature = 'app:import-official-emails
        {file : Path to a CSV file with staff_number and email columns}
        {--dry-run : Report what would be recorded without saving anything}';

    protected $description = 'Record official @audit.gov.gh email contacts on staff records from a CSV, so those staff can self-register.';

    /**
     * Official addresses this run has already given to someone, keyed by address.
     *
     * @var array<string, int>
     */
    private array $assigned = [];

    public function handle(): int
    {
        $rows = $this->readRows($this->argument('file'));

        if ($rows === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $recorded = 0;
        $alreadyOnRecord = 0;
        $skipped = [];

        DB::transaction(function () use ($rows, $dryRun, &$recorded, &$alreadyOnRecord, &$skipped): void {
            foreach ($rows as $row) {
                [$personId, $outcome] = $this->assess($row['staff_number'], $row['email']);

                if ($outcome === 'already on record') {
                    $alreadyOnRecord++;

                    continue;
                }

                if ($outcome !== null) {
                    $skipped[] = [$row['line'], $row['staff_number'], $row['email'], $outcome];

                    continue;
                }

                $this->assigned[$row['email']] = $personId;
                $recorded++;

                if (! $dryRun) {
                    Contact::create([
                        'person_id' => $personId,
                        'contact_type' => ContactTypeEnum::EMAIL,
                        'contact' => $row['email'],
                    ]);
                }
            }
        });

        if ($skipped !== []) {
            $this->warn('Skipped rows:');
            $this->table(['Line', 'Staff number', 'Email', 'Reason'], $skipped);
        }

        $this->info(($dryRun ? 'Would record: ' : 'Recorded: ') . $recorded);
        $this->info('Already on record: ' . $alreadyOnRecord);
        $this->info('Skipped: ' . count($skipped));

        if ($dryRun) {
            $this->comment('Dry run: nothing was saved.');
        }

        return self::SUCCESS;
    }

    /**
     * Decide what to do with one row.
     *
     * An official address decides where a person's first password is sent, so
     * a row is only accepted when it names exactly one person and the address
     * is not already tied to anyone else.
     *
     * @return array{0: int|null, 1: string|null} the person to record the address for, or why not
     */
    private function assess(string $staffNumber, string $email): array
    {
        if ($staffNumber === '' || $email === '') {
            return [null, 'staff number or email is missing'];
        }

        if (! $this->isOfficialAddress($email)) {
            return [null, 'not an @audit.gov.gh address'];
        }

        $people = InstitutionPerson::query()
            ->where('staff_number', $staffNumber)
            ->whereHas('person')
            ->pluck('person_id')
            ->unique();

        if ($people->isEmpty()) {
            return [null, 'staff number not found'];
        }

        if ($people->count() > 1) {
            return [null, 'staff number matches more than one person'];
        }

        $personId = (int) $people->first();

        if (isset($this->assigned[$email])) {
            return $this->assigned[$email] === $personId
                ? [$personId, 'already on record']
                : [null, 'address is given to more than one person in this file'];
        }

        $holders = Contact::query()
            ->where('contact_type', ContactTypeEnum::EMAIL)
            ->whereNull('valid_end')
            ->whereRaw('LOWER(TRIM(contact)) = ?', [$email])
            ->pluck('person_id');

        if ($holders->contains($personId)) {
            return [$personId, 'already on record'];
        }

        if ($holders->isNotEmpty()) {
            return [null, "address is on another person's record"];
        }

        $isSomeoneElsesLogin = User::query()
            ->where('email', $email)
            ->where(fn ($query) => $query->whereNull('person_id')->orWhere('person_id', '!=', $personId))
            ->exists();

        if ($isSomeoneElsesLogin) {
            return [null, "address is another user's login"];
        }

        return [$personId, null];
    }

    /**
     * Whether the value is a well-formed address on the organisation's own domain,
     * by the same rule the contact model and self-registration use.
     */
    private function isOfficialAddress(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return (new Contact([
            'contact_type' => ContactTypeEnum::EMAIL,
            'contact' => $email,
        ]))->isProtectedOrgEmail();
    }

    /**
     * Read the data rows of the CSV, or report why it cannot be used.
     *
     * @return array<int, array{line: int, staff_number: string, email: string}>|null
     */
    private function readRows(string $path): ?array
    {
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Cannot read {$path}.");

            return null;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, null, ',', '"', '');

        $columns = array_map(
            fn ($name): string => Str::of((string) $name)->replace("\u{FEFF}", '')->trim()->lower()->replace(' ', '_')->toString(),
            $header === false ? [] : $header,
        );

        $staffNumberColumn = array_search('staff_number', $columns, true);
        $emailColumn = array_search('email', $columns, true);

        if ($staffNumberColumn === false || $emailColumn === false) {
            fclose($handle);
            $this->error('The first line must name the columns and include staff_number and email.');

            return null;
        }

        $rows = [];
        $line = 1;

        while (($cells = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;

            if ($cells === [null]) {
                continue;
            }

            $rows[] = [
                'line' => $line,
                'staff_number' => trim((string) ($cells[$staffNumberColumn] ?? '')),
                'email' => Str::lower(trim((string) ($cells[$emailColumn] ?? ''))),
            ];
        }

        fclose($handle);

        return $rows;
    }
}
