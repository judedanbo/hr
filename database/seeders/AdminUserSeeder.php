<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the first super-administrator.
 *
 * The password is generated when the account is created and printed once. It
 * is never kept in the repository, and the account must replace it at first
 * sign-in. An account that already exists keeps the password it has.
 */
class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@audit.gov.gh';

    public function run(): void
    {
        $user = User::query()->where('email', self::EMAIL)->first();

        if ($user === null) {
            $password = Str::password(24);

            $user = User::create([
                'name' => 'System Administrator',
                'email' => self::EMAIL,
                'password' => bcrypt($password),
                'password_change_at' => null,
            ]);

            $this->command?->warn('Created ' . self::EMAIL . ' with a one-time password, shown only now: ' . $password);
            $this->command?->line('It must be changed at first sign-in.');
        } else {
            $this->command?->line(self::EMAIL . ' already exists. Its password was not changed.');
        }

        $user->assignRole('super-administrator');
    }
}
