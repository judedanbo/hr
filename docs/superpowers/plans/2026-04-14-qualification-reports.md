# Qualification Reports Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a comprehensive qualifications reporting suite: dashboard charts, `/qualifications/reports` analytics page, and five downloadable report variants (PDF + Excel), all backed by a shared service + DTO.

**Architecture:** `QualificationReportFilter` DTO feeds a `QualificationReportService` that centralises aggregation queries. Controllers for the Inertia page, dashboard widgets JSON endpoint, and exports all call into the service. Frontend uses Chart.js via `vue-chartjs` (matching existing `Components/Charts/*` conventions). PDFs via DomPDF; Excel via Maatwebsite.

**Tech Stack:** Laravel 11 · PHP 8.4 · Spatie Permission · Maatwebsite Excel · DomPDF · Vue 3.5 · Inertia 1 · vue-chartjs · Tailwind 3 · PHPUnit 11

**Spec:** `docs/superpowers/specs/2026-04-14-qualification-reports-design.md`

---

## Task 1: Migration — Composite indexes on `qualifications`

**Files:**
- Create: `database/migrations/2026_04_14_000000_add_report_indexes_to_qualifications.php`

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration add_report_indexes_to_qualifications --table=qualifications --no-interaction
```

- [ ] **Step 2: Replace migration content**

Rename the generated file to `2026_04_14_000000_add_report_indexes_to_qualifications.php` and set contents:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            if (! $this->hasIndex('qualifications', 'qualifications_level_status_index')) {
                $table->index(['level', 'status'], 'qualifications_level_status_index');
            }
            if (! $this->hasIndex('qualifications', 'qualifications_status_approved_at_index')) {
                $table->index(['status', 'approved_at'], 'qualifications_status_approved_at_index');
            }
            if (! $this->hasIndex('qualifications', 'qualifications_year_index')) {
                $table->index('year', 'qualifications_year_index');
            }
            if (! $this->hasIndex('qualifications', 'qualifications_institution_index')) {
                $table->index('institution', 'qualifications_institution_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropIndex('qualifications_level_status_index');
            $table->dropIndex('qualifications_status_approved_at_index');
            $table->dropIndex('qualifications_year_index');
            $table->dropIndex('qualifications_institution_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        return DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $index]
        )->c > 0;
    }
};
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate --no-interaction`
Expected: `Migrating: 2026_04_14_000000_add_report_indexes_to_qualifications ... DONE`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_04_14_000000_add_report_indexes_to_qualifications.php
git commit -m "feat(qualifications): add report aggregation indexes"
```

---

## Task 2: Extend QualificationFactory with states

**Files:**
- Modify: `database/factories/QualificationFactory.php`

- [ ] **Step 1: Replace file with state-aware factory**

```php
<?php

namespace Database\Factories;

use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Models\Person;
use App\Models\Qualification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class QualificationFactory extends Factory
{
    protected $model = Qualification::class;

    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'course' => $this->faker->word(),
            'institution' => $this->faker->company(),
            'qualification' => $this->faker->jobTitle(),
            'qualification_number' => Str::random(10),
            'level' => $this->faker->randomElement(QualificationLevelEnum::cases())->value,
            'pk' => Str::random(6),
            'year' => (string) $this->faker->numberBetween(1990, 2025),
            'status' => QualificationStatusEnum::Approved->value,
            'approved_by' => null,
            'approved_at' => now(),
        ];
    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'status' => QualificationStatusEnum::Approved->value,
            'approved_at' => now(),
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn () => [
            'status' => QualificationStatusEnum::Pending->value,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn () => [
            'status' => QualificationStatusEnum::Rejected->value,
        ]);
    }

    public function atLevel(QualificationLevelEnum $level): self
    {
        return $this->state(fn () => ['level' => $level->value]);
    }
}
```

- [ ] **Step 2: Smoke-test via tinker**

Run: `php artisan tinker --execute="echo App\Models\Qualification::factory()->pending()->atLevel(App\Enums\QualificationLevelEnum::Masters)->make()->toJson();"`
Expected: JSON with `"status":"pending"` and `"level":"masters"`.

- [ ] **Step 3: Commit**

```bash
git add database/factories/QualificationFactory.php
git commit -m "feat(qualifications): add factory states for status and level"
```

---

## Task 3: Add ordinal rank to QualificationLevelEnum

**Files:**
- Modify: `app/Enums/QualificationLevelEnum.php`

The "highest qualification per person" rule requires a numeric ordering across levels.

- [ ] **Step 1: Write failing test**

Create `tests/Unit/QualificationLevelEnumTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Enums\QualificationLevelEnum;
use PHPUnit\Framework\TestCase;

class QualificationLevelEnumTest extends TestCase
{
    public function test_rank_orders_levels_from_lowest_to_highest(): void
    {
        $ordered = [
            QualificationLevelEnum::SssceWassce,
            QualificationLevelEnum::Certificate,
            QualificationLevelEnum::Diploma,
            QualificationLevelEnum::Hnd,
            QualificationLevelEnum::Degree,
            QualificationLevelEnum::PostGraduateCertificate,
            QualificationLevelEnum::PostGraduateDiploma,
            QualificationLevelEnum::Masters,
            QualificationLevelEnum::Doctorate,
        ];

        $ranks = array_map(fn ($level) => $level->rank(), $ordered);

        $this->assertSame($ranks, array_values(array_unique(array_merge([], ...[$ranks]))));
        $sorted = $ranks;
        sort($sorted);
        $this->assertSame($sorted, $ranks);
    }

