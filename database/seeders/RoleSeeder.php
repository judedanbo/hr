<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Application roles with their descriptions.
     *
     * @var array<string, string>
     */
    protected array $roles = [
        'super-administrator' => 'Full system access with all permissions',
        'admin-user' => 'Administrative access for system management',
        'general-admin-user' => 'General administrative functions',
        'hr-user' => 'Human resources management access',
        'personel-user' => 'Personnel/staff data management',
        'aag-admin' => 'AAG Admin with dashboard access',
        'internal-audit-user' => 'Internal audit with read-only staff list access',
        'staff' => 'Basic staff access with limited permissions',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->roles as $name => $description) {
            Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            );
        }

        $this->command->info('Roles seeded successfully: ' . count($this->roles) . ' roles');
    }
}
