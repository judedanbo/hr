<?php

namespace Tests\Feature\QualificationReports;

use App\Enums\QualificationLevelEnum;
use App\Models\InstitutionPerson;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
    }

    public function test_authenticated_user_without_permission_is_denied(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/qualifications/reports')->assertForbidden();
    }

    public function test_user_with_permission_can_view_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Qualification/Reports/Index')
                ->has('levelDistribution')
                ->has('byUnit')
                ->has('topInstitutions')
                ->has('trendByYear')
                ->has('staffList')
                ->has('kpis.totalQualifications.value')
                ->has('kpis.staffCovered.value')
                ->has('kpis.staffCovered.total')
                ->has('kpis.pending.value')
                ->has('kpis.pending.oldestDays')
                ->has('kpis.withoutQualifications.value')
                ->has('kpis.withoutQualifications.total')
                ->has('filterOptions')
                ->has('filters')
            );
    }

    public function test_kpis_payload_carries_active_staff_total_and_numeric_values(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        // 3 active staff, 1 separated
        InstitutionPerson::factory()->count(3)->create(['end_date' => null]);
        InstitutionPerson::factory()->create(['end_date' => now()->subMonth()]);

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports')
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.staffCovered.total', 3)
                ->where('kpis.withoutQualifications.total', 3)
                ->where('kpis.pending.oldestDays', null)
            );
    }

    public function test_total_qualifications_kpi_respects_year_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        Qualification::factory()->approved()->count(2)->create(['year' => '2020']);
        Qualification::factory()->approved()->count(3)->create(['year' => '2023']);

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports?year_from=2022')
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.totalQualifications.value', 3)
            );
    }

    public function test_total_qualifications_kpi_respects_level_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Degree)->count(4)->create();
        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Masters)->count(2)->create();

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports?level=' . QualificationLevelEnum::Masters->value)
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.totalQualifications.value', 2)
            );
    }

    public function test_pending_kpi_ignores_status_filter_so_pending_count_stays_meaningful(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        Qualification::factory()->pending()->count(3)->create();
        Qualification::factory()->approved()->count(5)->create();

        // Even though the user filters by status=Approved, the Pending card
        // should still count pending qualifications (matching other filters).
        $this->actingAs($user->fresh())
            ->get('/qualifications/reports?status=approved')
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.pending.value', 3)
                ->where('kpis.totalQualifications.value', 5)
            );
    }

    public function test_staff_covered_kpi_respects_level_filter(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        $personA = \App\Models\Person::factory()->create();
        $personB = \App\Models\Person::factory()->create();

        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Degree)->for($personA)->create();
        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Masters)->for($personB)->create();

        $this->actingAs($user->fresh())
            ->get('/qualifications/reports?level=' . QualificationLevelEnum::Masters->value)
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.staffCovered.value', 1)
            );
    }
}
