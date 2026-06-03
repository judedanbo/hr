<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@audit.gov.gh'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('gbqdF4b6zxF7G87ihvA'),
            ]
        );
        if ($user) {
            $user->assignRole('super-administrator');
        }
    }
}
