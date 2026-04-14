<?php

namespace Tests\Feature\QualificationReports;

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
                ->has('kpis')
                ->has('filterOptions')
                ->has('filters')
            );
    }
}
