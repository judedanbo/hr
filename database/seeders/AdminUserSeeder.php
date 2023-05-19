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
        User::create(
            [
                'name' => 'System Administrator',
                'email' => 'admin@audit.gov.gh',
                'password' => bcrypt('gbqdF4b6zxF7G87ihvA'),
            ],
        );
    }
}
