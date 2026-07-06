<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateServiceAccount extends Command
{
    protected $signature = 'app:create-service-account
        {email : Email for the service account}
        {--name= : Display name (defaults to the title-cased email local-part)}';

    protected $description = 'Create a locked, non-human user intended solely to own external API tokens.';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => 'email'])->fails()) {
            $this->error("'{$email}' is not a valid email address.");

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email {$email} already exists.");

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $this->option('name') ?: $this->deriveName($email),
            'email' => $email,
            'password' => Hash::make(Str::random(48)),
            'password_change_at' => now(),
            'is_service' => true,
        ]);

        $this->info("Service account created (id={$user->id}, email={$user->email}).");
        $this->line("Issue a token for it with: php artisan app:issue-api-token {$user->email}");

        return self::SUCCESS;
    }

    private function deriveName(string $email): string
    {
        return Str::of($email)->before('@')->replace(['.', '_', '-'], ' ')->title()->toString();
    }
}
