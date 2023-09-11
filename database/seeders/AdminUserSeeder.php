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
            [
                'name' => 'Richard Opoku Brobbey',
                'email' => 'richard.brobbey@audit.gov.gh',
                'password' => bcrypt('password123'),
            ],
            [
                'name' => 'Yvette Akuorkor Barnor',
                'email' => 'yvette.barnor@audit.gov.gh',
                'password' => bcrypt('password123'),
            ],
            [
                'name' => 'Naa Densua Prentice',
                'email' => 'naadensua.prentice@audit.gov.gh',
                'password' => bcrypt('password123'),
            ],
        );
    }
}