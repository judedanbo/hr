<?php

namespace Tests\Feature\QualificationReports;

use App\Models\InstitutionPerson;
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
}