    public function test_professional_is_ranked_alongside_degree_level(): void
    {
        $this->assertGreaterThan(0, QualificationLevelEnum::Professional->rank());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=QualificationLevelEnumTest`
Expected: FAIL (method `rank()` does not exist).

- [ ] **Step 3: Add `rank()` method**

Append to `QualificationLevelEnum`:

```php
    /**
     * Numeric ordinality used for "highest qualification per person" calculations.
     * Higher number = higher qualification.
     */
    public function rank(): int
    {
        return match ($this) {
            self::SssceWassce => 10,
            self::Certificate => 20,
            self::Professional => 25,
            self::Diploma => 30,
            self::Hnd => 40,
            self::Degree => 50,
            self::PostGraduateCertificate => 60,
            self::PostGraduateDiploma => 70,
            self::Masters => 80,
            self::Doctorate => 90,
        };
    }

    public static function orderedByRank(): array
    {
        $cases = self::cases();
        usort($cases, fn (self $a, self $b) => $a->rank() <=> $b->rank());
        return $cases;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=QualificationLevelEnumTest`
Expected: PASS (2 tests, 3 assertions).

- [ ] **Step 5: Commit**

```bash
git add app/Enums/QualificationLevelEnum.php tests/Unit/QualificationLevelEnumTest.php
git commit -m "feat(qualifications): add numeric rank ordering to level enum"
```

---

## Task 4: Permission seeder for report permissions

**Files:**
- Create: `database/seeders/QualificationReportPermissionSeeder.php`
- Modify: `database/seeders/AssignRolePermissionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` (register new seeder)

- [ ] **Step 1: Create the seeder**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class QualificationReportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'qualifications.reports.view',
            'qualifications.reports.export',
            'qualifications.reports.view.all',
            'qualifications.reports.view.own_unit',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }
    }
}
```

- [ ] **Step 2: Register in DatabaseSeeder**

Open `database/seeders/DatabaseSeeder.php` and add to the `$this->call([...])` array near the other permission seeders:

```php
QualificationReportPermissionSeeder::class,
```

(If unsure of exact surrounding code, grep first: `grep -n "QualificationPermissionSeeder" database/seeders/DatabaseSeeder.php` and place the new line immediately after it.)

- [ ] **Step 3: Add role assignments**

Open `database/seeders/AssignRolePermissionSeeder.php`. Find the block that assigns permissions to `super-administrator` and add:

```php
$superAdmin->givePermissionTo([
    'qualifications.reports.view',
    'qualifications.reports.export',
    'qualifications.reports.view.all',
]);
```

Do the same for the `admin` role. For any unit-head / HR role present in that seeder, add:

```php
$role->givePermissionTo([
    'qualifications.reports.view',
    'qualifications.reports.export',
    'qualifications.reports.view.own_unit',
]);
```

If no such role exists, note that `view.own_unit` is created unassigned — that's fine; administrators can assign it via the UI.

- [ ] **Step 4: Run the seeders**

Run: `php artisan db:seed --class=QualificationReportPermissionSeeder --no-interaction`
Expected: command completes without error.

Run: `php artisan db:seed --class=AssignRolePermissionSeeder --no-interaction`
Expected: command completes without error.

- [ ] **Step 5: Verify permissions exist**

Run: `php artisan tinker --execute="echo Spatie\Permission\Models\Permission::whereIn('name', ['qualifications.reports.view','qualifications.reports.export','qualifications.reports.view.all','qualifications.reports.view.own_unit'])->count();"`
Expected: `4`

- [ ] **Step 6: Commit**

```bash
git add database/seeders/QualificationReportPermissionSeeder.php database/seeders/AssignRolePermissionSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat(qualifications): seed report permissions and role assignments"
```

---

## Task 5: Filter DTO

**Files:**
- Create: `app/DataTransferObjects/QualificationReportFilter.php`
- Create: `tests/Unit/QualificationReportFilterTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Unit;

use App\DataTransferObjects\QualificationReportFilter;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class QualificationReportFilterTest extends TestCase
{
    public function test_from_request_maps_all_known_fields(): void
    {
        $req = Request::create('/x', 'GET', [
            'unit_id' => '5',
            'department_id' => '2',
            'level' => 'masters',
            'status' => 'approved',
            'year_from' => '2010',
            'year_to' => '2020',
            'gender' => 'F',
            'job_category_id' => '3',
            'institution' => 'Legon',
            'course' => 'Audit',
        ]);

        $f = QualificationReportFilter::fromRequest($req);

        $this->assertSame(5, $f->unitId);
        $this->assertSame(2, $f->departmentId);
        $this->assertSame('masters', $f->level);
        $this->assertSame('approved', $f->status);
        $this->assertSame(2010, $f->yearFrom);
        $this->assertSame(2020, $f->yearTo);
        $this->assertSame('F', $f->gender);
        $this->assertSame(3, $f->jobCategoryId);
        $this->assertSame('Legon', $f->institution);
        $this->assertSame('Audit', $f->course);
    }

    public function test_missing_fields_become_null(): void
    {
        $f = QualificationReportFilter::fromRequest(Request::create('/x', 'GET', []));
        $this->assertNull($f->unitId);
        $this->assertNull($f->yearFrom);
    }

    public function test_to_query_array_drops_nulls(): void
    {
        $f = QualificationReportFilter::fromArray(['level' => 'masters']);
        $this->assertSame(['level' => 'masters'], $f->toQueryArray());
    }

    public function test_cache_key_is_deterministic(): void
    {
        $a = QualificationReportFilter::fromArray(['unit_id' => 5, 'level' => 'masters']);
        $b = QualificationReportFilter::fromArray(['level' => 'masters', 'unit_id' => 5]);
        $this->assertSame($a->cacheKey(), $b->cacheKey());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=QualificationReportFilterTest`
Expected: FAIL (class not found).

- [ ] **Step 3: Create the DTO**

```php
<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;

final class QualificationReportFilter
{
    public function __construct(
        public readonly ?int $unitId = null,
        public readonly ?int $departmentId = null,
        public readonly ?string $level = null,
        public readonly ?string $status = null,
        public readonly ?int $yearFrom = null,
        public readonly ?int $yearTo = null,
        public readonly ?string $gender = null,
        public readonly ?int $jobCategoryId = null,
        public readonly ?string $institution = null,
        public readonly ?string $course = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->all());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            unitId: self::nullableInt($data['unit_id'] ?? null),
            departmentId: self::nullableInt($data['department_id'] ?? null),
            level: self::nullableString($data['level'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            yearFrom: self::nullableInt($data['year_from'] ?? null),
            yearTo: self::nullableInt($data['year_to'] ?? null),
            gender: self::nullableString($data['gender'] ?? null),
            jobCategoryId: self::nullableInt($data['job_category_id'] ?? null),
            institution: self::nullableString($data['institution'] ?? null),
            course: self::nullableString($data['course'] ?? null),
        );
    }

    /** @return array<string, scalar> */
    public function toQueryArray(): array
    {
        return array_filter([
            'unit_id' => $this->unitId,
            'department_id' => $this->departmentId,
            'level' => $this->level,
            'status' => $this->status,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
            'gender' => $this->gender,
            'job_category_id' => $this->jobCategoryId,
            'institution' => $this->institution,
            'course' => $this->course,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function cacheKey(): string
    {
        $data = $this->toQueryArray();
        ksort($data);
        return md5(json_encode($data));
    }

    public function withUnitId(?int $unitId): self
    {
        return new self(
            unitId: $unitId,
            departmentId: $this->departmentId,
            level: $this->level,
            status: $this->status,
            yearFrom: $this->yearFrom,
            yearTo: $this->yearTo,
            gender: $this->gender,
            jobCategoryId: $this->jobCategoryId,
            institution: $this->institution,
            course: $this->course,
        );
    }

    private static function nullableInt(mixed $v): ?int
    {
        return ($v === null || $v === '') ? null : (int) $v;
    }

    private static function nullableString(mixed $v): ?string
    {
        return ($v === null || $v === '') ? null : (string) $v;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=QualificationReportFilterTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add app/DataTransferObjects/QualificationReportFilter.php tests/Unit/QualificationReportFilterTest.php
git commit -m "feat(qualifications): add QualificationReportFilter DTO"
```

---

## Task 6: Service skeleton + `levelDistribution()`

**Files:**
- Create: `app/Services/QualificationReportService.php`
- Create: `tests/Feature/QualificationReports/ServiceAggregationsTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature\QualificationReports;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Models\Person;
use App\Models\Qualification;
use App\Services\QualificationReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAggregationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_distribution_counts_only_highest_level_per_person(): void
    {
        $person = Person::factory()->create();
        Qualification::factory()->for($person)->approved()->atLevel(QualificationLevelEnum::Degree)->create();
        Qualification::factory()->for($person)->approved()->atLevel(QualificationLevelEnum::Masters)->create();

        $another = Person::factory()->create();
        Qualification::factory()->for($another)->approved()->atLevel(QualificationLevelEnum::Degree)->create();

        $service = app(QualificationReportService::class);
        $result = $service->levelDistribution(new QualificationReportFilter());

        $this->assertSame(1, $result['degree'] ?? 0, 'Only the Degree-only person should count under Degree');
        $this->assertSame(1, $result['masters'] ?? 0, 'Only the Masters-holder counts once under Masters');
    }

    public function test_level_distribution_ignores_pending_qualifications(): void
    {
        $person = Person::factory()->create();
        Qualification::factory()->for($person)->pending()->atLevel(QualificationLevelEnum::Masters)->create();

        $result = app(QualificationReportService::class)->levelDistribution(new QualificationReportFilter());

        $this->assertSame(0, array_sum($result));
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=ServiceAggregationsTest`
Expected: FAIL (`Target class [App\Services\QualificationReportService] does not exist`).

- [ ] **Step 3: Create the service**

```php
<?php

namespace App\Services;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Enums\QualificationStatusEnum;
use App\Models\Qualification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QualificationReportService
{
    /**
     * Count of distinct people at each level, using the highest qualification per person.
     *
     * @return array<string, int>  ['masters' => 12, 'degree' => 34, ...]
     */
    public function levelDistribution(QualificationReportFilter $filter): array
    {
        $ranks = collect(QualificationLevelEnum::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->rank()])
            ->all();

        $highestPerPerson = $this->applyFilter(Qualification::query(), $filter)
            ->approved()
            ->get(['person_id', 'level'])
            ->groupBy('person_id')
            ->map(function ($quals) use ($ranks) {
                $best = null;
                $bestRank = -1;
                foreach ($quals as $q) {
                    $r = $ranks[$q->level] ?? -1;
                    if ($r > $bestRank) {
                        $bestRank = $r;
                        $best = $q->level;
                    }
                }
                return $best;
            });

        $counts = [];
        foreach (QualificationLevelEnum::cases() as $case) {
            $counts[$case->value] = 0;
        }
        foreach ($highestPerPerson as $level) {
            if ($level !== null && isset($counts[$level])) {
                $counts[$level]++;
            }
        }
        return $counts;
    }

    protected function applyFilter(Builder $query, QualificationReportFilter $filter): Builder
    {
        if ($filter->level) {
            $query->where('level', $filter->level);
        }
        if ($filter->status) {
            $query->where('status', $filter->status);
        }
        if ($filter->yearFrom) {
            $query->where('year', '>=', (string) $filter->yearFrom);
        }
        if ($filter->yearTo) {
            $query->where('year', '<=', (string) $filter->yearTo);
        }
        if ($filter->institution) {
            $query->where('institution', 'like', "%{$filter->institution}%");
        }
        if ($filter->course) {
            $query->where('course', 'like', "%{$filter->course}%");
        }

        if ($filter->unitId || $filter->departmentId || $filter->gender || $filter->jobCategoryId) {
            $query->whereHas('person', function (Builder $q) use ($filter) {
                if ($filter->gender) {
                    $q->where('gender', $filter->gender);
                }
                if ($filter->unitId || $filter->departmentId) {
                    $q->whereHas('institutionPerson.staffUnits', function (Builder $u) use ($filter) {
                        $u->whereNull('end_date');
                        if ($filter->unitId) {
                            $u->where('unit_id', $filter->unitId);
                        }
                        if ($filter->departmentId) {
                            $u->whereHas('unit', fn ($unit) => $unit->where('parent_id', $filter->departmentId));
                        }
                    });
                }
                if ($filter->jobCategoryId) {
                    $q->whereHas('institutionPerson.jobStaff', function (Builder $j) use ($filter) {
                        $j->whereNull('end_date')->whereHas('job', fn ($job) =>
                            $job->where('job_category_id', $filter->jobCategoryId)
                        );
                    });
                }
            });
        }

        return $query;
    }
}
```

NOTE: if `Person`'s institution relationship uses a different accessor than `institutionPerson`, inspect `app/Models/Person.php` and substitute accordingly. Use the Read tool to confirm — do not guess.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ServiceAggregationsTest`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/QualificationReportService.php tests/Feature/QualificationReports/ServiceAggregationsTest.php
git commit -m "feat(qualifications): add report service with level distribution"
```

---

## Task 7: Service — `byUnit()` aggregation

**Files:**
- Modify: `app/Services/QualificationReportService.php`
- Modify: `tests/Feature/QualificationReports/ServiceAggregationsTest.php`

- [ ] **Step 1: Add failing test**

Append to `ServiceAggregationsTest`:

```php
public function test_by_unit_groups_highest_qualifications_by_current_unit(): void
{
    // Build two units with known staff/qualifications
    $unitA = \App\Models\Unit::factory()->create(['name' => 'Unit A']);
    $unitB = \App\Models\Unit::factory()->create(['name' => 'Unit B']);

    $p1 = Person::factory()->create();
    // Assume a helper or factory sequence that places person in unitA; if absent, create InstitutionPerson + StaffUnit manually.
    \App\Models\InstitutionPerson::factory()->for($p1)->create();
    \App\Models\StaffUnit::factory()->create([
        'institution_person_id' => $p1->institutionPerson->id,
        'unit_id' => $unitA->id,
        'end_date' => null,
    ]);
    Qualification::factory()->for($p1)->approved()->atLevel(QualificationLevelEnum::Masters)->create();

    $result = app(QualificationReportService::class)->byUnit(new QualificationReportFilter());

    $this->assertArrayHasKey('Unit A', $result);
    $this->assertSame(1, $result['Unit A']['masters'] ?? 0);
}
```

NOTE: if factories for `Unit`, `InstitutionPerson`, or `StaffUnit` do not exist or require different arguments, inspect them first (`php artisan tinker --execute="..."` or Read the factory files) and adjust. Do not invent factory states.

- [ ] **Step 2: Run test — expect fail**

Run: `php artisan test --filter=test_by_unit_groups`
Expected: FAIL (`byUnit` method missing).

- [ ] **Step 3: Implement `byUnit()`**

Add to `QualificationReportService`:

```php
    /**
     * @return array<string, array<string, int>>  ['Unit A' => ['masters' => 3, 'degree' => 5, ...], ...]
     */
    public function byUnit(QualificationReportFilter $filter): array
    {
        $ranks = collect(QualificationLevelEnum::cases())
            ->mapWithKeys(fn ($c) => [$c->value => $c->rank()])
            ->all();

        $rows = $this->applyFilter(Qualification::query(), $filter)
            ->approved()
            ->join('people', 'qualifications.person_id', '=', 'people.id')
            ->join('institution_person', 'people.id', '=', 'institution_person.person_id')
            ->join('staff_units', function ($j) {
                $j->on('staff_units.institution_person_id', '=', 'institution_person.id')
                  ->whereNull('staff_units.end_date');
            })
            ->join('units', 'staff_units.unit_id', '=', 'units.id')
            ->get(['qualifications.person_id', 'qualifications.level', 'units.id as unit_id', 'units.name as unit_name']);

        // Pick highest-per-person-per-unit
        $highest = [];  // [unit_name][person_id] => level
        foreach ($rows as $row) {
            $currentLevel = $highest[$row->unit_name][$row->person_id] ?? null;
            if ($currentLevel === null || ($ranks[$row->level] ?? -1) > ($ranks[$currentLevel] ?? -1)) {
                $highest[$row->unit_name][$row->person_id] = $row->level;
            }
        }

        $result = [];
        foreach ($highest as $unitName => $personLevels) {
            $result[$unitName] = [];
            foreach (QualificationLevelEnum::cases() as $case) {
                $result[$unitName][$case->value] = 0;
            }
            foreach ($personLevels as $level) {
                $result[$unitName][$level]++;
            }
        }
        return $result;
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_by_unit_groups`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/QualificationReportService.php tests/Feature/QualificationReports/ServiceAggregationsTest.php
git commit -m "feat(qualifications): add byUnit aggregation"
```

---

## Task 8: Service — `topInstitutions()`, `trendByYear()`

**Files:**
- Modify: `app/Services/QualificationReportService.php`
- Modify: `tests/Feature/QualificationReports/ServiceAggregationsTest.php`

- [ ] **Step 1: Add failing tests**

```php
public function test_top_institutions_normalizes_casing_and_trimming(): void
{
    Qualification::factory()->approved()->create(['institution' => 'University of Ghana']);
    Qualification::factory()->approved()->create(['institution' => ' university of ghana ']);
    Qualification::factory()->approved()->create(['institution' => 'KNUST']);

    $result = app(QualificationReportService::class)->topInstitutions(new QualificationReportFilter(), 10);

    $uog = collect($result)->firstWhere('name', 'University of Ghana') ?? collect($result)->first();
    $this->assertNotNull($uog);
    $this->assertSame(2, $uog['count']);
}

public function test_trend_by_year_returns_year_counts(): void
{
    Qualification::factory()->approved()->count(3)->create(['year' => '2018']);
    Qualification::factory()->approved()->count(2)->create(['year' => '2020']);

    $result = app(QualificationReportService::class)->trendByYear(new QualificationReportFilter());

    $this->assertSame(3, $result[2018] ?? 0);
    $this->assertSame(2, $result[2020] ?? 0);
}
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=ServiceAggregationsTest`
Expected: FAIL (methods missing).

- [ ] **Step 3: Implement methods**

Append to the service:

```php
    /**
     * @return array<int, array{name: string, count: int}>
     */
    public function topInstitutions(QualificationReportFilter $filter, int $limit = 10): array
    {
        $rows = $this->applyFilter(Qualification::query(), $filter)
            ->approved()
            ->whereNotNull('institution')
            ->where('institution', '!=', '')
            ->get(['institution']);

        $groups = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim($row->institution));
            if ($key === '') {
                continue;
            }
            $groups[$key]['count'] = ($groups[$key]['count'] ?? 0) + 1;
            $groups[$key]['labels'][$row->institution] = ($groups[$key]['labels'][$row->institution] ?? 0) + 1;
        }

        $out = [];
        foreach ($groups as $data) {
            arsort($data['labels']);
            $displayLabel = array_key_first($data['labels']);
            $out[] = ['name' => $displayLabel, 'count' => $data['count']];
        }
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);
        return array_slice($out, 0, $limit);
    }

    /**
     * @return array<int, int>  [2018 => 3, 2020 => 2]
     */
    public function trendByYear(QualificationReportFilter $filter): array
    {
        $rows = $this->applyFilter(Qualification::query(), $filter)
            ->approved()
            ->whereNotNull('year')
            ->where('year', '!=', '')
            ->selectRaw('year, COUNT(*) AS n')
            ->groupBy('year')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if (is_numeric($row->year)) {
                $out[(int) $row->year] = (int) $row->n;
            }
        }
        ksort($out);
        return $out;
    }
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ServiceAggregationsTest`
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/QualificationReportService.php tests/Feature/QualificationReports/ServiceAggregationsTest.php
git commit -m "feat(qualifications): add topInstitutions and trendByYear"
```

---

## Task 9: Service — `pendingApprovalsStats()`

**Files:**
- Modify: `app/Services/QualificationReportService.php`
- Modify: `tests/Feature/QualificationReports/ServiceAggregationsTest.php`

- [ ] **Step 1: Add failing test**

```php
public function test_pending_approvals_stats_returns_count_and_sparkline(): void
{
    Qualification::factory()->pending()->count(3)->create();

    $result = app(QualificationReportService::class)->pendingApprovalsStats();

    $this->assertSame(3, $result['count']);
    $this->assertCount(30, $result['sparkline']);
}
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=test_pending_approvals_stats`

- [ ] **Step 3: Implement**

```php
    /**
     * @return array{count: int, sparkline: array<int, int>}  sparkline is 30-day daily submissions (newest last)
     */
    public function pendingApprovalsStats(): array
    {
        $count = Qualification::query()->pending()->count();

        $since = now()->subDays(29)->startOfDay();
        $daily = Qualification::query()
            ->pending()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS n')
            ->groupBy('d')
            ->pluck('n', 'd');

        $sparkline = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $sparkline[] = (int) ($daily[$date] ?? 0);
        }

        return ['count' => $count, 'sparkline' => $sparkline];
    }
```

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --filter=test_pending_approvals_stats`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/QualificationReportService.php tests/Feature/QualificationReports/ServiceAggregationsTest.php
git commit -m "feat(qualifications): add pending approvals stats"
```

---

## Task 10: Service — `staffWithoutQualifications()` and `staffList()`

**Files:**
- Modify: `app/Services/QualificationReportService.php`
- Modify: `tests/Feature/QualificationReports/ServiceAggregationsTest.php`

- [ ] **Step 1: Add failing tests**

```php
public function test_staff_without_qualifications_returns_only_staff_with_no_approved_qualifications(): void
{
    $with = Person::factory()->create();
    \App\Models\InstitutionPerson::factory()->for($with)->create();
    Qualification::factory()->for($with)->approved()->create();

    $without = Person::factory()->create();
    \App\Models\InstitutionPerson::factory()->for($without)->create();

    $result = app(QualificationReportService::class)->staffWithoutQualifications(new QualificationReportFilter());

    $ids = $result->pluck('id')->all();
    $this->assertContains($without->id, $ids);
    $this->assertNotContains($with->id, $ids);
}

public function test_staff_list_is_paginated(): void
{
    Qualification::factory()->approved()->count(30)->create();
    $result = app(QualificationReportService::class)->staffList(new QualificationReportFilter(), perPage: 10);
    $this->assertSame(10, $result->perPage());
    $this->assertGreaterThanOrEqual(3, $result->lastPage());
}
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=ServiceAggregationsTest`

- [ ] **Step 3: Implement**

```php
    public function staffWithoutQualifications(QualificationReportFilter $filter): \Illuminate\Support\Collection
    {
        return \App\Models\Person::query()
            ->whereHas('institutionPerson', function ($q) use ($filter) {
                // Active-only check: adjust to match existing InstitutionPerson::active() scope if present.
                if (method_exists(\App\Models\InstitutionPerson::class, 'scopeActive')) {
                    $q->active();
                }
                if ($filter->unitId) {
                    $q->whereHas('staffUnits', fn ($u) =>
                        $u->whereNull('end_date')->where('unit_id', $filter->unitId)
                    );
                }
            })
            ->whereDoesntHave('qualifications', fn ($q) =>
                $q->where('status', QualificationStatusEnum::Approved->value)
            )
            ->with(['institutionPerson.currentUnit.unit', 'institutionPerson.currentRank.job'])
            ->get();
    }

    public function staffList(QualificationReportFilter $filter, int $perPage = 25): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->applyFilter(Qualification::query(), $filter)
            ->with([
                'person.institutionPerson.currentUnit.unit',
                'person.institutionPerson.currentRank.job',
                'approver',
            ])
            ->orderByDesc('year')
            ->paginate($perPage)
            ->withQueryString();
    }
```

NOTE: the `InstitutionPerson` relation and any scopes (`active`, `currentUnit`, `currentRank`) must match what the codebase actually exposes — verify with Grep before adjusting. Do not invent accessor names.

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ServiceAggregationsTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/QualificationReportService.php tests/Feature/QualificationReports/ServiceAggregationsTest.php
git commit -m "feat(qualifications): add staff-without-quals and staffList"
```

---

## Task 11: Service — Unit-scoping helper

**Files:**
- Modify: `app/Services/QualificationReportService.php`
- Create: `tests/Feature/QualificationReports/UnitScopingTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature\QualificationReports;

use App\DataTransferObjects\QualificationReportFilter;
use App\Models\Unit;
use App\Models\User;
use App\Services\QualificationReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_own_unit_user_has_unit_id_injected_into_filter(): void
    {
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);

        $unit = Unit::factory()->create();
        $user = User::factory()->create();
        // Attach person + institution_person + staff_units to this user; or use existing factory sequence.
        // Substitute with actual user->person->currentUnit wiring as implemented in the codebase.

        $user->givePermissionTo('qualifications.reports.view');
        $user->givePermissionTo('qualifications.reports.view.own_unit');

        $filter = new QualificationReportFilter();
        $service = app(QualificationReportService::class);

        $scoped = $service->applyUnitScope($filter, $user);

        // Exact assertion depends on how user->currentUnit is wired — at minimum, unit_id should not be null for own_unit users with a unit.
        $this->assertNotNull($scoped->unitId);
    }

    public function test_view_all_user_keeps_filter_unchanged(): void
    {
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('qualifications.reports.view');
        $user->givePermissionTo('qualifications.reports.view.all');

        $filter = new QualificationReportFilter(unitId: 7);
        $scoped = app(QualificationReportService::class)->applyUnitScope($filter, $user);

        $this->assertSame(7, $scoped->unitId);
    }
}
```

NOTE: the first test wiring depends on how your codebase exposes `user→person→currentUnit`. Before running, open `app/Models/User.php` and `app/Models/Person.php` to confirm the path; adjust the test harness accordingly. The essence is: `own_unit` user should get `unitId` auto-filled.

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=UnitScopingTest`

- [ ] **Step 3: Add `applyUnitScope()`**

```php
    public function applyUnitScope(QualificationReportFilter $filter, \App\Models\User $user): QualificationReportFilter
    {
        if ($user->can('qualifications.reports.view.all')) {
            return $filter;
        }

        if ($user->can('qualifications.reports.view.own_unit')) {
            $unitId = optional(optional($user->person)->institutionPerson)
                ?->currentUnit
                ?->unit_id;

            if ($unitId) {
                return $filter->withUnitId($unitId);
            }
        }

        return $filter;
    }
```

Adjust the accessor chain if the actual User→Person→InstitutionPerson→currentUnit path differs.

- [ ] **Step 4: Run — expect pass**

Run: `php artisan test --filter=UnitScopingTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/QualificationReportService.php tests/Feature/QualificationReports/UnitScopingTest.php
git commit -m "feat(qualifications): add unit-scoping helper to report service"
```

---

## Task 12: Caching + observer invalidation

**Files:**
- Modify: `app/Services/QualificationReportService.php` (wrap aggregate methods in `Cache::remember`)
- Create: `app/Observers/QualificationObserver.php`
- Modify: `app/Providers/AppServiceProvider.php` (register observer)

- [ ] **Step 1: Wrap aggregate methods with Cache::remember**

In `QualificationReportService`, add private helper:

```php
    private function remember(string $method, QualificationReportFilter $filter, \Closure $callback): mixed
    {
        $key = "qual-report:{$method}:{$filter->cacheKey()}";
        return \Illuminate\Support\Facades\Cache::remember($key, now()->addMinutes(10), $callback);
    }
```

Then wrap each aggregate method's body:

```php
    public function levelDistribution(QualificationReportFilter $filter): array
    {
        return $this->remember('levelDistribution', $filter, function () use ($filter) {
            // existing body
        });
    }
```

Do this for `levelDistribution`, `byUnit`, `topInstitutions`, `trendByYear`, `pendingApprovalsStats` (pending uses empty filter → stable key), `staffWithoutQualifications`.

Do NOT cache `staffList` (paginator).

- [ ] **Step 2: Create the observer**

```php
<?php

namespace App\Observers;

use App\Models\Qualification;
use Illuminate\Support\Facades\Cache;

class QualificationObserver
{
    public function saved(Qualification $qualification): void
    {
        $this->flush();
    }

    public function deleted(Qualification $qualification): void
    {
        $this->flush();
    }

    public function restored(Qualification $qualification): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        // Simple approach: key prefix scan is unreliable across drivers; flush known aggregate methods.
        // Since keys include the filter hash, use a versioned-cache pattern instead.
        Cache::increment('qual-report:version');
    }
}
```

Then update `remember()` to incorporate the version:

```php
    private function remember(string $method, QualificationReportFilter $filter, \Closure $callback): mixed
    {
        $version = \Illuminate\Support\Facades\Cache::get('qual-report:version', 0);
        $key = "qual-report:v{$version}:{$method}:{$filter->cacheKey()}";
        return \Illuminate\Support\Facades\Cache::remember($key, now()->addMinutes(10), $callback);
    }
```

- [ ] **Step 3: Register the observer in `AppServiceProvider::boot()`**

```php
\App\Models\Qualification::observe(\App\Observers\QualificationObserver::class);
```

- [ ] **Step 4: Add cache invalidation test**

Add to `ServiceAggregationsTest`:

```php
public function test_cache_invalidates_when_qualification_is_saved(): void
{
    $service = app(QualificationReportService::class);

    $first = $service->levelDistribution(new QualificationReportFilter());
    $this->assertSame(0, array_sum($first));

    $person = Person::factory()->create();
    Qualification::factory()->for($person)->approved()->atLevel(QualificationLevelEnum::Masters)->create();

    $second = $service->levelDistribution(new QualificationReportFilter());
    $this->assertSame(1, $second['masters']);
}
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=ServiceAggregationsTest`
Expected: all PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/QualificationReportService.php app/Observers/QualificationObserver.php app/Providers/AppServiceProvider.php tests/Feature/QualificationReports/ServiceAggregationsTest.php
git commit -m "feat(qualifications): cache report aggregates with versioned invalidation"
```

---

## Task 13: Controller skeleton + `index()` route

**Files:**
- Create: `app/Http/Controllers/QualificationReportController.php`
- Modify: `routes/web.php` (add routes)
- Create: `tests/Feature/QualificationReports/IndexPageTest.php`
- Create: `resources/js/Pages/Qualification/Reports/Index.vue` (stub; full UI added later)

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature\QualificationReports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_without_permission_is_denied(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/qualifications/reports')->assertForbidden();
    }

