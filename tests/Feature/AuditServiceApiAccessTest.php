<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AuditServiceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuditServiceApiAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('staff_statistics');
    }

    public function test_seeder_creates_audit_service_user(): void
    {
        $this->seed(AuditServiceUserSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'audit-service@audit.gov.gh',
            'name' => 'Audit Service Website',
        ]);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(AuditServiceUserSeeder::class);
        $this->seed(AuditServiceUserSeeder::class);

        $this->assertSame(1, User::where('email', 'audit-service@audit.gov.gh')->count());
    }

    public function test_token_with_ability_can_read_statistics(): void
    {
        $user = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($user, ['staff-statistics:read']);

        $this->getJson('/api/staff-statistics')
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_staff',
                'regional_offices',
                'district_offices',
                'field_staff',
                'professionals',
                'professions',
            ]);
    }

    public function test_token_without_ability_is_forbidden(): void
    {
        $user = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($user, ['some-other-ability']);

        $this->getJson('/api/staff-statistics')->assertStatus(403);
    }

    public function test_request_without_token_is_unauthorized(): void
    {
        $this->getJson('/api/staff-statistics')->assertStatus(401);
    }
}
