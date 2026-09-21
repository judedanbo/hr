<?php

namespace App\Services\Staff;

use App\Enums\ContactTypeEnum;
use App\Models\InstitutionPerson;
use Illuminate\Database\Eloquent\Builder;

/**
 * Matches rows of an externally supplied staff list (a promotion list, a training
 * nominal roll, ...) against the staff records held in institution_person, so that
 * staff numbers and the authoritative name breakdown can be pulled back out.
 *
 * Staff are indexed once into memory and each list row is then resolved against
 * those indexes, strongest signal first.
 *
 * @phpstan-type StaffRecord array{staff_id: int, person_id: int, staff_number: ?string, file_number: ?string, old_staff_number: ?string, surname: string, first_name: string, other_names: string, maiden_name: ?string, full_name: string, emails: list<string>, rank: ?string, unit: ?string}
 * @phpstan-type MatchResult array{matched: bool, method: string, confidence: string, candidates: int, staff: ?StaffRecord, surname: string, first_name: string, other_names: string, name_source: string, split_strategy: string}
 */
class StaffListMatcher
{
    /** @var array<string, list<StaffRecord>> */
    private array $byEmail = [];

    /** @var array<string, list<StaffRecord>> */
    private array $byNameKey = [];

    /** @var array<string, list<StaffRecord>> */
    private array $bySurnameAndFirst = [];

    /** @var array<string, list<StaffRecord>> */
    private array $bySurname = [];

    private bool $indexed = false;

    public function __construct(private readonly StaffNameSplitter $splitter) {}

    /**
     * Load every staff record into the lookup indexes. Returns the number indexed.
     */
    public function index(?int $institutionId = null): int
    {
        $this->byEmail = [];
        $this->byNameKey = [];
        $this->bySurnameAndFirst = [];
        $this->bySurname = [];

        $count = 0;

        $this->baseQuery($institutionId)->chunkById(500, function ($staff) use (&$count): void {
            foreach ($staff as $record) {
                $this->addToIndexes($this->toRecord($record));
                $count++;
            }
        }, 'institution_person.id', 'id');

        $this->indexed = true;

        return $count;
    }

    /**
     * Resolve a single list row to a staff record.
     *
     * @return MatchResult
     */
    public function match(string $fullName, ?string $email = null): array
    {
        if (! $this->indexed) {
            $this->index();
        }

        $derived = $this->splitter->split($fullName, $email);

        foreach ($this->lookupStrategies($fullName, $email, $derived) as $method => $candidates) {
            $candidates = $this->unique($candidates);

            if ($candidates === []) {
                continue;
            }

            if (count($candidates) > 1) {
                return $this->miss('ambiguous_' . $method, count($candidates), $derived);
            }

            return $this->hit($method, $candidates[0], $derived);
        }

        return $this->miss('none', 0, $derived);
    }

    /**
     * Candidate lookups ordered strongest signal first.
     *
     * @param  array{surname: string, first_name: string, other_names: string, strategy: string}  $derived
     * @return array<string, list<StaffRecord>>
     */
    private function lookupStrategies(string $fullName, ?string $email, array $derived): array
    {
        $emailKey = $email === null ? '' : strtolower(trim($email));
        $nameKey = $this->splitter->tokenKey($fullName);
        $surnameFirstKey = $this->splitter->squash($derived['surname']) . '|' . $this->splitter->squash($derived['first_name']);

        $hasSurnameAndFirst = $this->splitter->squash($derived['surname']) !== ''
            && $this->splitter->squash($derived['first_name']) !== '';

        return [
            'email' => $emailKey === '' ? [] : ($this->byEmail[$emailKey] ?? []),
            'full_name' => $nameKey === '' ? [] : ($this->byNameKey[$nameKey] ?? []),
            'surname_and_first_name' => $hasSurnameAndFirst ? ($this->bySurnameAndFirst[$surnameFirstKey] ?? []) : [],
        ];
    }

    /**
     * Collapse candidates that are the same staff record reached through more than
     * one index entry (for example via both surname and maiden name).
     *
     * @param  list<StaffRecord>  $candidates
     * @return list<StaffRecord>
     */
    private function unique(array $candidates): array
    {
        $seen = [];
        $unique = [];

        foreach ($candidates as $candidate) {
            if (isset($seen[$candidate['staff_id']])) {
                continue;
            }

            $seen[$candidate['staff_id']] = true;
            $unique[] = $candidate;
        }

        return $unique;
    }

