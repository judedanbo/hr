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

    public function test_successful_request_is_logged(): void
    {
        $user = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($user, ['staff-statistics:read']);

        $this->getJson('/api/staff-statistics')->assertStatus(200);

        $this->assertDatabaseHas('api_logs', [
            'method' => 'GET',
            'path' => 'api/staff-statistics',
            'status' => 200,
            'user_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_request_is_logged_with_null_user(): void
    {
        $this->getJson('/api/staff-statistics')->assertStatus(401);

        $this->assertDatabaseHas('api_logs', [
            'path' => 'api/staff-statistics',
            'status' => 401,
            'user_id' => null,
        ]);
    }

    public function test_request_with_real_token_logs_token_name_and_duration(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $user->createToken('Audit Service Website', ['staff-statistics:read'])->plainTextToken;

        $this->getJson('/api/staff-statistics', ['Authorization' => "Bearer {$plainTextToken}"])
            ->assertStatus(200);

        $log = \App\Models\ApiLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('Audit Service Website', $log->token_name);
        $this->assertSame($user->id, $log->user_id);
        $this->assertNotNull($log->duration_ms);
        // The plaintext token must never be persisted anywhere on the row.
        $this->assertStringNotContainsString($plainTextToken, json_encode($log->getAttributes()));
    }

    public function test_statistics_token_cannot_reach_other_api_routes(): void
    {
        $user = User::factory()->create();

        \Laravel\Sanctum\Sanctum::actingAs($user, ['staff-statistics:read']);

        $this->getJson('/api/user')->assertStatus(403);
        $this->getJson('/api/staff-search/options')->assertStatus(403);
    }
}
