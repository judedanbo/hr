<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueApiToken extends Command
{
    protected $signature = 'app:issue-api-token
        {email : Email of the user to issue the token for}
        {--name=Audit Service Website : A label for the token}
        {--ability=* : Abilities to grant (repeatable); defaults to staff-statistics:read}';

    protected $description = 'Issue a scoped Sanctum personal access token for an external API consumer.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $abilities = $this->option('ability');

        if (empty($abilities)) {
            $abilities = ['staff-statistics:read'];
        }

        $token = $user->createToken($this->option('name'), $abilities);

        $this->info('Token issued. Copy it now — it will not be shown again:');
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->info('Abilities: ' . implode(', ', $abilities));

        return self::SUCCESS;
    }
}
