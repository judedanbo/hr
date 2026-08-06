<?php

namespace App\Services\Staff;

use App\Enums\ContactTypeEnum;
use App\Enums\UnitType;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\JobStaff;
use App\Models\Person;
use App\Models\StaffUnit;
use App\Models\Unit;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Resolves an externally supplied list of people (name + email, e.g. a training
 * attendance sheet) against the staff register, reporting the department and
 * unit each one currently belongs to.
 */
class StaffUnitLookupService
{
    public const MATCH_EMAIL = 'Email';

    public const MATCH_NAME = 'Name';

    public const MATCH_AMBIGUOUS = 'Ambiguous name';

    public const MATCH_NOT_STAFF = 'Person not a staff member';

    public const MATCH_NONE = 'Not found';

    /**
     * Guards against a cycle in the unit parent chain.
     */
    private const MAX_HIERARCHY_DEPTH = 20;

    /**
     * Look up every supplied entry.
     *
     * @param  array<int, array{row?: int|null, name?: string|null, email?: string|null}>  $entries
     * @return array<int, array{row: int|null, source_name: string|null, source_email: string|null, match_type: string, staff_number: string|null, file_number: string|null, staff_name: string|null, rank: string|null, department: string|null, unit: string|null, unit_type: string|null, hierarchy: string|null, assignment_start: string|null, assignment_status: string|null}>
     */
    public function lookup(array $entries): array
    {
        $personIdsByEmail = $this->personIdsByEmail(
            collect($entries)->map(fn (array $entry): string => $this->normalizeEmail($entry['email'] ?? null))
                ->filter()
                ->unique()
                ->all()
        );

        $personIdsByName = $this->personIdsByName();

        $matches = [];
        foreach ($entries as $index => $entry) {
            $matches[$index] = $this->matchPerson($entry, $personIdsByEmail, $personIdsByName);
        }

        $staffByPersonId = $this->staffByPersonId(
            collect($matches)->pluck('person_id')->filter()->unique()->all()
        );

        $unitsById = $this->unitsById();
        $assignmentsByStaffId = $this->assignmentsByStaffId($staffByPersonId->pluck('id')->all());
        $ranksByStaffId = $this->ranksByStaffId($staffByPersonId->pluck('id')->all());

        $results = [];
        foreach ($entries as $index => $entry) {
            $results[] = $this->buildResult(
                $entry,
                $matches[$index],
                $staffByPersonId,
                $assignmentsByStaffId,
                $ranksByStaffId,
                $unitsById
            );
        }

        return $results;
    }

    /**
     * @param  array{row?: int|null, name?: string|null, email?: string|null}  $entry
     * @param  array<string, array<int, int>>  $personIdsByEmail
     * @param  array<string, array<int, int>>  $personIdsByName
     * @return array{person_id: int|null, match_type: string}
     */
    private function matchPerson(array $entry, array $personIdsByEmail, array $personIdsByName): array
    {
        $email = $this->normalizeEmail($entry['email'] ?? null);

        if ($email !== '' && isset($personIdsByEmail[$email]) && count($personIdsByEmail[$email]) === 1) {
            return ['person_id' => $personIdsByEmail[$email][0], 'match_type' => self::MATCH_EMAIL];
        }

        $name = $this->normalizeName($entry['name'] ?? null);

        if ($name !== '' && isset($personIdsByName[$name])) {
            $candidates = $personIdsByName[$name];

            if (count($candidates) === 1) {
                return ['person_id' => $candidates[0], 'match_type' => self::MATCH_NAME];
            }

            return ['person_id' => null, 'match_type' => self::MATCH_AMBIGUOUS];
        }

        if ($email !== '' && isset($personIdsByEmail[$email])) {
            return ['person_id' => null, 'match_type' => self::MATCH_AMBIGUOUS];
        }

        return ['person_id' => null, 'match_type' => self::MATCH_NONE];
    }