    /**
     * Staff sharing a surname with the row, offered for manual review when nothing matched.
     *
     * @return list<StaffRecord>
     */
    public function surnameCandidates(string $surname): array
    {
        return $this->unique($this->bySurname[$this->splitter->squash($surname)] ?? []);
    }

    /**
     * @param  StaffRecord  $staff
     * @param  array{surname: string, first_name: string, other_names: string, strategy: string}  $derived
     * @return MatchResult
     */
    private function hit(string $method, array $staff, array $derived): array
    {
        return [
            'matched' => true,
            'method' => $method,
            'confidence' => $method === 'surname_and_first_name' ? 'medium' : 'high',
            'candidates' => 1,
            'staff' => $staff,
            'surname' => $staff['surname'],
            'first_name' => $staff['first_name'],
            'other_names' => $staff['other_names'],
            'name_source' => 'database',
            'split_strategy' => $derived['strategy'],
        ];
    }

    /**
     * @param  array{surname: string, first_name: string, other_names: string, strategy: string}  $derived
     * @return MatchResult
     */
    private function miss(string $method, int $candidates, array $derived): array
    {
        return [
            'matched' => false,
            'method' => $method,
            'confidence' => 'none',
            'candidates' => $candidates,
            'staff' => null,
            'surname' => $derived['surname'],
            'first_name' => $derived['first_name'],
            'other_names' => $derived['other_names'],
            'name_source' => 'derived',
            'split_strategy' => $derived['strategy'],
        ];
    }

    private function baseQuery(?int $institutionId): Builder
    {
        return InstitutionPerson::query()
            ->when($institutionId !== null, fn (Builder $query): Builder => $query->where('institution_id', $institutionId))
            ->whereHas('person')
            ->with([
                'person:id,title,surname,first_name,other_names,maiden_name',
                'person.contacts' => fn ($query) => $query->where('contact_type', ContactTypeEnum::EMAIL->value),
            ])
            ->currentRank()
            ->currentUnit();
    }

    /**
     * @return StaffRecord
     */
    private function toRecord(InstitutionPerson $staff): array
    {
        $person = $staff->person;

        $emails = $person->contacts
            ->pluck('contact')
            ->filter(fn (?string $contact): bool => $contact !== null && trim($contact) !== '')
            ->map(fn (string $contact): string => strtolower(trim($contact)))
            ->unique()
            ->values()
            ->all();

        return [
            'staff_id' => (int) $staff->getKey(),
            'person_id' => (int) $person->getKey(),
            'staff_number' => $staff->staff_number,
            'file_number' => $staff->file_number,
            'old_staff_number' => $staff->old_staff_number,
            'surname' => (string) $person->surname,
            'first_name' => (string) $person->first_name,
            'other_names' => (string) $person->other_names,
            'maiden_name' => $person->maiden_name,
            'full_name' => trim(preg_replace('/\s+/', ' ', "{$person->first_name} {$person->other_names} {$person->surname}") ?? ''),
            'emails' => $emails,
            'rank' => $staff->currentRank?->job?->name,
            'unit' => $staff->currentUnit?->unit?->name,
        ];
    }

    /**
     * @param  StaffRecord  $record
     */
    private function addToIndexes(array $record): void
    {
        foreach ($record['emails'] as $email) {
            $this->byEmail[$email][] = $record;
        }

        $this->byNameKey[$this->splitter->tokenKey($record['first_name'], $record['other_names'], $record['surname'])][] = $record;

        if ($record['maiden_name'] !== null && trim($record['maiden_name']) !== '') {
            $this->byNameKey[$this->splitter->tokenKey($record['first_name'], $record['other_names'], $record['maiden_name'])][] = $record;
        }

        $surname = $this->splitter->squash($record['surname']);
        $this->bySurnameAndFirst[$surname . '|' . $this->splitter->squash($record['first_name'])][] = $record;
        $this->bySurname[$surname][] = $record;
    }
}
