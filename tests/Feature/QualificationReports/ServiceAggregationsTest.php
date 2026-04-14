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
}
