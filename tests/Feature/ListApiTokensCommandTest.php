<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ListApiTokensCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_issued_tokens(): void
    {
        $user = User::factory()->create(['email' => 'svc@example.test']);
        $user->createToken('Audit Service Website', ['staff-statistics:read']);

        $exitCode = Artisan::call('app:list-api-tokens');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Audit Service Website', $output);
        $this->assertStringContainsString('staff-statistics:read', $output);
    }

    public function test_it_filters_tokens_by_email(): void
    {
        $alice = User::factory()->create(['email' => 'alice@example.test']);
        $bob = User::factory()->create(['email' => 'bob@example.test']);
        $alice->createToken('Alice Token', ['staff-statistics:read']);
        $bob->createToken('Bob Token', ['staff-statistics:read']);

        $exitCode = Artisan::call('app:list-api-tokens', ['email' => 'alice@example.test']);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Alice Token', $output);
        $this->assertStringNotContainsString('Bob Token', $output);
    }

    public function test_it_reports_empty_state_when_no_tokens_exist(): void
    {
        $this->artisan('app:list-api-tokens')
            ->expectsOutput('No API tokens issued.')
            ->assertExitCode(0);
    }

    public function test_it_fails_for_unknown_email(): void
    {
        $this->artisan('app:list-api-tokens', ['email' => 'nobody@example.test'])
            ->assertExitCode(1);
    }
}