    /**
     * @param  array{row?: int|null, name?: string|null, email?: string|null}  $entry
     * @param  array{person_id: int|null, match_type: string}  $match
     * @param  Collection<int, InstitutionPerson>  $staffByPersonId
     * @param  Collection<int, StaffUnit>  $assignmentsByStaffId
     * @param  Collection<int, JobStaff>  $ranksByStaffId
     * @param  Collection<int, Unit>  $unitsById
     * @return array<string, mixed>
     */
    private function buildResult(
        array $entry,
        array $match,
        Collection $staffByPersonId,
        Collection $assignmentsByStaffId,
        Collection $ranksByStaffId,
        Collection $unitsById
    ): array {
        $base = [
            'row' => $entry['row'] ?? null,
            'source_name' => $this->cleanText($entry['name'] ?? null),
            'source_email' => $this->cleanText($entry['email'] ?? null),
            'match_type' => $match['match_type'],
            'staff_number' => null,
            'file_number' => null,
            'staff_name' => null,
            'rank' => null,
            'department' => null,
            'unit' => null,
            'unit_type' => null,
            'hierarchy' => null,
            'assignment_start' => null,
            'assignment_status' => null,
        ];

        if ($match['person_id'] === null) {
            return $base;
        }

        $staff = $staffByPersonId->get($match['person_id']);

        if ($staff === null) {
            $base['match_type'] = self::MATCH_NOT_STAFF;
            $base['staff_name'] = Person::find($match['person_id'])?->full_name;

            return $base;
        }

        $base['staff_number'] = $staff->staff_number;
        $base['file_number'] = $staff->file_number;
        $base['staff_name'] = $staff->person?->full_name;
        $base['rank'] = $ranksByStaffId->get($staff->id)?->job?->name;

        $assignment = $assignmentsByStaffId->get($staff->id);

        if ($assignment === null) {
            $base['assignment_status'] = 'No unit assignment on record';

            return $base;
        }

        $unit = $unitsById->get($assignment->unit_id);
        $chain = $unit === null ? [] : $this->ancestorChain($unit, $unitsById);

        $base['unit'] = $unit?->name;
        $base['unit_type'] = $unit?->type?->label();
        $base['department'] = $this->departmentFor($chain)?->name;
        $base['hierarchy'] = $chain === []
            ? null
            : collect(array_reverse($chain))->pluck('name')->implode(' > ');
        $base['assignment_start'] = $assignment->start_date?->format('d F, Y');
        $base['assignment_status'] = $assignment->end_date === null
            ? 'Current'
            : 'Ended ' . $assignment->end_date->format('d F, Y');

        return $base;
    }

    /**
     * The unit itself followed by each ancestor, nearest first.
     *
     * @param  Collection<int, Unit>  $unitsById
     * @return array<int, Unit>
     */
    private function ancestorChain(Unit $unit, Collection $unitsById): array
    {
        $chain = [$unit];
        $current = $unit;

        while ($current->unit_id !== null && count($chain) < self::MAX_HIERARCHY_DEPTH) {
            $parent = $unitsById->get($current->unit_id);

            if ($parent === null) {
                break;
            }

            $chain[] = $parent;
            $current = $parent;
        }

        return $chain;
    }

    /**
     * The nearest department in the chain, falling back to the topmost unit for
     * branches (regional and district offices) that sit outside a department.
     *
     * @param  array<int, Unit>  $chain
     */
    private function departmentFor(array $chain): ?Unit
    {
        foreach ($chain as $unit) {
            if ($unit->type === UnitType::DEPARTMENT) {
                return $unit;
            }
        }

        return end($chain) ?: null;
    }

