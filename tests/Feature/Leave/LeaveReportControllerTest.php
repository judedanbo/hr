<?php

namespace Tests\Feature\Leave;

use App\Models\LeaveYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $guestUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['password_change_at' => now()]);
        $this->superAdmin->assignRole('super-administrator');

        $this->guestUser = User::factory()->create(['password_change_at' => now()]);

        LeaveYear::factory()->active()->create();
    }

    public function test_index_requires_permission(): void
    {
        $this->actingAs($this->guestUser)->get(route('leave-reports.index'))->assertForbidden();
    }

    public function test_index_renders_the_report(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('leave-reports.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Leave/Reports/Index')
                ->has('utilisationByType')
                ->has('kpis')
                ->has('compliance')
                ->has('filterOptions'));
    }

    public function test_excel_export_downloads(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('leave-reports.export.excel', ['type' => 'balances']));

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_pdf_export_downloads(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('leave-reports.export.pdf', ['type' => 'utilisation']));

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_export_requires_permission(): void
    {
        $this->actingAs($this->guestUser)
            ->get(route('leave-reports.export.excel'))
            ->assertForbidden();
    }
}
