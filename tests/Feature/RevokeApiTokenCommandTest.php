<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class RevokeApiTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_revokes_a_token_by_id_with_force(): void
    {
        $user = User::factory()->create();
        $keep = $user->createToken('Keep', ['staff-statistics:read'])->accessToken;
        $revoke = $user->createToken('Revoke', ['staff-statistics:read'])->accessToken;

        $this->artisan('app:revoke-api-token', ['id' => $revoke->getKey(), '--force' => true])
            ->assertExitCode(0);

        $this->assertNull(PersonalAccessToken::find($revoke->getKey()));
        $this->assertNotNull(PersonalAccessToken::find($keep->getKey()));
    }

    public function test_it_revokes_a_token_by_id_after_confirmation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Revoke', ['staff-statistics:read'])->accessToken;

        $this->artisan('app:revoke-api-token', ['id' => $token->getKey()])
            ->expectsConfirmation("Revoke token #{$token->getKey()}?", 'yes')
            ->assertExitCode(0);

        $this->assertNull(PersonalAccessToken::find($token->getKey()));
    }

    public function test_it_keeps_the_token_when_confirmation_declined(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Keep', ['staff-statistics:read'])->accessToken;

        $this->artisan('app:revoke-api-token', ['id' => $token->getKey()])
            ->expectsConfirmation("Revoke token #{$token->getKey()}?", 'no')
            ->expectsOutput('Aborted.')
            ->assertExitCode(0);

        $this->assertNotNull(PersonalAccessToken::find($token->getKey()));
    }

    public function test_it_revokes_all_tokens_for_a_user(): void
    {
        $target = User::factory()->create(['email' => 'target@example.test']);
        $other = User::factory()->create(['email' => 'other@example.test']);
        $target->createToken('A', ['staff-statistics:read']);
        $target->createToken('B', ['staff-statistics:read']);
        $other->createToken('C', ['staff-statistics:read']);

        $this->artisan('app:revoke-api-token', ['--all-for' => 'target@example.test', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(0, $target->fresh()->tokens()->count());
        $this->assertSame(1, $other->fresh()->tokens()->count());
    }

    public function test_it_reports_when_user_has_no_tokens_to_revoke(): void
    {
        User::factory()->create(['email' => 'empty@example.test']);

        $this->artisan('app:revoke-api-token', ['--all-for' => 'empty@example.test', '--force' => true])
            ->expectsOutput('No tokens to revoke for empty@example.test.')
            ->assertExitCode(0);
    }

    public function test_it_fails_for_unknown_id(): void
    {
        $this->artisan('app:revoke-api-token', ['id' => 999, '--force' => true])
            ->assertExitCode(1);
    }

    public function test_it_fails_for_unknown_all_for_email(): void
    {
        $this->artisan('app:revoke-api-token', ['--all-for' => 'nobody@example.test', '--force' => true])
            ->assertExitCode(1);
    }

    public function test_it_fails_when_both_id_and_all_for_supplied(): void
    {
        $this->artisan('app:revoke-api-token', ['id' => 1, '--all-for' => 'someone@example.test'])
            ->assertExitCode(1);
    }

    public function test_it_fails_when_neither_id_nor_all_for_supplied(): void
    {
        $this->artisan('app:revoke-api-token')
            ->assertExitCode(1);
    }
}
