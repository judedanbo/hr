<?php

namespace Tests\Feature\QualificationReports;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
    }

    public function test_widgets_endpoint_returns_expected_shape(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['qualifications.reports.view', 'qualifications.reports.view.all']);

        $this->actingAs($user->fresh())
            ->getJson('/dashboard/qualifications-widgets')
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
        $this->actingAs($user)
            ->getJson('/dashboard/qualifications-widgets')
            ->assertForbidden();
    }
}
