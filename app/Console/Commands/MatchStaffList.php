<?php

namespace App\Console\Commands;

use App\Services\Staff\StaffListMatcher;
use App\Services\Staff\StaffNameSplitter;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reconciles an externally supplied staff list (e.g. a promotion eligibility list)
 * against institution_person, writing a CSV report that carries the staff number
 * and the surname / first name / other names breakdown for every row.
 */
class MatchStaffList extends Command
{
    protected $signature = 'app:match-staff-list
        {file : Path to the .xlsx or .csv list to reconcile}
        {--sheet= : Sheet name or zero-based index to read (defaults to the first sheet)}
        {--output= : Where to write the CSV report (defaults to <file>-matched.csv)}
        {--institution= : Only match against staff of this institution id}
        {--name-column= : Column letter holding the full name, when auto-detection fails}
        {--email-column= : Column letter holding the email address}';

    protected $description = 'Match a staff list against the staff database, resolving staff numbers and splitting names into surname and other names.';

    public function handle(StaffListMatcher $matcher, StaffNameSplitter $splitter): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $sheet = $this->loadSheet($file);

        if ($sheet === null) {
            $this->error('Could not open the requested sheet.');

            return self::FAILURE;
        }

        $rows = $this->readRows($sheet);
        $columns = $this->resolveColumns($rows);

        if ($columns['name'] === null) {
            $this->error('Could not find a "name" column. Pass --name-column=B to set it explicitly.');

            return self::FAILURE;
        }

        $entries = $this->extractEntries($rows, $columns);

        if ($entries === []) {
            $this->error('No data rows found in the sheet.');

            return self::FAILURE;
        }

        $institution = $this->option('institution') === null ? null : (int) $this->option('institution');

        $this->info('Indexing staff records...');
        $indexed = $matcher->index($institution);
        $this->line("  indexed {$indexed} staff records");

        $report = [];
        $summary = [];

        $bar = $this->output->createProgressBar(count($entries));
        $bar->start();

        foreach ($entries as $entry) {
            $result = $matcher->match($entry['name'], $entry['email']);
            $report[] = $this->reportRow($entry, $result, $matcher);
            $key = $result['matched'] ? $result['method'] : 'UNMATCHED (' . $result['method'] . ')';
            $summary[$key] = ($summary[$key] ?? 0) + 1;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $output = $this->option('output') ?? preg_replace('/\.[^.]+$/', '', $file) . '-matched.csv';
        $this->writeCsv((string) $output, $report);

        $this->table(
            ['Match method', 'Rows'],
            collect($summary)->map(fn (int $count, string $method): array => [$method, $count])->values()->all(),
        );

        $matched = collect($report)->where('staff_number', '!=', '')->count();
        $this->info(sprintf('%d of %d rows resolved to a staff number.', $matched, count($report)));
        $this->info("Report written to {$output}");

        return self::SUCCESS;
    }

    private function loadSheet(string $file): ?Worksheet
    {
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);

        $sheetOption = $this->option('sheet');

        if ($sheetOption === null) {
            return $spreadsheet->getSheet(0);
        }

        if (is_numeric($sheetOption)) {
            return $spreadsheet->getSheet((int) $sheetOption);
        }

