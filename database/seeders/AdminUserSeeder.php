<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Creates the first super-administrator.
 *
 * The password is generated when the account is created and printed once. It
 * is never kept in the repository, and the account is sent to the change
 * password form at first sign-in. An account that already exists keeps the
 * password it has.
 *
 * The password is letters and digits only. The console formatter reads some
 * symbols as markup, so a password containing them could be printed
 * differently from the one that was stored.
 */
class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@audit.gov.gh';

    public function run(): void
    {
        $user = User::query()->where('email', self::EMAIL)->first();

        if ($user === null) {
            $password = Str::password(24, symbols: false);

            $user = User::create([
                'name' => 'System Administrator',
                'email' => self::EMAIL,
                'password' => bcrypt($password),
                'password_change_at' => null,
            ]);

            $this->command?->warn('Created ' . self::EMAIL . ' with a one-time password, shown only now: ' . OutputFormatter::escape($password));
            $this->command?->line('Change it at first sign-in.');
        } else {
            $this->command?->line(self::EMAIL . ' already exists. Its password was not changed.');
            $this->command?->line('If an earlier version of this seeder created it, change its password now.');
        }

        $user->assignRole('super-administrator');
    }
}
