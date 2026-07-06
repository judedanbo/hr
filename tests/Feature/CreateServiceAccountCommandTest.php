<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateServiceAccountCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_locked_service_account(): void
    {
        $this->artisan('app:create-service-account', ['email' => 'audit@service.local'])
            ->assertExitCode(0);

        $user = User::where('email', 'audit@service.local')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->is_service);
        $this->assertNotNull($user->password_change_at);
        $this->assertCount(0, $user->roles);
    }

    public function test_it_defaults_the_name_to_the_titlecased_email_local_part(): void
    {
        $this->artisan('app:create-service-account', ['email' => 'audit-service@service.local'])
            ->assertExitCode(0);

        $user = User::where('email', 'audit-service@service.local')->first();

        $this->assertSame('Audit Service', $user->name);
    }

    public function test_it_respects_a_custom_name_option(): void
    {
        $this->artisan('app:create-service-account', [
            'email' => 'svc@service.local',
            '--name' => 'Reporting Bot',
        ])->assertExitCode(0);

        $this->assertSame('Reporting Bot', User::where('email', 'svc@service.local')->first()->name);
    }

    public function test_it_fails_when_the_email_already_exists(): void
    {
        User::factory()->create(['email' => 'taken@service.local']);

        $this->artisan('app:create-service-account', ['email' => 'taken@service.local'])
            ->assertExitCode(1);

        $this->assertSame(1, User::where('email', 'taken@service.local')->count());
    }

    public function test_it_fails_for_an_invalid_email(): void
    {
        $this->artisan('app:create-service-account', ['email' => 'not-an-email'])
            ->assertExitCode(1);

        $this->assertSame(0, User::where('email', 'not-an-email')->count());
    }
}
