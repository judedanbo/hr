<?php

namespace App\Console\Commands;

use App\Exports\StaffUnitLookupExport;
use App\Imports\SpreadsheetRowsImport;
use App\Services\Staff\StaffUnitLookupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LookupStaffUnits extends Command
{
    protected $signature = 'app:lookup-staff-units
        {file : Path to the .xlsx or .csv file listing the people to look up}
        {--email-column=Email : Heading of the column holding the email address to match on}
        {--name-column=Name : Heading of the column holding the name, used when the email does not match}
        {--sheet=0 : Zero-based index of the worksheet to read}
        {--output= : Where to write the result spreadsheet (defaults to <file>-units.xlsx beside the input)}';

    protected $description = 'Match a list of people from a spreadsheet against the staff register and report the department and unit each one belongs to.';

    public function handle(StaffUnitLookupService $service): int
    {
        $path = $this->argument('file');

        if (! File::isFile($path)) {
            $this->error("No file found at {$path}.");

            return self::FAILURE;
        }

        $sheets = Excel::toArray(new SpreadsheetRowsImport, $path);
        $rows = $sheets[(int) $this->option('sheet')] ?? null;

        if ($rows === null || $rows === []) {
            $this->error('Worksheet ' . $this->option('sheet') . ' is empty or does not exist.');

            return self::FAILURE;
        }

        $headings = array_shift($rows);
        $emailIndex = $this->columnIndex($headings, $this->option('email-column'));
        $nameIndex = $this->columnIndex($headings, $this->option('name-column'));

        if ($emailIndex === null && $nameIndex === null) {
            $this->error('Neither the email nor the name column was found. Available headings:');
            $this->line(collect($headings)->filter()->map(fn ($heading): string => '  ' . Str::squish((string) $heading))->implode(PHP_EOL));

            return self::FAILURE;
        }

        $entries = [];
        foreach ($rows as $offset => $row) {
            if (collect($row)->filter(fn ($cell): bool => $cell !== null && $cell !== '')->isEmpty()) {
                continue;
            }

            $entries[] = [
                'row' => $offset + 2,
                'name' => $nameIndex === null ? null : (string) ($row[$nameIndex] ?? ''),
                'email' => $emailIndex === null ? null : (string) ($row[$emailIndex] ?? ''),
            ];
        }

        if ($entries === []) {
            $this->error('The worksheet has a heading row but no data rows.');

            return self::FAILURE;
        }

        $this->info('Looking up ' . count($entries) . ' ' . Str::plural('person', count($entries)) . '...');

        $results = $service->lookup($entries);

        $destination = $this->resolveDestination($path);
        $this->writeResults($results, $destination);

        $this->newLine();
        $this->table(
            ['Match', 'Count'],
            collect($results)
                ->countBy('match_type')
                ->sortDesc()
                ->map(fn (int $count, string $matchType): array => [$matchType, $count])
                ->values()
                ->all()
        );

        $this->info("Results written to {$destination}");

        return self::SUCCESS;
    }

    /**
     * Locate a column by its printed heading, ignoring case, padding and the
     * non-breaking spaces that survey exports carry.
     *
     * @param  array<int, mixed>  $headings
     */
    private function columnIndex(array $headings, string $wanted): ?int
    {
        $target = $this->normalizeHeading($wanted);

        foreach ($headings as $index => $heading) {
            if ($heading !== null && $this->normalizeHeading((string) $heading) === $target) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeHeading(string $heading): string
    {
        return Str::of($heading)->replace(["\u{00A0}", "\u{200B}"], ' ')->squish()->lower()->__toString();
    }

    private function resolveDestination(string $sourcePath): string
    {
        $output = $this->option('output');

        if ($output !== null && $output !== '') {
            return Str::startsWith($output, DIRECTORY_SEPARATOR)
                ? $output
                : getcwd() . DIRECTORY_SEPARATOR . $output;
        }

        $directory = File::dirname(File::isFile($sourcePath) ? realpath($sourcePath) : $sourcePath);

        return $directory . DIRECTORY_SEPARATOR . File::name($sourcePath) . '-units.xlsx';
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function writeResults(array $results, string $destination): void
    {
        $temporaryName = 'staff-unit-lookup/' . Str::uuid() . '.xlsx';

        Excel::store(new StaffUnitLookupExport($results), $temporaryName, 'local');

        File::ensureDirectoryExists(File::dirname($destination));
        File::move(Storage::disk('local')->path($temporaryName), $destination);
    }
}
