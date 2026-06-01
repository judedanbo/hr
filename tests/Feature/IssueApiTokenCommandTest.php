<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueApiTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_issues_a_token_with_the_requested_ability(): void
    {
        $user = User::factory()->create(['email' => 'svc@example.test']);

        $this->artisan('app:issue-api-token', ['email' => 'svc@example.test'])
            ->assertExitCode(0);

        $token = $user->fresh()->tokens()->first();

        $this->assertNotNull($token);
        $this->assertSame('Audit Service Website', $token->name);
        $this->assertContains('staff-statistics:read', $token->abilities);
    }

    public function test_it_respects_custom_name_and_ability_options(): void
    {
        $user = User::factory()->create(['email' => 'svc2@example.test']);

        $this->artisan('app:issue-api-token', [
            'email' => 'svc2@example.test',
            '--name' => 'My Token',
            '--ability' => ['reports:read', 'staff-statistics:read'],
        ])->assertExitCode(0);

        $token = $user->fresh()->tokens()->first();

        $this->assertSame('My Token', $token->name);
        $this->assertContains('reports:read', $token->abilities);
        $this->assertContains('staff-statistics:read', $token->abilities);
    }

    public function test_it_fails_for_unknown_email(): void
    {
        $this->artisan('app:issue-api-token', ['email' => 'nobody@example.test'])
            ->assertExitCode(1);

        $this->assertSame(0, \Laravel\Sanctum\PersonalAccessToken::count());
    }
}
