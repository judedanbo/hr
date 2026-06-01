<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AuditServiceUserSeeder extends Seeder
{
    /**
     * Create the Audit Service Website service account.
     *
     * The account authenticates only via a Sanctum personal access token
     * (issued out-of-band with `php artisan app:issue-api-token`), so the
     * password is randomized and never used for interactive login.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'audit-service@audit.gov.gh'],
            [
                'name' => 'Audit Service Website',
                'password' => bcrypt(Str::random(40)),
            ]
        );
    }
}
