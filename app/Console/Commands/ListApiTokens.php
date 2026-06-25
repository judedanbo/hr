<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class ListApiTokens extends Command
{
    protected $signature = 'app:list-api-tokens
        {email? : Optional user email to filter tokens by}';

    protected $description = 'List issued Sanctum personal access tokens and their abilities (secrets are never shown).';

    public function handle(): int
    {
        $query = PersonalAccessToken::query()->with('tokenable')->latest('id');

        if ($this->argument('email') !== null) {
            $user = User::where('email', $this->argument('email'))->first();

            if ($user === null) {
                $this->error("No user found with email {$this->argument('email')}.");

                return self::FAILURE;
            }

            $query->where('tokenable_id', $user->getKey())
                ->where('tokenable_type', $user->getMorphClass());
        }

        $tokens = $query->get();

        if ($tokens->isEmpty()) {
            $this->info('No API tokens issued.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Owner', 'Name', 'Abilities', 'Last used', 'Created'],
            $tokens->map(fn (PersonalAccessToken $token): array => [
                $token->getKey(),
                $token->tokenable?->email ?? '—',
                $token->name,
                implode(', ', $token->abilities ?? []),
                $token->last_used_at?->diffForHumans() ?? 'never',
                $token->created_at->toDateString(),
            ])->all(),
        );

        return self::SUCCESS;
    }
}
