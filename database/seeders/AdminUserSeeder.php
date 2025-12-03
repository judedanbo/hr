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
        User::firstOrCreate(
            ['email' => 'admin@audit.gov.gh'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('gbqdF4b6zxF7G87ihvA'),
            ]
        );
        User::firstOrCreate(
            ['email' => 'richard.brobbey@audit.gov.gh'],
            [
                'name' => 'Richard Opoku Brobbey',
                'password' => bcrypt('password123'),
            ]
        );
        User::firstOrCreate(
            ['email' => 'yvette.barnor@audit.gov.gh'],
            [
                'name' => 'Yvette Akuorkor Barnor',
                'password' => bcrypt('password123'),
            ]
        );
        User::firstOrCreate(
            ['email' => 'naadensua.prentice@audit.gov.gh'],
            [
                'name' => 'Naa Densua Prentice',
                'password' => bcrypt('password123'),
            ]
        );
    }
}
