<?php

namespace Tests\Feature\Appraisal;

use App\Enums\AppraisalStatusEnum;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AppraisalReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');
    }

    public function test_report_index_requires_permission(): void
    {
        $guest = User::factory()->create(['password_change_at' => now()]);

        $this->actingAs($guest)->get(route('appraisal.report.index'))->assertForbidden();
    }

    public function test_report_index_returns_distributions(): void
    {
        $cycle = AppraisalCycle::factory()->create();
        Appraisal::factory()->count(2)->create(['appraisal_cycle_id' => $cycle->id, 'status' => AppraisalStatusEnum::Completed, 'overall_band' => 'Outstanding']);
        Appraisal::factory()->create(['appraisal_cycle_id' => $cycle->id, 'status' => AppraisalStatusEnum::SelfAppraisal]);

        $this->actingAs($this->superAdmin)
            ->get(route('appraisal.report.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Appraisal/Report/Index')
                ->where('summary.total', 3)
                ->where('summary.completed', 2)
                ->has('statusDistribution')
                ->has('bandDistribution')
                ->has('byUnit'));
    }

    public function test_export_downloads_excel(): void
    {
        Excel::fake();

        Appraisal::factory()->create(['appraisal_cycle_id' => AppraisalCycle::factory()]);

        $this->actingAs($this->superAdmin)
            ->get(route('appraisal.report.export'))
            ->assertStatus(200);

        Excel::assertDownloaded('appraisals.xlsx');
    }

    public function test_pdf_renders_for_authorized_user(): void
    {
        $appraisal = Appraisal::factory()->create(['appraisal_cycle_id' => AppraisalCycle::factory()]);

        $response = $this->actingAs($this->superAdmin)->get(route('appraisal.pdf', $appraisal));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