        return $spreadsheet->getSheetByName((string) $sheetOption);
    }

    /**
     * Read the sheet into row number => [column letter => trimmed value].
     *
     * @return array<int, array<string, string>>
     */
    private function readRows(Worksheet $sheet): array
    {
        $rows = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            $iterator = $row->getCellIterator();
            $iterator->setIterateOnlyExistingCells(true);

            foreach ($iterator as $cell) {
                $value = $cell->getFormattedValue();
                $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

                if ($value !== '') {
                    $cells[$cell->getColumn()] = $value;
                }
            }

            if ($cells !== []) {
                $rows[$row->getRowIndex()] = $cells;
            }
        }

        return $rows;
    }

    /**
     * Locate the columns of interest from the sheet's header row.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array{name: ?string, unit: ?string, email: ?string, retirement: ?string, rank_start: ?string, contact: ?string, index: ?string}
     */
    private function resolveColumns(array $rows): array
    {
        $columns = [
            'name' => $this->option('name-column'),
            'unit' => null,
            'email' => $this->option('email-column'),
            'retirement' => null,
            'rank_start' => null,
            'contact' => null,
            'index' => null,
        ];

        $patterns = [
            'index' => '/^no\.?$/i',
            'name' => '/name/i',
            'unit' => '/unit|station|department/i',
            'email' => '/e-?mail/i',
            'retirement' => '/retire/i',
            'rank_start' => '/start date|rank start/i',
            'contact' => '/contact|phone|mobile/i',
        ];

        foreach ($rows as $cells) {
            $hasName = false;

            foreach ($cells as $value) {
                if (preg_match('/name/i', $value) === 1) {
                    $hasName = true;
                }
            }

            if (! $hasName) {
                continue;
            }

            foreach ($cells as $column => $value) {
                foreach ($patterns as $key => $pattern) {
                    if ($columns[$key] === null && preg_match($pattern, $value) === 1) {
                        $columns[$key] = $column;
                    }
                }
            }

            break;
        }

        return $columns;
    }

    /**
     * Pull the data rows out of the sheet, carrying the group heading each row sits under.
     *
     * @param  array<int, array<string, string>>  $rows
     * @param  array{name: ?string, unit: ?string, email: ?string, retirement: ?string, rank_start: ?string, contact: ?string, index: ?string}  $columns
     * @return list<array{row: int, group: string, name: string, unit: string, email: ?string, retirement: string, rank_start: string, contact: string}>
     */
    private function extractEntries(array $rows, array $columns): array
    {
        $entries = [];
        $group = '';

        foreach ($rows as $rowIndex => $cells) {
            $name = $cells[$columns['name']] ?? '';

            if (count($cells) === 1) {
                $group = (string) reset($cells);

                continue;
            }

            if ($name === '' || preg_match('/^(full )?name$/i', $name) === 1) {
                continue;
            }

            $entries[] = [
                'row' => $rowIndex,
                'group' => $group,
                'name' => $name,
                'unit' => $cells[$columns['unit']] ?? '',
                'email' => ($cells[$columns['email']] ?? '') ?: null,
                'retirement' => $cells[$columns['retirement']] ?? '',
                'rank_start' => $cells[$columns['rank_start']] ?? '',
                'contact' => $cells[$columns['contact']] ?? '',
            ];
        }

        return $entries;
    }

    /**
     * @param  array{row: int, group: string, name: string, unit: string, email: ?string, retirement: string, rank_start: string, contact: string}  $entry
     * @param  array<string, mixed>  $result
     * @return array<string, string>
     */
    private function reportRow(array $entry, array $result, StaffListMatcher $matcher): array
    {
        $staff = $result['staff'];

        return [
            'sheet_row' => (string) $entry['row'],
            'list_rank_group' => $entry['group'],
            'list_full_name' => $entry['name'],
            'surname' => $result['surname'],
            'first_name' => $result['first_name'],
            'other_names' => $result['other_names'],
            'name_source' => $result['name_source'],
            'split_strategy' => $result['split_strategy'],
            'staff_number' => $staff['staff_number'] ?? '',
            'file_number' => $staff['file_number'] ?? '',
            'old_staff_number' => $staff['old_staff_number'] ?? '',
            'person_id' => isset($staff['person_id']) ? (string) $staff['person_id'] : '',
            'staff_id' => isset($staff['staff_id']) ? (string) $staff['staff_id'] : '',
            'db_full_name' => $staff['full_name'] ?? '',
            'db_maiden_name' => $staff['maiden_name'] ?? '',
            'db_rank' => $staff['rank'] ?? '',
            'db_unit' => $staff['unit'] ?? '',
            'list_unit' => $entry['unit'],
            'list_email' => $entry['email'] ?? '',
            'db_emails' => implode('; ', $staff['emails'] ?? []),
            'list_retirement_date' => $entry['retirement'],
            'list_rank_start_date' => $entry['rank_start'],
            'list_contact' => $entry['contact'],
            'match_method' => $result['method'],
            'match_candidates' => (string) $result['candidates'],
            'confidence' => $result['confidence'],
            'review_note' => $result['matched'] ? '' : $this->reviewNote($result, $matcher),
        ];
    }

    /**
     * Suggest staff who share the row's surname, to speed up manual reconciliation.
     *
     * @param  array<string, mixed>  $result
     */
    private function reviewNote(array $result, StaffListMatcher $matcher): string
    {
        $candidates = $matcher->surnameCandidates($result['surname']);

        if ($candidates === []) {
            return 'no staff found with this surname';
        }

        $suggestions = collect($candidates)
            ->take(5)
            ->map(fn (array $staff): string => trim("{$staff['full_name']} [{$staff['staff_number']}]"))
            ->implode('; ');

        return sprintf('%d staff share this surname: %s', count($candidates), $suggestions);
    }

    /**
     * @param  list<array<string, string>>  $report
     */
    private function writeCsv(string $path, array $report): void
    {
        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new \RuntimeException("Unable to open {$path} for writing.");
        }

        fputcsv($handle, array_keys($report[0]));

        foreach ($report as $row) {
            fputcsv($handle, array_values($row));
        }

        fclose($handle);
    }
}