    /**
     * @param  array<int, string>  $emails
     * @return array<string, array<int, int>>
     */
    private function personIdsByEmail(array $emails): array
    {
        if ($emails === []) {
            return [];
        }

        $map = [];

        Contact::query()
            ->where('contact_type', ContactTypeEnum::EMAIL)
            ->get(['person_id', 'contact'])
            ->each(function (Contact $contact) use (&$map): void {
                $email = $this->normalizeEmail($contact->contact);

                if ($email !== '') {
                    $map[$email][] = (int) $contact->person_id;
                }
            });

        User::query()
            ->whereNotNull('person_id')
            ->get(['person_id', 'email'])
            ->each(function (User $user) use (&$map): void {
                $email = $this->normalizeEmail($user->email);

                if ($email !== '') {
                    $map[$email][] = (int) $user->person_id;
                }
            });

        return array_map(
            fn (array $ids): array => array_values(array_unique($ids)),
            array_intersect_key($map, array_flip($emails))
        );
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function personIdsByName(): array
    {
        $map = [];

        Person::query()
            ->get(['id', 'first_name', 'other_names', 'surname'])
            ->each(function (Person $person) use (&$map): void {
                $name = $this->normalizeName(
                    implode(' ', array_filter([$person->first_name, $person->other_names, $person->surname]))
                );

                if ($name !== '') {
                    $map[$name][] = (int) $person->id;
                }
            });

        return array_map(fn (array $ids): array => array_values(array_unique($ids)), $map);
    }

    /**
     * The staff record for each person, preferring an assignment that has not ended.
     *
     * @param  array<int, int>  $personIds
     * @return Collection<int, InstitutionPerson>
     */
    private function staffByPersonId(array $personIds): Collection
    {
        if ($personIds === []) {
            return collect();
        }

        return InstitutionPerson::query()
            ->whereIn('person_id', $personIds)
            ->with('person:id,title,first_name,other_names,surname')
            ->get()
            ->sortBy(fn (InstitutionPerson $staff): string => $this->recencyKey($staff->end_date === null, $staff->hire_date))
            ->unique('person_id')
            ->keyBy('person_id');
    }

    /**
     * The most relevant unit assignment per staff member: the latest one that is
     * still open, otherwise the most recently ended one.
     *
     * @param  array<int, int>  $staffIds
     * @return Collection<int, StaffUnit>
     */
    private function assignmentsByStaffId(array $staffIds): Collection
    {
        if ($staffIds === []) {
            return collect();
        }

        return StaffUnit::query()
            ->whereIn('staff_id', $staffIds)
            ->whereNull('deleted_at')
            ->get()
            ->sortBy(fn (StaffUnit $assignment): string => $this->recencyKey($assignment->end_date === null, $assignment->start_date))
            ->unique('staff_id')
            ->keyBy('staff_id');
    }

    /**
     * The current rank per staff member: the latest one that is still open,
     * otherwise the most recently ended one.
     *
     * @param  array<int, int>  $staffIds
     * @return Collection<int, JobStaff>
     */
    private function ranksByStaffId(array $staffIds): Collection
    {
        if ($staffIds === []) {
            return collect();
        }

        return JobStaff::query()
            ->from('job_staff')
            ->whereIn('staff_id', $staffIds)
            ->whereNull('deleted_at')
            ->with('job:id,name')
            ->get()
            ->sortBy(fn (JobStaff $rank): string => $this->recencyKey($rank->end_date === null, $rank->start_date))
            ->unique('staff_id')
            ->keyBy('staff_id');
    }

    /**
     * Sort key that puts open records ahead of ended ones and, within each group,
     * the most recent start date first.
     */
    private function recencyKey(bool $isOpen, ?CarbonInterface $startDate): string
    {
        $stamp = (int) ($startDate?->format('Ymd') ?? 0);

        return ($isOpen ? '0' : '1') . str_pad((string) (99999999 - $stamp), 8, '0', STR_PAD_LEFT);
    }

    /**
     * @return Collection<int, Unit>
     */
    private function unitsById(): Collection
    {
        return Unit::withTrashed()->get(['id', 'name', 'type', 'unit_id'])->keyBy('id');
    }

    private function normalizeEmail(?string $email): string
    {
        return Str::lower($this->cleanText($email) ?? '');
    }

    /**
     * Reduces a name to a comparable key: lower case, punctuation and honorifics
     * removed, and the remaining words sorted so word order does not matter.
     */
    private function normalizeName(?string $name): string
    {
        $normalized = Str::of($this->cleanText($name) ?? '')
            ->lower()
            ->replaceMatches('/[^a-z ]+/', ' ')
            ->squish()
            ->__toString();

        $words = array_values(array_diff(
            array_filter(explode(' ', $normalized)),
            ['mr', 'mrs', 'ms', 'miss', 'dr', 'prof', 'rev', 'hon', 'madam']
        ));

        sort($words);

        return implode(' ', $words);
    }

    /**
     * Trims the value and collapses the non-breaking spaces that survey exports carry.
     */
    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = Str::of($value)->replace(["\u{00A0}", "\u{200B}"], ' ')->squish()->__toString();

        return $cleaned === '' ? null : $cleaned;
    }
}
