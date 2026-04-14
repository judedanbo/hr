<?php

namespace Tests\Feature\QualificationReports;

use App\DataTransferObjects\QualificationReportFilter;
use App\Enums\QualificationLevelEnum;
use App\Enums\TransferStatusEnum;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Qualification;
use App\Models\Unit;
use App\Services\QualificationReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $result = $service->levelDistribution(new QualificationReportFilter);

        $this->assertSame(1, $result['degree'] ?? 0, 'Only the Degree-only person should count under Degree');
        $this->assertSame(1, $result['masters'] ?? 0, 'Only the Masters-holder counts once under Masters');
    }

    public function test_level_distribution_ignores_pending_qualifications(): void
    {
        $person = Person::factory()->create();
        Qualification::factory()->for($person)->pending()->atLevel(QualificationLevelEnum::Masters)->create();

        $result = app(QualificationReportService::class)->levelDistribution(new QualificationReportFilter);

        $this->assertSame(0, array_sum($result));
    }

    public function test_level_distribution_respects_level_filter(): void
    {
        $person = Person::factory()->create();
        Qualification::factory()->for($person)->approved()->atLevel(QualificationLevelEnum::Masters)->create();

        $result = app(QualificationReportService::class)->levelDistribution(
            new QualificationReportFilter(level: 'degree')
        );

        $this->assertSame(0, $result['masters'] ?? 0);
        $this->assertSame(0, $result['degree'] ?? 0);
    }

    public function test_by_unit_groups_highest_qualifications_by_current_unit(): void
    {
        $unitA = Unit::factory()->create(['name' => 'Unit A']);

        $person = Person::factory()->create();
        $instPerson = InstitutionPerson::factory()->for($person)->create();

        DB::table('staff_unit')->insert([
            'staff_id' => $instPerson->id,
            'unit_id' => $unitA->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => null,
            'status' => TransferStatusEnum::Pending->value,
        ]);

        Qualification::factory()->for($person)->approved()->atLevel(QualificationLevelEnum::Degree)->create();
        Qualification::factory()->for($person)->approved()->atLevel(QualificationLevelEnum::Masters)->create();

        $result = app(QualificationReportService::class)->byUnit(new QualificationReportFilter);

        $this->assertArrayHasKey('Unit A', $result);
        $this->assertSame(1, $result['Unit A']['masters'] ?? 0, 'Person should be counted once at their highest level (Masters)');
        $this->assertSame(0, $result['Unit A']['degree'] ?? 0, 'Person should NOT be double-counted under Degree');
    }

    public function test_by_unit_ignores_ended_unit_assignments(): void
    {
        $unitA = Unit::factory()->create(['name' => 'Ended Unit']);

        $person = Person::factory()->create();
        $instPerson = InstitutionPerson::factory()->for($person)->create();

        DB::table('staff_unit')->insert([
            'staff_id' => $instPerson->id,
            'unit_id' => $unitA->id,
            'start_date' => now()->subYears(2)->toDateString(),
            'end_date' => now()->subYear()->toDateString(),  // ended assignment — should be excluded
            'status' => TransferStatusEnum::Pending->value,
        ]);

        Qualification::factory()->for($person)->approved()->atLevel(QualificationLevelEnum::Masters)->create();

        $result = app(QualificationReportService::class)->byUnit(new QualificationReportFilter);

        $this->assertArrayNotHasKey('Ended Unit', $result);
    }

    public function test_top_institutions_normalizes_casing_and_trimming(): void
    {
        Qualification::factory()->approved()->create(['institution' => 'University of Ghana']);
        Qualification::factory()->approved()->create(['institution' => ' university of ghana ']);
        Qualification::factory()->approved()->create(['institution' => 'KNUST']);

        $result = app(QualificationReportService::class)->topInstitutions(new QualificationReportFilter, 10);

        $byName = collect($result)->keyBy('name');
        $uog = $byName->first(fn ($r) => stripos($r['name'], 'University of Ghana') !== false);
        $this->assertNotNull($uog, 'Expected a University of Ghana entry');
        $this->assertSame(2, $uog['count'], 'Casing/trim variants should collapse to one group');
    }

    public function test_top_institutions_respects_limit(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
            Qualification::factory()->approved()->create(['institution' => $name]);
        }
        $result = app(QualificationReportService::class)->topInstitutions(new QualificationReportFilter, 3);
        $this->assertCount(3, $result);
    }

    public function test_trend_by_year_returns_year_counts(): void
    {
        Qualification::factory()->approved()->count(3)->create(['year' => '2018']);
        Qualification::factory()->approved()->count(2)->create(['year' => '2020']);

        $result = app(QualificationReportService::class)->trendByYear(new QualificationReportFilter);

        $this->assertSame(3, $result[2018] ?? 0);
        $this->assertSame(2, $result[2020] ?? 0);
    }

    public function test_trend_by_year_skips_non_numeric_years(): void
    {
        Qualification::factory()->approved()->create(['year' => '']);
        Qualification::factory()->approved()->create(['year' => null]);
        Qualification::factory()->approved()->create(['year' => '2019']);

        $result = app(QualificationReportService::class)->trendByYear(new QualificationReportFilter);

        $this->assertSame(1, $result[2019] ?? 0);
        $this->assertArrayNotHasKey(0, $result);
    }

    public function test_pending_approvals_stats_returns_count_and_sparkline(): void
    {
        Qualification::factory()->pending()->count(3)->create();
        Qualification::factory()->approved()->count(5)->create();  // should NOT be counted

        $result = app(QualificationReportService::class)->pendingApprovalsStats();

        $this->assertSame(3, $result['count']);
        $this->assertCount(30, $result['sparkline']);
        $this->assertContainsOnly('int', $result['sparkline']);
    }

    public function test_pending_approvals_sparkline_reflects_today_submissions(): void
    {
        Qualification::factory()->pending()->count(2)->create();

        $result = app(QualificationReportService::class)->pendingApprovalsStats();

        // last element is today
        $this->assertSame(2, end($result['sparkline']));
    }
}
