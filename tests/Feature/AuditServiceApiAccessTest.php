<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AuditServiceUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceApiAccessTest extends TestCase
{
    use RefreshDatabase;

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
}
