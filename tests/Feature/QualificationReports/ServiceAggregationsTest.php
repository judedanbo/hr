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
}
