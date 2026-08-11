<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The roles list was gated three different ways: the route required
 * `view roles`, RoleController@index required `view all roles`, and the
 * sidebar checked `view all roles`. Standardising on `view roles` would
 * lock out anyone who only holds `view all roles`, so grant the
 * standardised permission to every role and user that already has the
 * old one.
 *
 * `view all roles` is deliberately left in place — it may be assigned in
 * production — it is simply no longer referenced by application code.
 */
return new class extends Migration
{
    public function up(): void
    {
        $legacy = Permission::where('name', 'view all roles')->first();

        if (! $legacy) {
            return;
        }

        $standard = Permission::firstOrCreate(
            ['name' => 'view roles'],
            ['guard_name' => $legacy->guard_name]
        );

        Role::whereHas('permissions', fn ($query) => $query->where('id', $legacy->id))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($standard));

        $userModel = config('auth.providers.users.model');

        $userModel::whereHas('permissions', fn ($query) => $query->where('id', $legacy->id))
            ->get()
            ->each(fn ($user) => $user->givePermissionTo($standard));
    }

    public function down(): void
    {
        // Intentionally irreversible: revoking `view roles` could remove access
        // that was granted independently of this migration.
    }
};
