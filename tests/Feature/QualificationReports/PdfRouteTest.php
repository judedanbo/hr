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

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\QualificationReportPermissionSeeder::class);
    }

    private function permittedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo([
            'qualifications.reports.view',
            'qualifications.reports.view.all',
            'qualifications.reports.export',
        ]);

        return $user->fresh();
    }

    public function test_pdf_list_returns_pdf_content_type(): void
    {
        Qualification::factory()->approved()->count(2)->create();
        $this->actingAs($this->permittedUser())
            ->get('/qualifications/reports/export/pdf?type=list')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_by_unit_ok(): void
    {
        $this->actingAs($this->permittedUser())
            ->get('/qualifications/reports/export/pdf?type=by_unit')
            ->assertOk();
    }

    public function test_pdf_by_level_ok(): void
    {
        $this->actingAs($this->permittedUser())
            ->get('/qualifications/reports/export/pdf?type=by_level')
            ->assertOk();
    }

    public function test_pdf_gaps_ok(): void
    {
        $this->actingAs($this->permittedUser())
            ->get('/qualifications/reports/export/pdf?type=gaps')
            ->assertOk();
    }

    public function test_staff_profile_pdf_ok(): void
    {
        $person = Person::factory()->create();
        Qualification::factory()->for($person)->approved()->count(2)->create();

        $this->actingAs($this->permittedUser())
            ->get("/qualifications/reports/staff/{$person->id}/profile.pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_staff_profile_pdf_denies_non_owner_without_export_permission(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create();
        // No export permission, not the owner

        $this->actingAs($user)
            ->get("/qualifications/reports/staff/{$person->id}/profile.pdf")
            ->assertForbidden();
    }

    public function test_pdf_route_rejects_invalid_type(): void
    {
        $this->actingAs($this->permittedUser())
            ->get('/qualifications/reports/export/pdf?type=bogus')
            ->assertStatus(302);
    }
}
