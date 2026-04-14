<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Status;
use App\Models\User;
use App\Services\InstitutionDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InstitutionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->institution = Institution::factory()->create();
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('institution.show', $this->institution));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Institution/Show')
            ->has('institution')
            ->has('overview')
            ->has('trends')
            ->has('analytics')
            ->has('action_items')
            ->has('departments')
            ->has('can')
        );
    }

    public function test_dashboard_returns_correct_overview_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('institution.show', $this->institution));

        $response->assertInertia(fn ($page) => $page
            ->has('overview.active_staff')
            ->has('overview.male_count')
            ->has('overview.female_count')
            ->has('overview.retired_count')
            ->has('overview.new_hires_this_year')
            ->has('overview.avg_tenure_years')
            ->has('overview.departments_count')
            ->has('overview.divisions_count')
            ->has('overview.units_count')
        );
    }

    public function test_dashboard_returns_correct_analytics_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('institution.show', $this->institution));

        $response->assertInertia(fn ($page) => $page
            ->has('analytics.gender')
            ->has('analytics.age_distribution')
            ->has('analytics.status')
            ->has('analytics.tenure_distribution')
        );
    }

    public function test_dashboard_returns_correct_trends_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('institution.show', $this->institution));

        $response->assertInertia(fn ($page) => $page
            ->has('trends.recruitment')
            ->has('trends.separations')
        );
    }

    public function test_dashboard_returns_action_items(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('institution.show', $this->institution));

        $response->assertInertia(fn ($page) => $page
            ->has('action_items', 7) // 7 action items: promotion, retirement, units, pictures, ranks, multiple-units, without-gender
            ->where('action_items.0.id', 'due-promotion')
            ->where('action_items.1.id', 'nearing-retirement')
        );
    }

    public function test_staff_filter_endpoint_returns_active_staff(): void
    {
        // Create some staff
        $person = Person::factory()->create(['gender' => 'M']);
        $staff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'person_id' => $person->id,
        ]);
        Status::factory()->create([
            'staff_id' => $staff->id,
            'institution_id' => $this->institution->id,
            'status' => 'A',
            'start_date' => now()->subYear(),
            'end_date' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('institution.staff-filter', [
                'institution' => $this->institution->id,
                'filter' => 'active',
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'staff',
            'filter',
            'count',
        ]);
    }

    public function test_staff_filter_endpoint_filters_by_gender(): void
    {
        // Create male staff
        $malePerson = Person::factory()->create(['gender' => 'M']);
        $maleStaff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'person_id' => $malePerson->id,
        ]);
        Status::factory()->create([
            'staff_id' => $maleStaff->id,
            'institution_id' => $this->institution->id,
            'status' => 'A',
            'end_date' => null,
        ]);

        // Create female staff
        $femalePerson = Person::factory()->create(['gender' => 'F']);
        $femaleStaff = InstitutionPerson::factory()->create([
            'institution_id' => $this->institution->id,
            'person_id' => $femalePerson->id,
        ]);
        Status::factory()->create([
            'staff_id' => $femaleStaff->id,
            'institution_id' => $this->institution->id,
            'status' => 'A',
            'end_date' => null,
        ]);

        // Filter by male
        $response = $this->actingAs($this->user)
            ->get(route('institution.staff-filter', [
                'institution' => $this->institution->id,
                'filter' => 'gender',
                'value' => 'M',
            ]));

        $response->assertStatus(200);
        $response->assertJsonPath('filter', 'gender');
    }

    public function test_dashboard_service_caches_overview_data(): void
    {
        $service = new InstitutionDashboardService;

        // Clear cache first
        Cache::forget("institution.{$this->institution->id}.dashboard.overview");

        // First call should create cache
        $data1 = $service->getOverviewStats($this->institution);

        // Verify cache exists
        $this->assertTrue(Cache::has("institution.{$this->institution->id}.dashboard.overview"));

        // Second call should use cache
        $data2 = $service->getOverviewStats($this->institution);

        $this->assertEquals($data1, $data2);
    }

    public function test_dashboard_service_clear_cache(): void
    {
        $service = new InstitutionDashboardService;

        // Create cache entries
        $service->getDashboardData($this->institution);

        // Verify caches exist
        $this->assertTrue(Cache::has("institution.{$this->institution->id}.dashboard.overview"));

        // Clear cache
        $service->clearCache($this->institution);

        // Verify caches are cleared
        $this->assertFalse(Cache::has("institution.{$this->institution->id}.dashboard.overview"));
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('institution.show', $this->institution));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_handles_institution_with_no_data(): void
    {
        $emptyInstitution = Institution::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('institution.show', $emptyInstitution));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('overview.active_staff', 0)
            ->where('overview.male_count', 0)
            ->where('overview.female_count', 0)
        );
    }
}
