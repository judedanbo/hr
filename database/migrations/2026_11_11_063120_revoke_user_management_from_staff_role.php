<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * The permissions UserPermissionsSeeder used to give the staff role.
     *
     * @var array<int, string>
     */
    private const USER_MANAGEMENT = ['view user', 'create user', 'update user'];

    /**
     * Take user management away from the staff role.
     *
     * UserPermissionsSeeder gave these to every member of staff, and whoever
     * creates a user also chooses its roles. The seeder no longer does, but a
     * database it has already run against keeps the grant until something
     * removes it. Every other role is left as it is.
     *
     * The grant is removed through the role, not with a query, so that the
     * cached permissions the running application reads are cleared as well.
     */
    public function up(): void
    {
        Role::query()->where('name', 'staff')->get()->each(function (Role $role): void {
            $role->permissions()
                ->whereIn('name', self::USER_MANAGEMENT)
                ->get()
                ->each(fn (Permission $permission) => $role->revokePermissionTo($permission));
        });
    }

    /**
     * Nothing to reverse: the grant was never meant to exist.
     */
    public function down(): void {}
};
