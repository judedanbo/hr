<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('regions')->insert(
            [
                ['id' => 1, 'name' => 'Ashanti', 'capital' => 'Kumasi'],
                ['id' => 2, 'name' => 'Greater Accra', 'capital' => 'Accra'],
                ['id' => 3, 'name' => 'Ahafo', 'capital' => 'Goaso'],
                ['id' => 4, 'name' => 'Bono', 'capital' => 'Sunyani'],
                ['id' => 5, 'name' => 'Bono East', 'capital' => 'Techiman'],
                ['id' => 6, 'name' => 'Central', 'capital' => 'Cape Coast'],
                ['id' => 7, 'name' => 'Eastern', 'capital' => 'Koforidua'],
                ['id' => 8, 'name' => 'North East', 'capital' => 'Nalerigu'],
                ['id' => 9, 'name' => 'Northern', 'capital' => 'Tamale'],
                ['id' => 10, 'name' => 'Oti', 'capital' => 'Dambai'],
                ['id' => 11, 'name' => 'Savannah', 'capital' => 'Damongo'],
                ['id' => 12, 'name' => 'Upper East', 'capital' => 'Bolgatanga'],
                ['id' => 13, 'name' => 'Upper West', 'capital' => 'Wa'],
                ['id' => 14, 'name' => 'Volta', 'capital' => 'Ho'],
                ['id' => 15, 'name' => 'Western', 'capital' => 'Sekondi-Takoradi'],
                ['id' => 16, 'name' => 'Western North', 'capital' => 'Wiawso'],
            ]
        );
    }
}