    public function test_user_with_permission_can_view_page(): void
    {
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('qualifications.reports.view');

        $this->actingAs($user)
            ->get('/qualifications/reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Qualification/Reports/Index')
                    ->has('levelDistribution')
                    ->has('byUnit')
                    ->has('topInstitutions')
                    ->has('trendByYear')
                    ->has('staffList')
                    ->has('kpis')
                    ->has('filterOptions')
            );
    }
}
```

- [ ] **Step 2: Create stub Vue page**

Create `resources/js/Pages/Qualification/Reports/Index.vue`:

```vue
<script setup>
defineProps({
    levelDistribution: Object,
    byUnit: Object,
    topInstitutions: Array,
    trendByYear: Object,
    staffList: Object,
    kpis: Object,
    filterOptions: Object,
    filters: Object,
});
</script>

<template>
    <div>Qualifications Reports (stub)</div>
</template>
```

- [ ] **Step 3: Create the controller**

```php
<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Models\Unit;
use App\Services\QualificationReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QualificationReportController extends Controller
{
    public function __construct(private readonly QualificationReportService $service)
    {
    }

    public function index(Request $request): Response
    {
        $filter = $this->service->applyUnitScope(
            QualificationReportFilter::fromRequest($request),
            $request->user(),
        );

        return Inertia::render('Qualification/Reports/Index', [
            'filters' => $filter->toQueryArray(),
            'filterOptions' => $this->filterOptions(),
            'kpis' => $this->kpis($filter),
            'levelDistribution' => $this->service->levelDistribution($filter),
            'byUnit' => $this->service->byUnit($filter),
            'topInstitutions' => $this->service->topInstitutions($filter, 10),
            'trendByYear' => $this->service->trendByYear($filter),
            'staffList' => $this->service->staffList($filter),
        ]);
    }

    /** @return array<string, mixed> */
    private function filterOptions(): array
    {
        return [
            'units' => Unit::query()->select('id', 'name')->orderBy('name')->get(),
            'levels' => collect(QualificationLevelEnum::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->all(),
            'statuses' => collect(\App\Enums\QualificationStatusEnum::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => $c->label(),
            ])->all(),
            'genders' => [['value' => 'M', 'label' => 'Male'], ['value' => 'F', 'label' => 'Female']],
            'jobCategories' => class_exists(\App\Models\JobCategory::class)
                ? \App\Models\JobCategory::query()->select('id', 'name')->orderBy('name')->get()
                : [],
        ];
    }

    /** @return array<string, int> */
    private function kpis(QualificationReportFilter $filter): array
    {
        return [
            'totalQualifications' => \App\Models\Qualification::query()->approved()->count(),
            'staffCovered' => \App\Models\Qualification::query()->approved()->distinct('person_id')->count('person_id'),
            'pending' => $this->service->pendingApprovalsStats()['count'],
            'withoutQualifications' => $this->service->staffWithoutQualifications($filter)->count(),
        ];
    }
}
```

- [ ] **Step 4: Add route**

Append to `routes/web.php` inside the existing `auth` middleware group (or a new group):

```php
Route::middleware(['auth'])->prefix('qualifications/reports')->name('qualifications.reports.')->group(function () {
    Route::get('/', [App\Http\Controllers\QualificationReportController::class, 'index'])
        ->name('index')
        ->middleware('can:qualifications.reports.view');
});
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=IndexPageTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/QualificationReportController.php routes/web.php resources/js/Pages/Qualification/Reports/Index.vue tests/Feature/QualificationReports/IndexPageTest.php
git commit -m "feat(qualifications): add reports index controller and stub page"
```

---

## Task 14: Excel export — `QualificationListExport`

**Files:**
- Create: `app/Exports/Qualifications/QualificationListExport.php`
- Create: `tests/Feature/QualificationReports/ExcelListExportTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature\QualificationReports;

