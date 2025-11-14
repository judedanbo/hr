<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(AdminUserSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(StaffUserSeeder::class);
        $this->call(UserPermissionsSeeder::class);
        $this->call(SeparationPermissionsSeeder::class);
        $this->call(DependentSeeder::class);
        $this->call(UnitsPermissionSeeder::class);
        $this->call(JobsPermissionSeeder::class);
        $this->call(JobsCategoryPermissionSeeder::class);
        $this->call(ReportPermissionSeeder::class);
        $this->call(PromotionsPermissionSeeder::class);
        $this->call(QualificationPermissionSeeder::class);
        $this->call(InstitutionPermissionSeeder::class);
        $this->call(SuperAdminSeeder::class);

        $user = \App\Models\User::where('email', 'admin@audit.gov.gh')->first();
        if ($user) {
            $user->assignRole('super-administrator');
        }
    }
}
