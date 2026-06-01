<?php

namespace Tests\Feature;

use App\Enums\DistrictTypeEnum;
use App\Enums\OfficeTypeEnum;
use App\Enums\QualificationLevelEnum;
use App\Enums\StaffTypeEnum;
use App\Models\District;
use App\Models\Institution;
use App\Models\InstitutionPerson;
use App\Models\Office;
use App\Models\Qualification;
use App\Models\Region;
use App\Models\StaffType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StaffStatisticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institution $institution;

    protected District $district;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('staff_statistics');

        $this->user = User::factory()->create();
        $this->institution = Institution::factory()->create();

        Model::unguarded(function () {
            $region = Region::create([
                'name' => 'Test Region',
                'capital' => 'Test Capital',
            ]);

            $this->district = District::create([
                'name' => 'Test District',
                'region_id' => $region->id,
                'capital' => 'Test District Capital',
                'district_type' => DistrictTypeEnum::DISTRICT,
            ]);
        });
    }

    public function test_endpoint_requires_authentication(): void
    {
        $response = $this->get('/api/staff-statistics');

        $response->assertRedirect('/login');
    }

    public function test_endpoint_returns_expected_json_structure(): void
    {
        $response = $this->actingAs($this->user)->get('/api/staff-statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_staff',
                'regional_offices',
                'district_offices',
                'field_staff',
                'professionals',
                'professions',
            ]);
    }

    public function test_endpoint_returns_correct_statistics(): void
    {
        // Offices: 2 regional, 3 district, 1 headquarters
        Office::create(['name' => 'Greater Accra Regional', 'type' => OfficeTypeEnum::REGIONAL, 'district_id' => $this->district->id]);
        Office::create(['name' => 'Ashanti Regional', 'type' => OfficeTypeEnum::REGIONAL, 'district_id' => $this->district->id]);
        Office::create(['name' => 'Tema District', 'type' => OfficeTypeEnum::DISTRICT, 'district_id' => $this->district->id]);
        Office::create(['name' => 'Kumasi District', 'type' => OfficeTypeEnum::DISTRICT, 'district_id' => $this->district->id]);
        Office::create(['name' => 'Cape Coast District', 'type' => OfficeTypeEnum::DISTRICT, 'district_id' => $this->district->id]);
        Office::create(['name' => 'HQ', 'type' => OfficeTypeEnum::HEADQUARTERS, 'district_id' => $this->district->id]);

        // 4 active staff
        $activeStaff = collect();
        for ($i = 0; $i < 4; $i++) {
            $staff = InstitutionPerson::factory()->create(['institution_id' => $this->institution->id]);
            $staff->statuses()->create([
                'status' => 'A',
                'start_date' => now()->subYear(),
                'end_date' => null,
                'institution_id' => $this->institution->id,
            ]);
            $activeStaff->push($staff);
        }

        // 1 separated/non-active staff (status != A)
        $separated = InstitutionPerson::factory()->create(['institution_id' => $this->institution->id]);
        $separated->statuses()->create([
            'status' => 'E',
            'start_date' => now()->subMonth(),
            'end_date' => null,
            'institution_id' => $this->institution->id,
        ]);

        // 2 of the active staff are field staff (FS), 1 is FSS
        StaffType::create([
            'staff_id' => $activeStaff[0]->id,
            'staff_type' => StaffTypeEnum::Field->value,
            'start_date' => now()->subYear(),
            'end_date' => null,
        ]);
        StaffType::create([
            'staff_id' => $activeStaff[1]->id,
            'staff_type' => StaffTypeEnum::Field->value,
            'start_date' => now()->subYear(),
            'end_date' => null,
        ]);
        StaffType::create([
            'staff_id' => $activeStaff[2]->id,
            'staff_type' => StaffTypeEnum::SupportService->value,
            'start_date' => now()->subYear(),
            'end_date' => null,
        ]);
        StaffType::create([
            'staff_id' => $activeStaff[3]->id,
            'staff_type' => StaffTypeEnum::Supporting->value,
            'start_date' => now()->subYear(),
            'end_date' => null,
        ]);

        // 2 active staff have professional qualifications across 2 distinct professions
        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Professional)->create([
            'person_id' => $activeStaff[0]->person_id,
            'course' => 'Accountancy',
        ]);
        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Professional)->create([
            'person_id' => $activeStaff[1]->person_id,
            'course' => 'Auditing',
        ]);
        // Duplicate profession - should not increase distinct count
        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Professional)->create([
            'person_id' => $activeStaff[2]->person_id,
            'course' => 'Accountancy',
        ]);
        // Non-professional qualification should be ignored
        Qualification::factory()->approved()->atLevel(QualificationLevelEnum::Degree)->create([
            'person_id' => $activeStaff[3]->person_id,
            'course' => 'Engineering',
        ]);
        // Pending professional qualification should be ignored
        Qualification::factory()->pending()->atLevel(QualificationLevelEnum::Professional)->create([
            'person_id' => $activeStaff[3]->person_id,
            'course' => 'Law',
        ]);

        Cache::forget('staff_statistics');

        $response = $this->actingAs($this->user)->get('/api/staff-statistics');

        $response->assertStatus(200)
            ->assertExactJson([
                'total_staff' => 4,
                'regional_offices' => 2,
                'district_offices' => 3,
                'field_staff' => 3,
                'professionals' => 3,
                'professions' => 2,
            ]);
    }
}
