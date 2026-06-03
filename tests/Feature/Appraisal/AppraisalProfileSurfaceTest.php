<?php

namespace Tests\Feature\Appraisal;

use App\Enums\AppraisalStatusEnum;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\InstitutionPerson;
use App\Models\Person;
use App\Models\Status;
use App\Services\StaffProfileProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalProfileSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_payload_includes_latest_appraisal(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);

        $cycle = AppraisalCycle::factory()->create();
        Appraisal::factory()->create([
            'appraisal_cycle_id' => $cycle->id,
            'staff_id' => $staff->id,
            'status' => AppraisalStatusEnum::Completed,
            'overall_score' => 88.5,
            'overall_band' => 'Exceeds Expectations',
        ]);

        $payload = app(StaffProfileProvider::class)->forPerson($person->id);

        $this->assertNotNull($payload['latest_appraisal']);
        $this->assertSame(88.5, $payload['latest_appraisal']['overall_score']);
        $this->assertSame('Exceeds Expectations', $payload['latest_appraisal']['overall_band']);
    }

    public function test_profile_payload_latest_appraisal_null_when_none(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $person = Person::factory()->create();
        $staff = InstitutionPerson::factory()->create(['person_id' => $person->id]);
        Status::factory()->active()->create(['staff_id' => $staff->id, 'institution_id' => $staff->institution_id]);

        $payload = app(StaffProfileProvider::class)->forPerson($person->id);

        $this->assertNull($payload['latest_appraisal']);
    }
}
