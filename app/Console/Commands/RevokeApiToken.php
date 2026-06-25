<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class RevokeApiToken extends Command
{
    protected $signature = 'app:revoke-api-token
        {id? : The token id to revoke (see app:list-api-tokens)}
        {--all-for= : Revoke ALL tokens for the user with this email}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Revoke a Sanctum personal access token by id, or all tokens for a user.';

    public function handle(): int
    {
        $id = $this->argument('id');
        $email = $this->option('all-for');

        if (($id === null) === ($email === null)) {
            $this->error('Provide either a token id or --all-for=email, not both.');

            return self::FAILURE;
        }

        return $email !== null
            ? $this->revokeAllForUser($email)
            : $this->revokeById($id);
    }

    protected function revokeAllForUser(string $email): int
    {
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email {$email}.");

            return self::FAILURE;
        }

        $count = $user->tokens()->count();

        if ($count === 0) {
            $this->info("No tokens to revoke for {$email}.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Revoke all {$count} token(s) for {$email}?")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $user->tokens()->delete();

        $this->info("Revoked {$count} token(s) for {$email}.");

        return self::SUCCESS;
    }

    protected function revokeById(string $id): int
    {
        $token = PersonalAccessToken::with('tokenable')->find($id);

        if ($token === null) {
            $this->error("No token found with id {$id}.");

            return self::FAILURE;
        }

        $owner = $token->tokenable?->email ?? '—';
        $abilities = implode(', ', $token->abilities ?? []);
        $this->line("Token #{$token->getKey()} — {$owner} — {$token->name} — [{$abilities}]");

        if (! $this->option('force') && ! $this->confirm("Revoke token #{$token->getKey()}?")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $token->delete();

        $this->info("Revoked token #{$id}.");

        return self::SUCCESS;
    }
}