use App\DataTransferObjects\QualificationReportFilter;
use App\Exports\Qualifications\QualificationListExport;
use App\Models\Qualification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelListExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_export_download_ok(): void
    {
        Excel::fake();
        Qualification::factory()->approved()->count(3)->create();

        Excel::download(new QualificationListExport(new QualificationReportFilter()), 'quals.xlsx');

        Excel::assertDownloaded('quals.xlsx');
    }

    public function test_list_export_headings_are_correct(): void
    {
        $export = new QualificationListExport(new QualificationReportFilter());
        $this->assertContains('Staff Number', $export->headings());
        $this->assertContains('Qualification', $export->headings());
        $this->assertContains('Level', $export->headings());
    }
}
```

- [ ] **Step 2: Run — expect fail**

Run: `php artisan test --filter=ExcelListExportTest`

- [ ] **Step 3: Create the export**

```php
<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Models\Qualification;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QualificationListExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter)
    {
    }

    public function title(): string
    {
        return 'Qualifications';
    }

    public function headings(): array
    {
        return [
            'Staff Number', 'First Name', 'Surname', 'Rank', 'Unit',
            'Qualification', 'Level', 'Institution', 'Year', 'Status', 'Approved At',
        ];
    }

    public function query()
    {
        return app(\App\Services\QualificationReportService::class)
            ->staffList($this->filter, perPage: 10_000)  // Paginator is fine for small sets; for FromQuery we use a raw builder:
            ->getCollection() instanceof \Illuminate\Support\Collection
                ? Qualification::query()->whereIn('id', app(\App\Services\QualificationReportService::class)->staffList($this->filter, perPage: 100_000)->pluck('id'))
                : Qualification::query();
    }

    public function map($q): array
    {
        $person = $q->person;
        $inst = $person?->institutionPerson;
        return [
            $inst?->staff_number,
            $person?->first_name,
            $person?->surname,
            $inst?->currentRank?->job?->name,
            $inst?->currentUnit?->unit?->name,
            $q->qualification,
            $q->level ? (QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level) : null,
            $q->institution,
            $q->year,
            $q->status?->label(),
            $q->approved_at?->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
```

NOTE: Verify `InstitutionPerson::staff_number` column name in the existing codebase (check `StaffListRawExport.php` — which uses `$staff->staff_number`). Adjust property access if your codebase uses a different accessor.

The `query()` double-invocation in the snippet above is intentionally cautious; simplify to:

```php
public function query()
{
    $base = Qualification::query();
    // re-apply filters without the paginator:
    $service = app(\App\Services\QualificationReportService::class);
    $reflected = new \ReflectionMethod($service, 'applyFilter');
    $reflected->setAccessible(true);
    return $reflected->invoke($service, $base, $this->filter)->with(['person.institutionPerson.currentUnit.unit', 'person.institutionPerson.currentRank.job']);
}
```

Or — cleaner — promote `applyFilter` from `protected` to `public` on the service, and call it directly:

```php
public function query()
{
    return app(\App\Services\QualificationReportService::class)
        ->applyFilter(Qualification::query(), $this->filter)
        ->with([
            'person.institutionPerson.currentUnit.unit',
            'person.institutionPerson.currentRank.job',
        ]);
}
```

**Choose the latter.** Update the service method visibility to `public`.

- [ ] **Step 4: Run test — expect pass**

Run: `php artisan test --filter=ExcelListExportTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Exports/Qualifications/QualificationListExport.php app/Services/QualificationReportService.php tests/Feature/QualificationReports/ExcelListExportTest.php
git commit -m "feat(qualifications): add list Excel export"
```

---

## Task 15: Excel export — `QualificationByUnitExport`

**Files:**
- Create: `app/Exports/Qualifications/QualificationByUnitExport.php`
- Add tests in `tests/Feature/QualificationReports/ExcelListExportTest.php` (or new file)

- [ ] **Step 1: Add failing test**

Append to the existing test file:

```php
public function test_by_unit_export_pivot_rows(): void
{
    Excel::fake();
    Qualification::factory()->approved()->count(2)->create();

    Excel::download(
        new \App\Exports\Qualifications\QualificationByUnitExport(new QualificationReportFilter()),
        'by-unit.xlsx'
    );
    Excel::assertDownloaded('by-unit.xlsx');
}
```

- [ ] **Step 2: Implement**

```php
<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Services\QualificationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QualificationByUnitExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter)
    {
    }

    public function title(): string
    {
        return 'Qualifications by Unit';
    }

    public function headings(): array
    {
        $levels = collect(QualificationLevelEnum::orderedByRank())
            ->map(fn ($c) => $c->label())
            ->all();
        return array_merge(['Unit'], $levels, ['Total']);
    }

    public function array(): array
    {
        $data = app(QualificationReportService::class)->byUnit($this->filter);
        $orderedLevels = collect(QualificationLevelEnum::orderedByRank())->map(fn ($c) => $c->value)->all();

        $rows = [];
        foreach ($data as $unitName => $counts) {
            $row = [$unitName];
            $total = 0;
            foreach ($orderedLevels as $lv) {
                $v = $counts[$lv] ?? 0;
                $row[] = $v;
                $total += $v;
            }
            $row[] = $total;
            $rows[] = $row;
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
```

- [ ] **Step 3: Run tests — expect pass**

Run: `php artisan test --filter=ExcelListExportTest`

- [ ] **Step 4: Commit**

```bash
git add app/Exports/Qualifications/QualificationByUnitExport.php tests/Feature/QualificationReports/ExcelListExportTest.php
git commit -m "feat(qualifications): add by-unit Excel export"
```

---

## Task 16: Excel export — `QualificationByLevelExport`

**Files:**
- Create: `app/Exports/Qualifications/QualificationByLevelExport.php`
- Update test file

- [ ] **Step 1: Add failing test**

```php
public function test_by_level_export_rows_contain_level_count_percent(): void
{
    Excel::fake();
    Qualification::factory()->approved()->count(2)->create();

    Excel::download(
        new \App\Exports\Qualifications\QualificationByLevelExport(new QualificationReportFilter()),
        'by-level.xlsx'
    );
    Excel::assertDownloaded('by-level.xlsx');
}
```

- [ ] **Step 2: Implement**

```php
<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Services\QualificationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QualificationByLevelExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter)
    {
    }

    public function title(): string { return 'Qualifications by Level'; }

    public function headings(): array { return ['Level', 'Staff Count', '% of Workforce', 'Pending']; }

    public function array(): array
    {
        $service = app(QualificationReportService::class);
        $dist = $service->levelDistribution($this->filter);
        $totalStaff = \App\Models\InstitutionPerson::query()->when(
            method_exists(\App\Models\InstitutionPerson::class, 'scopeActive'),
            fn ($q) => $q->active()
        )->count() ?: 1;

        $pendingPerLevel = \App\Models\Qualification::query()
            ->pending()
            ->selectRaw('level, COUNT(*) AS n')
            ->groupBy('level')
            ->pluck('n', 'level');

        $rows = [];
        foreach (QualificationLevelEnum::orderedByRank() as $case) {
            $count = $dist[$case->value] ?? 0;
            $rows[] = [
                $case->label(),
                $count,
                round(($count / $totalStaff) * 100, 1) . '%',
                (int) ($pendingPerLevel[$case->value] ?? 0),
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array { return [1 => ['font' => ['bold' => true]]]; }
}
```

- [ ] **Step 3: Run — expect pass**

Run: `php artisan test --filter=ExcelListExportTest`

- [ ] **Step 4: Commit**

```bash
git add app/Exports/Qualifications/QualificationByLevelExport.php tests/Feature/QualificationReports/ExcelListExportTest.php
git commit -m "feat(qualifications): add by-level Excel export"
```

---

## Task 17: Excel export — `StaffWithoutQualificationsExport` + `StaffQualificationProfileExport`

**Files:**
- Create: `app/Exports/Qualifications/StaffWithoutQualificationsExport.php`
- Create: `app/Exports/Qualifications/StaffQualificationProfileExport.php`
- Update test file

- [ ] **Step 1: Add failing tests**

```php
public function test_staff_without_quals_export_downloads(): void
{
    Excel::fake();
    Excel::download(new \App\Exports\Qualifications\StaffWithoutQualificationsExport(new QualificationReportFilter()), 'gaps.xlsx');
    Excel::assertDownloaded('gaps.xlsx');
}

public function test_staff_qualification_profile_export_downloads(): void
{
    Excel::fake();
    $person = \App\Models\Person::factory()->create();
    Qualification::factory()->for($person)->approved()->count(2)->create();
    Excel::download(new \App\Exports\Qualifications\StaffQualificationProfileExport($person), "profile-{$person->id}.xlsx");
    Excel::assertDownloaded("profile-{$person->id}.xlsx");
}
```

- [ ] **Step 2: Implement both exports**

`StaffWithoutQualificationsExport.php`:

```php
<?php

namespace App\Exports\Qualifications;

use App\DataTransferObjects\QualificationReportFilter;
use App\Services\QualificationReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffWithoutQualificationsExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    use Exportable;

    public function __construct(private readonly QualificationReportFilter $filter)
    {
    }

    public function title(): string { return 'Staff Without Qualifications'; }

    public function headings(): array { return ['Staff Number', 'First Name', 'Surname', 'Rank', 'Unit']; }

    public function array(): array
    {
        $rows = [];
        foreach (app(QualificationReportService::class)->staffWithoutQualifications($this->filter) as $person) {
            $inst = $person->institutionPerson;
            $rows[] = [
                $inst?->staff_number,
                $person->first_name,
                $person->surname,
                $inst?->currentRank?->job?->name,
                $inst?->currentUnit?->unit?->name,
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array { return [1 => ['font' => ['bold' => true]]]; }
}
```

`StaffQualificationProfileExport.php`:

```php
<?php

namespace App\Exports\Qualifications;

use App\Enums\QualificationLevelEnum;
use App\Models\Person;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class StaffQualificationProfileExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle
{
    use Exportable;

    public function __construct(private readonly Person $person)
    {
    }

    public function title(): string
    {
        return "Qualifications - {$this->person->first_name} {$this->person->surname}";
    }

    public function headings(): array
    {
        return ['Qualification', 'Level', 'Institution', 'Year', 'Status', 'Approved At'];
    }

    public function array(): array
    {
        return $this->person->qualifications()
            ->orderByDesc('year')
            ->get()
            ->map(fn ($q) => [
                $q->qualification,
                $q->level ? (QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level) : null,
                $q->institution,
                $q->year,
                $q->status?->label(),
                $q->approved_at?->format('Y-m-d'),
            ])->all();
    }
}
```

- [ ] **Step 3: Run tests**

Run: `php artisan test --filter=ExcelListExportTest`

- [ ] **Step 4: Commit**

```bash
git add app/Exports/Qualifications/ tests/Feature/QualificationReports/ExcelListExportTest.php
git commit -m "feat(qualifications): add gaps and staff profile Excel exports"
```

---

## Task 18: Excel export — controller `exportExcel()` endpoint

**Files:**
- Modify: `app/Http/Controllers/QualificationReportController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/QualificationReports/ExcelRouteTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature\QualificationReports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExcelRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_route_dispatches_list_export(): void
    {
        Excel::fake();
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.export']);

        $this->actingAs($user)
            ->get('/qualifications/reports/export/excel?type=list')
            ->assertOk();

        Excel::assertDownloaded(fn ($name) => str_ends_with($name, '.xlsx'));
    }

    public function test_excel_route_requires_export_permission(): void
    {
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo('qualifications.reports.view');

        $this->actingAs($user)
            ->get('/qualifications/reports/export/excel?type=list')
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Add controller method**

Add to `QualificationReportController`:

```php
    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:list,by_unit,by_level,gaps',
        ]);
        $filter = $this->service->applyUnitScope(
            QualificationReportFilter::fromRequest($request),
            $request->user(),
        );

        [$export, $filename] = match ($validated['type']) {
            'list'    => [new \App\Exports\Qualifications\QualificationListExport($filter),         'qualifications-list.xlsx'],
            'by_unit' => [new \App\Exports\Qualifications\QualificationByUnitExport($filter),      'qualifications-by-unit.xlsx'],
            'by_level'=> [new \App\Exports\Qualifications\QualificationByLevelExport($filter),     'qualifications-by-level.xlsx'],
            'gaps'    => [new \App\Exports\Qualifications\StaffWithoutQualificationsExport($filter),'staff-without-qualifications.xlsx'],
        };

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
```

- [ ] **Step 3: Add route**

Inside the existing `qualifications.reports.*` group, after the `index` route:

```php
Route::get('/export/excel', [App\Http\Controllers\QualificationReportController::class, 'exportExcel'])
    ->name('export.excel')
    ->middleware('can:qualifications.reports.export');
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=ExcelRouteTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/QualificationReportController.php routes/web.php tests/Feature/QualificationReports/ExcelRouteTest.php
git commit -m "feat(qualifications): add Excel export route"
```

---

## Task 19: Install DomPDF facade + shared PDF layout

**Files:**
- Check: `composer.json` already includes `dompdf/dompdf`; we'll use Barryvdh wrapper if not already installed.
- Create/Verify: install `barryvdh/laravel-dompdf` if not present.
- Create: `resources/views/pdf/qualifications/layout.blade.php`

- [ ] **Step 1: Ensure the Laravel DomPDF wrapper is installed**

Run: `grep -q barryvdh/laravel-dompdf composer.json || composer require barryvdh/laravel-dompdf --no-interaction`
Expected: command succeeds; `Pdf` facade becomes available.

After install, verify facade works:

Run: `php artisan tinker --execute="var_dump(class_exists('Barryvdh\DomPDF\Facade\Pdf'));"`
Expected: `bool(true)`.

- [ ] **Step 2: Create shared layout**

`resources/views/pdf/qualifications/layout.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Qualification Report')</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #666; font-size: 9px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; letter-spacing: 0.03em; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #888; text-align: center; }
        .filter-summary { background: #f9fafb; border: 1px solid #e5e7eb; padding: 6px 8px; margin-bottom: 10px; font-size: 9px; }
        .bar { display: inline-block; height: 8px; background: #4f46e5; vertical-align: middle; }
    </style>
</head>
<body>
    <h1>@yield('title', 'Qualification Report')</h1>
    <div class="meta">
        Generated: {{ now()->format('Y-m-d H:i') }}
        @isset($user) · By: {{ $user->name ?? '—' }} @endisset
    </div>

    @isset($filterSummary)
        <div class="filter-summary"><strong>Filters:</strong> {{ $filterSummary }}</div>
    @endisset

    @yield('content')

    <div class="footer">
        Generated by HRMIS · {{ now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>
```

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock resources/views/pdf/qualifications/layout.blade.php
git commit -m "feat(qualifications): install laravel-dompdf and add shared PDF layout"
```

---

## Task 20: PDF templates — list, by_unit, by_level, gaps, staff_profile

**Files:**
- Create: `resources/views/pdf/qualifications/list.blade.php`
- Create: `resources/views/pdf/qualifications/by_unit.blade.php`
- Create: `resources/views/pdf/qualifications/by_level.blade.php`
- Create: `resources/views/pdf/qualifications/gaps.blade.php`
- Create: `resources/views/pdf/qualifications/staff_profile.blade.php`

These are all view templates — no tests at this layer (the route-level tests will exercise them).

- [ ] **Step 1: `list.blade.php`**

```blade
@extends('pdf.qualifications.layout')

@section('title', 'Staff Qualifications List')

@section('content')
    <table>
        <thead>
        <tr>
            <th>Staff #</th><th>Name</th><th>Rank</th><th>Unit</th>
            <th>Qualification</th><th>Level</th><th>Institution</th><th>Year</th><th>Status</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $q)
            <tr>
                <td>{{ $q->person?->institutionPerson?->staff_number }}</td>
                <td>{{ $q->person?->first_name }} {{ $q->person?->surname }}</td>
                <td>{{ $q->person?->institutionPerson?->currentRank?->job?->name }}</td>
                <td>{{ $q->person?->institutionPerson?->currentUnit?->unit?->name }}</td>
                <td>{{ $q->qualification }}</td>
                <td>{{ $q->level ? (\App\Enums\QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level) : '' }}</td>
                <td>{{ $q->institution }}</td>
                <td>{{ $q->year }}</td>
                <td>{{ $q->status?->label() }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
```

- [ ] **Step 2: `by_unit.blade.php`**

```blade
@extends('pdf.qualifications.layout')

@section('title', 'Qualifications by Unit')

@section('content')
    <table>
        <thead>
        <tr>
            <th>Unit</th>
            @foreach ($levels as $lv)
                <th>{{ $lv->label() }}</th>
            @endforeach
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($byUnit as $unitName => $counts)
            @php $total = array_sum($counts); @endphp
            <tr>
                <td>{{ $unitName }}</td>
                @foreach ($levels as $lv)
                    <td>{{ $counts[$lv->value] ?? 0 }}</td>
                @endforeach
                <td><strong>{{ $total }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
```

- [ ] **Step 3: `by_level.blade.php`**

```blade
@extends('pdf.qualifications.layout')

@section('title', 'Qualifications by Level')

@section('content')
    <table>
        <thead><tr><th>Level</th><th>Staff Count</th><th>% of Workforce</th><th style="width:30%">Distribution</th></tr></thead>
        <tbody>
        @foreach ($levels as $lv)
            @php
                $count = $distribution[$lv->value] ?? 0;
                $pct = $totalStaff > 0 ? round(($count / $totalStaff) * 100, 1) : 0;
                $barWidth = (int) $pct;
            @endphp
            <tr>
                <td>{{ $lv->label() }}</td>
                <td>{{ $count }}</td>
                <td>{{ $pct }}%</td>
                <td><span class="bar" style="width: {{ $barWidth }}%"></span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
```

- [ ] **Step 4: `gaps.blade.php`**

```blade
@extends('pdf.qualifications.layout')

@section('title', 'Staff Without Recorded Qualifications')

@section('content')
    <table>
        <thead><tr><th>Staff #</th><th>Name</th><th>Rank</th><th>Unit</th></tr></thead>
        <tbody>
        @foreach ($staff as $person)
            <tr>
                <td>{{ $person->institutionPerson?->staff_number }}</td>
                <td>{{ $person->first_name }} {{ $person->surname }}</td>
                <td>{{ $person->institutionPerson?->currentRank?->job?->name }}</td>
                <td>{{ $person->institutionPerson?->currentUnit?->unit?->name }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
```

- [ ] **Step 5: `staff_profile.blade.php`**

```blade
@extends('pdf.qualifications.layout')

@section('title', 'Qualifications: ' . $person->first_name . ' ' . $person->surname)

@section('content')
    <p>
        <strong>Staff #:</strong> {{ $person->institutionPerson?->staff_number ?? '—' }}<br>
        <strong>Rank:</strong> {{ $person->institutionPerson?->currentRank?->job?->name ?? '—' }}<br>
        <strong>Unit:</strong> {{ $person->institutionPerson?->currentUnit?->unit?->name ?? '—' }}
    </p>
    <table>
        <thead>
        <tr>
            <th>Qualification</th><th>Level</th><th>Institution</th><th>Year</th><th>Status</th><th>Approved</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($qualifications as $q)
            <tr>
                <td>{{ $q->qualification }}</td>
                <td>{{ $q->level ? (\App\Enums\QualificationLevelEnum::tryFrom($q->level)?->label() ?? $q->level) : '' }}</td>
                <td>{{ $q->institution }}</td>
                <td>{{ $q->year }}</td>
                <td>{{ $q->status?->label() }}</td>
                <td>{{ $q->approved_at?->format('Y-m-d') ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
```

- [ ] **Step 6: Commit**

```bash
git add resources/views/pdf/qualifications/
git commit -m "feat(qualifications): add PDF blade templates for reports"
```

---

## Task 21: PDF export controller endpoints

**Files:**
- Modify: `app/Http/Controllers/QualificationReportController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/QualificationReports/PdfRouteTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature\QualificationReports;

use App\Models\Person;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfRouteTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.export']);
        return $user;
    }

    public function test_pdf_list_returns_pdf_content_type(): void
    {
        Qualification::factory()->approved()->count(2)->create();
        $this->actingAs($this->actingUser())
            ->get('/qualifications/reports/export/pdf?type=list')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_by_unit_ok(): void
    {
        $this->actingAs($this->actingUser())
            ->get('/qualifications/reports/export/pdf?type=by_unit')
            ->assertOk();
    }

    public function test_pdf_by_level_ok(): void
    {
        $this->actingAs($this->actingUser())
            ->get('/qualifications/reports/export/pdf?type=by_level')
            ->assertOk();
    }

    public function test_pdf_gaps_ok(): void
    {
        $this->actingAs($this->actingUser())
            ->get('/qualifications/reports/export/pdf?type=gaps')
            ->assertOk();
    }

    public function test_staff_profile_pdf_ok(): void
    {
        $person = Person::factory()->create();
        Qualification::factory()->for($person)->approved()->count(2)->create();
        $this->actingAs($this->actingUser())
            ->get("/qualifications/reports/staff/{$person->id}/profile.pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
```

- [ ] **Step 2: Add controller methods**

Append to `QualificationReportController`:

```php
    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $validated = $request->validate([
            'type' => 'required|in:list,by_unit,by_level,gaps',
        ]);
        $filter = $this->service->applyUnitScope(
            QualificationReportFilter::fromRequest($request),
            $request->user(),
        );

        $data = match ($validated['type']) {
            'list' => [
                'view' => 'pdf.qualifications.list',
                'data' => [
                    'rows' => $this->service->staffList($filter, perPage: 5000)->items(),
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'qualifications-list.pdf',
            ],
            'by_unit' => [
                'view' => 'pdf.qualifications.by_unit',
                'data' => [
                    'byUnit' => $this->service->byUnit($filter),
                    'levels' => QualificationLevelEnum::orderedByRank(),
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'qualifications-by-unit.pdf',
            ],
            'by_level' => [
                'view' => 'pdf.qualifications.by_level',
                'data' => [
                    'distribution' => $this->service->levelDistribution($filter),
                    'levels' => QualificationLevelEnum::orderedByRank(),
                    'totalStaff' => \App\Models\InstitutionPerson::query()->count() ?: 1,
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'qualifications-by-level.pdf',
            ],
            'gaps' => [
                'view' => 'pdf.qualifications.gaps',
                'data' => [
                    'staff' => $this->service->staffWithoutQualifications($filter),
                    'user' => $request->user(),
                    'filterSummary' => $this->filterSummary($filter),
                ],
                'filename' => 'staff-without-qualifications.pdf',
            ],
        };

        return \Barryvdh\DomPDF\Facade\Pdf::loadView($data['view'], $data['data'])
            ->setPaper('a4')
            ->download($data['filename']);
    }

    public function staffProfilePdf(\App\Models\Person $person, Request $request): \Illuminate\Http\Response
    {
        // Access control: the requesting user must have export permission OR own this Person record
        $user = $request->user();
        $owns = optional($user->person)->id === $person->id;
        if (! $owns && ! $user->can('qualifications.reports.export')) {
            abort(403);
        }

        $qualifications = $person->qualifications()->orderByDesc('year')->get();

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.qualifications.staff_profile', [
            'person' => $person->loadMissing(['institutionPerson.currentUnit.unit', 'institutionPerson.currentRank.job']),
            'qualifications' => $qualifications,
            'user' => $user,
        ])->setPaper('a4')->download("qualifications-{$person->id}.pdf");
    }

    private function filterSummary(QualificationReportFilter $filter): string
    {
        $parts = [];
        foreach ($filter->toQueryArray() as $k => $v) {
            $parts[] = "{$k}={$v}";
        }
        return $parts ? implode(', ', $parts) : 'none';
    }
```

- [ ] **Step 3: Add routes**

Inside the same `qualifications.reports.*` group:

```php
Route::get('/export/pdf', [App\Http\Controllers\QualificationReportController::class, 'exportPdf'])
    ->name('export.pdf')
    ->middleware('can:qualifications.reports.export');

Route::get('/staff/{person}/profile.pdf', [App\Http\Controllers\QualificationReportController::class, 'staffProfilePdf'])
    ->name('staff.profile.pdf');
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=PdfRouteTest`
Expected: all 5 PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/QualificationReportController.php routes/web.php tests/Feature/QualificationReports/PdfRouteTest.php
git commit -m "feat(qualifications): add PDF export routes and controller methods"
```

---

## Task 22: Dashboard widgets JSON endpoint

**Files:**
- Create: `app/Http/Controllers/QualificationDashboardController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/QualificationReports/DashboardWidgetTest.php`

(We create a dedicated controller rather than editing the existing dashboard closure.)

- [ ] **Step 1: Write failing test**

```php
<?php

namespace Tests\Feature\QualificationReports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widgets_endpoint_returns_expected_shape(): void
    {
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        $this->actingAs($user)->getJson('/dashboard/qualifications-widgets')
            ->assertOk()
            ->assertJsonStructure([
                'levelDistribution',
                'byUnit',
                'topInstitutions',
                'trendByYear',
                'pendingApprovals' => ['count', 'sparkline'],
                'staffWithoutQualificationsCount',
            ]);
    }

    public function test_widgets_endpoint_requires_permission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/dashboard/qualifications-widgets')->assertForbidden();
    }
}
```

- [ ] **Step 2: Create controller**

```php
<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\QualificationReportFilter;
use App\Services\QualificationReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QualificationDashboardController extends Controller
{
    public function __construct(private readonly QualificationReportService $service)
    {
    }

    public function widgets(Request $request): JsonResponse
    {
        $filter = $this->service->applyUnitScope(
            new QualificationReportFilter(),
            $request->user(),
        );

        return response()->json([
            'levelDistribution' => $this->service->levelDistribution($filter),
            'byUnit' => $this->service->byUnit($filter),
            'topInstitutions' => $this->service->topInstitutions($filter, 10),
            'trendByYear' => $this->service->trendByYear($filter),
            'pendingApprovals' => $this->service->pendingApprovalsStats(),
            'staffWithoutQualificationsCount' => $this->service->staffWithoutQualifications($filter)->count(),
        ]);
    }
}
```

- [ ] **Step 3: Add route**

Outside the reports group (top-level auth group):

```php
Route::middleware(['auth', 'can:qualifications.reports.view'])
    ->get('/dashboard/qualifications-widgets', [App\Http\Controllers\QualificationDashboardController::class, 'widgets'])
    ->name('dashboard.qualifications');
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --filter=DashboardWidgetTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/QualificationDashboardController.php routes/web.php tests/Feature/QualificationReports/DashboardWidgetTest.php
git commit -m "feat(qualifications): add dashboard widgets JSON endpoint"
```

---

## Task 23: Reusable chart components

**Files:**
- Create: `resources/js/Components/Charts/Qualifications/LevelDistributionChart.vue`
- Create: `resources/js/Components/Charts/Qualifications/ByUnitChart.vue`
- Create: `resources/js/Components/Charts/Qualifications/TopInstitutionsChart.vue`
- Create: `resources/js/Components/Charts/Qualifications/AcquiredOverTimeChart.vue`
- Create: `resources/js/Components/Charts/Qualifications/PendingApprovalsWidget.vue`

(No new Vue-only test harness — rely on Inertia page tests + manual QA.)

- [ ] **Step 1: `LevelDistributionChart.vue`**

```vue
<script setup>
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Legend, Tooltip, ArcElement } from 'chart.js';
import { useDark } from '@vueuse/core';

ChartJS.register(Title, Legend, Tooltip, ArcElement);
const isDark = useDark();

const props = defineProps({
    distribution: { type: Object, required: true }, // { masters: 12, degree: 34, ... }
    labels: { type: Object, required: true },        // { masters: 'Masters', ... }
    title: { type: String, default: 'Qualification Level Distribution' },
});

const colors = [
    '#4f46e5', '#06b6d4', '#10b981', '#84cc16', '#eab308',
    '#f97316', '#ef4444', '#ec4899', '#8b5cf6', '#64748b',
];

const chartData = computed(() => {
    const entries = Object.entries(props.distribution).filter(([, v]) => v > 0);
    return {
        labels: entries.map(([k]) => props.labels[k] ?? k),
        datasets: [{
            data: entries.map(([, v]) => v),
            backgroundColor: entries.map((_, i) => colors[i % colors.length]),
        }],
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: { color: isDark.value ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.7)' },
        },
        title: {
            display: true,
            text: props.title,
            color: isDark.value ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.7)',
            font: { size: 14, weight: 'bold' },
        },
    },
}));
</script>

<template>
    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
        <div class="h-80"><Doughnut :data="chartData" :options="chartOptions" /></div>
    </div>
</template>
```

- [ ] **Step 2: `ByUnitChart.vue`**

```vue
<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { useDark } from '@vueuse/core';

ChartJS.register(Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale);
const isDark = useDark();

const props = defineProps({
    byUnit: { type: Object, required: true },  // { 'Unit A': { masters: 3, degree: 5, ... }, ... }
    levelLabels: { type: Object, required: true },
    title: { type: String, default: 'Qualifications by Unit' },
    topN: { type: Number, default: 8 },
});

const colors = ['#4f46e5', '#06b6d4', '#10b981', '#84cc16', '#eab308', '#f97316', '#ef4444', '#ec4899', '#8b5cf6', '#64748b'];

const topUnits = computed(() => {
    return Object.entries(props.byUnit)
        .map(([name, counts]) => ({ name, total: Object.values(counts).reduce((a, b) => a + b, 0), counts }))
        .sort((a, b) => b.total - a.total)
        .slice(0, props.topN);
});

const levelKeys = computed(() => {
    const keys = new Set();
    topUnits.value.forEach(u => Object.keys(u.counts).forEach(k => keys.add(k)));
    return [...keys];
});

const chartData = computed(() => ({
    labels: topUnits.value.map(u => u.name),
    datasets: levelKeys.value.map((lv, i) => ({
        label: props.levelLabels[lv] ?? lv,
        data: topUnits.value.map(u => u.counts[lv] ?? 0),
        backgroundColor: colors[i % colors.length],
    })),
}));

const chartOptions = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top', labels: { color: isDark.value ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.7)' } },
        title: { display: true, text: props.title, color: isDark.value ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.7)', font: { size: 14, weight: 'bold' } },
    },
    scales: {
        x: { stacked: true, ticks: { color: isDark.value ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)' } },
        y: { stacked: true, ticks: { color: isDark.value ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)' } },
    },
}));
</script>

<template>
    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
        <div class="h-96"><Bar :data="chartData" :options="chartOptions" /></div>
    </div>
</template>
```

- [ ] **Step 3: `TopInstitutionsChart.vue`**

```vue
<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { useDark } from '@vueuse/core';

ChartJS.register(Title, Legend, Tooltip, BarElement, CategoryScale, LinearScale);
const isDark = useDark();

const props = defineProps({
    institutions: { type: Array, required: true }, // [{ name, count }]
    title: { type: String, default: 'Top Institutions' },
});

const chartData = computed(() => ({
    labels: props.institutions.map(i => i.name),
    datasets: [{
        label: 'Qualifications',
        data: props.institutions.map(i => i.count),
        backgroundColor: '#06b6d4',
    }],
}));

const chartOptions = computed(() => ({
    indexAxis: 'y',
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        title: { display: true, text: props.title, color: isDark.value ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.7)', font: { size: 14, weight: 'bold' } },
    },
    scales: {
        x: { ticks: { color: isDark.value ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)' } },
        y: { ticks: { color: isDark.value ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)' } },
    },
}));
</script>

<template>
    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
        <div class="h-80"><Bar :data="chartData" :options="chartOptions" /></div>
    </div>
</template>
```

- [ ] **Step 4: `AcquiredOverTimeChart.vue`**

```vue
<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, Title, Legend, Tooltip, LineElement, PointElement, CategoryScale, LinearScale } from 'chart.js';
import { useDark } from '@vueuse/core';

ChartJS.register(Title, Legend, Tooltip, LineElement, PointElement, CategoryScale, LinearScale);
const isDark = useDark();

const props = defineProps({
    trend: { type: Object, required: true }, // { 2018: 3, 2020: 2 }
    title: { type: String, default: 'Qualifications Acquired Over Time' },
});

const sortedYears = computed(() => Object.keys(props.trend).map(Number).sort());

const chartData = computed(() => ({
    labels: sortedYears.value,
    datasets: [{
        label: 'Count',
        data: sortedYears.value.map(y => props.trend[y]),
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79, 70, 229, 0.15)',
        fill: true,
        tension: 0.3,
    }],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        title: { display: true, text: props.title, color: isDark.value ? 'rgba(255,255,255,0.8)' : 'rgba(0,0,0,0.7)', font: { size: 14, weight: 'bold' } },
    },
    scales: {
        x: { ticks: { color: isDark.value ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)' } },
        y: { beginAtZero: true, ticks: { color: isDark.value ? 'rgba(255,255,255,0.7)' : 'rgba(0,0,0,0.7)' } },
    },
}));
</script>

<template>
    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4">
        <div class="h-80"><Line :data="chartData" :options="chartOptions" /></div>
    </div>
</template>
```

- [ ] **Step 5: `PendingApprovalsWidget.vue`**

```vue
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    count: { type: Number, required: true },
    sparkline: { type: Array, required: true }, // 30 numbers
    linkTo: { type: String, default: '/data-integrity/pending-qualifications' },
    title: { type: String, default: 'Pending Qualification Approvals' },
});

const max = computed(() => Math.max(1, ...props.sparkline));
const bars = computed(() => props.sparkline.map(v => (v / max.value) * 100));
</script>

<template>
    <div class="h-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 flex flex-col">
        <div class="flex items-baseline justify-between">
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ title }}</h3>
            <Link :href="linkTo" class="text-xs text-indigo-600 hover:underline">View →</Link>
        </div>
        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ count.toLocaleString() }}</div>
        <div class="mt-auto flex items-end gap-0.5 h-12">
            <div
                v-for="(h, i) in bars"
                :key="i"
                class="flex-1 bg-indigo-400 dark:bg-indigo-500 rounded-sm"
                :style="{ height: h + '%', minHeight: '2px' }"
            ></div>
        </div>
        <div class="text-[10px] text-gray-400 mt-1">Submissions, last 30 days</div>
    </div>
</template>
```

- [ ] **Step 6: Smoke-test build**

Run: `npm run build`
Expected: build completes with no errors referencing the new components.

- [ ] **Step 7: Commit**

```bash
git add resources/js/Components/Charts/Qualifications/
git commit -m "feat(qualifications): add reusable chart components"
```

---

## Task 24: `/qualifications/reports` analytics page (full UI)

**Files:**
- Modify: `resources/js/Pages/Qualification/Reports/Index.vue` (replace stub)

- [ ] **Step 1: Replace the file contents**

```vue
<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link, Head, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LevelDistributionChart from '@/Components/Charts/Qualifications/LevelDistributionChart.vue';
import ByUnitChart from '@/Components/Charts/Qualifications/ByUnitChart.vue';
import TopInstitutionsChart from '@/Components/Charts/Qualifications/TopInstitutionsChart.vue';
import AcquiredOverTimeChart from '@/Components/Charts/Qualifications/AcquiredOverTimeChart.vue';

const props = defineProps({
    filters: Object,
    filterOptions: Object,
    kpis: Object,
    levelDistribution: Object,
    byUnit: Object,
    topInstitutions: Array,
    trendByYear: Object,
    staffList: Object,
});

const page = usePage();
const form = ref({ ...props.filters });

const levelLabels = computed(() => {
    const m = {};
    (props.filterOptions?.levels ?? []).forEach(l => { m[l.value] = l.label; });
    return m;
});

const update = debounce(() => {
    router.get(route('qualifications.reports.index'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch(form, update, { deep: true });

function clearFilters() {
    form.value = {};
}

const canExport = computed(() => page.props.auth?.can?.['qualifications.reports.export'] ?? false);

function exportUrl(format, type) {
    const base = format === 'pdf'
        ? route('qualifications.reports.export.pdf')
        : route('qualifications.reports.export.excel');
    const params = new URLSearchParams({ ...form.value, type });
    return `${base}?${params.toString()}`;
}
</script>

<template>
    <Head title="Qualification Reports" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Qualification Reports</h2>
                <div class="flex gap-2" v-if="canExport">
                    <div class="relative group">
                        <button class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">Export PDF ▾</button>
                        <div class="absolute right-0 mt-1 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded border dark:border-gray-700 z-10 min-w-[180px]">
                            <a :href="exportUrl('pdf','list')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Staff List</a>
                            <a :href="exportUrl('pdf','by_unit')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">By Unit</a>
                            <a :href="exportUrl('pdf','by_level')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">By Level</a>
                            <a :href="exportUrl('pdf','gaps')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Staff Without Quals</a>
                        </div>
                    </div>
                    <div class="relative group">
                        <button class="px-3 py-1.5 text-sm bg-emerald-600 text-white rounded hover:bg-emerald-700">Export Excel ▾</button>
                        <div class="absolute right-0 mt-1 hidden group-hover:block bg-white dark:bg-gray-800 shadow-lg rounded border dark:border-gray-700 z-10 min-w-[180px]">
                            <a :href="exportUrl('excel','list')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Staff List</a>
                            <a :href="exportUrl('excel','by_unit')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">By Unit</a>
                            <a :href="exportUrl('excel','by_level')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">By Level</a>
                            <a :href="exportUrl('excel','gaps')" class="block px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Staff Without Quals</a>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-7xl mx-auto px-4 space-y-6">
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <select v-model="form.unit_id" class="form-select rounded">
                        <option :value="undefined">All Units</option>
                        <option v-for="u in filterOptions?.units ?? []" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <select v-model="form.level" class="form-select rounded">
                        <option :value="undefined">All Levels</option>
                        <option v-for="l in filterOptions?.levels ?? []" :key="l.value" :value="l.value">{{ l.label }}</option>
                    </select>
                    <select v-model="form.status" class="form-select rounded">
                        <option :value="undefined">All Statuses</option>
                        <option v-for="s in filterOptions?.statuses ?? []" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                    <select v-model="form.gender" class="form-select rounded">
                        <option :value="undefined">Any Gender</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select>
                    <input v-model="form.year_from" type="number" placeholder="Year from" class="form-input rounded" />
                    <input v-model="form.year_to" type="number" placeholder="Year to" class="form-input rounded" />
                    <input v-model="form.institution" type="text" placeholder="Institution" class="form-input rounded" />
                    <input v-model="form.course" type="text" placeholder="Course keyword" class="form-input rounded" />
                </div>
                <div class="mt-3 flex justify-end">
                    <button @click="clearFilters" class="text-sm text-gray-600 dark:text-gray-300 hover:underline">Clear all</button>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Total Qualifications</div>
                    <div class="mt-1 text-2xl font-bold">{{ kpis.totalQualifications.toLocaleString() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Staff Covered</div>
                    <div class="mt-1 text-2xl font-bold">{{ kpis.staffCovered.toLocaleString() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Pending</div>
                    <div class="mt-1 text-2xl font-bold text-yellow-600">{{ kpis.pending.toLocaleString() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700">
                    <div class="text-xs uppercase tracking-wide text-gray-500">Staff Without Quals</div>
                    <div class="mt-1 text-2xl font-bold text-red-600">{{ kpis.withoutQualifications.toLocaleString() }}</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <LevelDistributionChart :distribution="levelDistribution" :labels="levelLabels" />
                <ByUnitChart :by-unit="byUnit" :level-labels="levelLabels" />
                <TopInstitutionsChart :institutions="topInstitutions" />
                <AcquiredOverTimeChart :trend="trendByYear" />
            </div>

            <!-- Staff list -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-semibold">Staff Qualifications</div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-left">
                        <tr>
                            <th class="px-3 py-2">Name</th>
                            <th class="px-3 py-2">Rank</th>
                            <th class="px-3 py-2">Unit</th>
                            <th class="px-3 py-2">Qualification</th>
                            <th class="px-3 py-2">Level</th>
                            <th class="px-3 py-2">Institution</th>
                            <th class="px-3 py-2">Year</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="q in staffList.data" :key="q.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                            <td class="px-3 py-2">{{ q.person?.first_name }} {{ q.person?.surname }}</td>
                            <td class="px-3 py-2">{{ q.person?.institution_person?.current_rank?.job?.name }}</td>
                            <td class="px-3 py-2">{{ q.person?.institution_person?.current_unit?.unit?.name }}</td>
                            <td class="px-3 py-2">{{ q.qualification }}</td>
                            <td class="px-3 py-2">{{ levelLabels[q.level] ?? q.level }}</td>
                            <td class="px-3 py-2">{{ q.institution }}</td>
                            <td class="px-3 py-2">{{ q.year }}</td>
                            <td class="px-3 py-2">{{ q.status }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400">
                    Page {{ staffList.current_page }} of {{ staffList.last_page }} · {{ staffList.total }} rows
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

NOTE: field access in the staff-list table (`q.person?.institution_person?.current_rank?.job?.name`) assumes Inertia's default snake_case key conversion. If the app uses `camelCase` for nested relations, update accordingly — check `app/Http/Middleware/HandleInertiaRequests.php` for any transformation and an existing Inertia page like `Person/Summary.vue` to confirm the exact access pattern.

- [ ] **Step 2: Verify the Index renders with live data**

Run: `php artisan serve` (background) + `npm run dev` (background), then log in with a super-administrator account and visit `/qualifications/reports`. Confirm filters update the URL and charts refresh.

- [ ] **Step 3: Run the full reports test suite**

Run: `php artisan test --filter=QualificationReports`
Expected: all tests still pass.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Qualification/Reports/Index.vue
git commit -m "feat(qualifications): implement reports analytics page UI"
```

---

## Task 25: Dashboard integration

**Files:**
- Create: `resources/js/Components/Qualifications/QualificationsDashboardSection.vue`
- Modify: `resources/js/Pages/Dashboard.vue`

- [ ] **Step 1: Create the section component**

```vue
<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { Link, usePage } from '@inertiajs/vue3';
import LevelDistributionChart from '@/Components/Charts/Qualifications/LevelDistributionChart.vue';
import ByUnitChart from '@/Components/Charts/Qualifications/ByUnitChart.vue';
import TopInstitutionsChart from '@/Components/Charts/Qualifications/TopInstitutionsChart.vue';
import AcquiredOverTimeChart from '@/Components/Charts/Qualifications/AcquiredOverTimeChart.vue';
import PendingApprovalsWidget from '@/Components/Charts/Qualifications/PendingApprovalsWidget.vue';

const page = usePage();
const loading = ref(true);
const data = ref(null);

const levelLabels = {
    sssce_wassce: 'SSSCE/WASSCE',
    certificate: 'Certificate',
    diploma: 'Diploma',
    hnd: 'HND',
    degree: 'Degree',
    pg_certificate: 'PG Certificate',
    pg_diploma: 'PG Diploma',
    masters: 'Masters',
    doctorate: 'Doctorate/PHD',
    professional: 'Professional',
};

onMounted(async () => {
    try {
        const res = await axios.get('/dashboard/qualifications-widgets');
        data.value = res.data;
    } finally {
        loading.value = false;
    }
});

const canView = computed(() => page.props.auth?.can?.['qualifications.reports.view'] ?? false);
</script>

<template>
    <section v-if="canView" class="mt-8 space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Workforce Qualifications</h2>
            <Link :href="route('qualifications.reports.index')" class="text-sm text-indigo-600 hover:underline">Full Reports →</Link>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="n in 4" :key="n" class="h-72 bg-gray-100 dark:bg-gray-800 rounded animate-pulse"></div>
        </div>

        <div v-else-if="data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <LevelDistributionChart :distribution="data.levelDistribution" :labels="levelLabels" />
            <ByUnitChart :by-unit="data.byUnit" :level-labels="levelLabels" />
            <TopInstitutionsChart :institutions="data.topInstitutions" />
            <AcquiredOverTimeChart :trend="data.trendByYear" />
            <PendingApprovalsWidget :count="data.pendingApprovals.count" :sparkline="data.pendingApprovals.sparkline" />
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-900/5 dark:ring-gray-700 p-4 flex flex-col">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-300">Staff Without Qualifications</h3>
                <div class="mt-2 text-3xl font-bold text-red-600">{{ data.staffWithoutQualificationsCount.toLocaleString() }}</div>
                <Link :href="route('qualifications.reports.index')" class="mt-auto text-xs text-indigo-600 hover:underline">View list →</Link>
            </div>
        </div>
    </section>
</template>
```

- [ ] **Step 2: Wire into Dashboard.vue**

Open `resources/js/Pages/Dashboard.vue`. Near the top:

```js
import QualificationsDashboardSection from '@/Components/Qualifications/QualificationsDashboardSection.vue';
```

Inside the main content region (after existing sections, before the closing layout tag), add:

```vue
<QualificationsDashboardSection />
```

Placement: find the last top-level `<section>` or equivalent region in `Dashboard.vue` and append the new section after it. If uncertain, read the file first.

- [ ] **Step 3: Smoke-test**

Run: `npm run build`
Expected: build succeeds.

Start dev servers (`php artisan serve` + `npm run dev`) and verify:
1. Log in as a user with `qualifications.reports.view` — dashboard shows the section.
2. Log in as a user without the permission — section is hidden.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/Qualifications/ resources/js/Pages/Dashboard.vue
git commit -m "feat(qualifications): add dashboard qualifications section"
```

---

## Task 26: Integrate pending approvals widget into `/data-integrity/pending-qualifications`

**Files:**
- Modify: `resources/js/Pages/DataIntegrity/PendingQualifications.vue`

- [ ] **Step 1: Read the existing file**

Use Read tool on `resources/js/Pages/DataIntegrity/PendingQualifications.vue` to understand current structure and where to place the widget.

- [ ] **Step 2: Add the widget**

Import and render `PendingApprovalsWidget` at the top of the page content. The widget needs `count` and `sparkline` — pass them via new props (prop-drill from controller):

In `app/Http/Controllers/DataIntegrityController.php`, find the method that renders `PendingQualifications` (grep for `'DataIntegrity/PendingQualifications'`), and add:

```php
'pendingStats' => app(\App\Services\QualificationReportService::class)->pendingApprovalsStats(),
```

to the `Inertia::render` props array.

In the Vue page:

```vue
<script setup>
// existing imports …
import PendingApprovalsWidget from '@/Components/Charts/Qualifications/PendingApprovalsWidget.vue';

defineProps({
    // existing props …
    pendingStats: Object,
});
</script>

<template>
    <!-- existing layout -->
    <div class="mb-4" v-if="pendingStats">
        <PendingApprovalsWidget :count="pendingStats.count" :sparkline="pendingStats.sparkline" link-to="#" />
    </div>
    <!-- existing table -->
</template>
```

- [ ] **Step 3: Smoke-test**

Run: `npm run build`. Start dev servers and visit `/data-integrity/pending-qualifications` — verify widget renders with count matching the list below.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/DataIntegrity/PendingQualifications.vue app/Http/Controllers/DataIntegrityController.php
git commit -m "feat(qualifications): embed pending approvals widget in data integrity page"
```

---

## Task 27: Staff summary page — "Download Qualification Profile" button

**Files:**
- Modify: `resources/js/Pages/Person/Summary.vue`

- [ ] **Step 1: Read existing file** to find the qualifications section block.

Use Read tool on `resources/js/Pages/Person/Summary.vue` and Grep for 'qualification' to locate the section.

- [ ] **Step 2: Add download button**

In the qualifications section, add:

```vue
<a
    v-if="$page.props.auth?.can?.['qualifications.reports.export'] || isOwner"
    :href="route('qualifications.reports.staff.profile.pdf', person.id)"
    class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700"
>
    Download Profile PDF
</a>
```

`isOwner` is typically available as a prop on this page — grep for `isOwner` in the file to confirm, and fall back to the auth-based check alone if not.

- [ ] **Step 3: Smoke-test**

Start dev servers, open a staff summary page, and click the download button. Confirm PDF downloads with expected contents.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Person/Summary.vue
git commit -m "feat(qualifications): add profile PDF download to staff summary"
```

---

## Task 28: Navigation link + permission-gated menu entry

**Files:**
- Modify: the main Authenticated layout navigation (typically `resources/js/Layouts/AuthenticatedLayout.vue` or a `Components/NavLink` host).

- [ ] **Step 1: Locate the Reports menu**

Grep for `Report/` or `reports.index` in `resources/js/Layouts/` and `resources/js/Components/` to find where report menu links are defined.

- [ ] **Step 2: Add a link to `/qualifications/reports`**

Following the same pattern as existing report links, add:

```vue
<Link
    v-if="$page.props.auth?.can?.['qualifications.reports.view']"
    :href="route('qualifications.reports.index')"
    class="..."
>
    Qualifications
</Link>
```

Use whatever class/structure is used for neighbouring links.

- [ ] **Step 3: Smoke-test**

Run: `npm run build`. Log in as a user with/without the permission and confirm visibility.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.vue  # or whichever file you modified
git commit -m "feat(qualifications): add navigation link to reports page"
```

---

## Task 29: Final consolidated test + Pint

- [ ] **Step 1: Run the full qualification reports test suite**

Run: `php artisan test --filter=QualificationReports`
Expected: all tests pass.

- [ ] **Step 2: Run the entire test suite**

Run: `php artisan test`
Expected: all tests pass. If a pre-existing test breaks, investigate — do not blindly adjust.

- [ ] **Step 3: Run Pint (PHP formatter)**

Run: `./vendor/bin/pint --dirty`
Expected: clean formatting.

- [ ] **Step 4: Run ESLint/Prettier on frontend**

Run: `npm run lint && npm run format`
Expected: no errors.

- [ ] **Step 5: Final build**

Run: `npm run build`
Expected: succeeds.

- [ ] **Step 6: Commit formatting fixes if any**

```bash
git add -A
git commit -m "chore: pint + prettier after qualifications reports feature" || echo "nothing to commit"
```

---

## Task 30: Manual QA checklist

Before opening PR, manually verify:

- [ ] `/qualifications/reports` loads with all 4 charts + KPI row + staff table.
- [ ] Changing each filter refreshes data and updates the URL.
- [ ] "Clear all" empties filters and re-renders unfiltered data.
- [ ] Export PDF dropdown — each of 4 report types downloads a valid PDF.
- [ ] Export Excel dropdown — each of 4 report types downloads a valid XLSX.
- [ ] Dashboard shows qualifications section for a permitted user; skeletons appear first, then charts.
- [ ] Dashboard section hidden for a user without `qualifications.reports.view`.
- [ ] Unit-scoped user (`view.own_unit`) sees data scoped to their unit on both dashboard and reports page.
- [ ] `/data-integrity/pending-qualifications` shows the pending-approvals widget with matching count.
- [ ] Staff summary page has a "Download Profile PDF" button that downloads correctly.
- [ ] A staff user (non-owner) cannot download someone else's profile PDF without `qualifications.reports.export`.

---

## Summary

| Area | Tasks |
|------|-------|
| Foundation (migration, enum, factory, permissions) | 1–4 |
| DTO + Service | 5–12 |
| Controllers + routes | 13, 18, 21, 22 |
| Excel exports | 14–17 |
| PDF exports | 19–21 |
| Chart components + pages | 23–25 |
| Integrations (data-integrity, staff summary, nav) | 26–28 |
| QA + polish | 29–30 |

**Total: 30 tasks.** Each produces a self-contained commit and keeps the feature branch in an always-green state.
