<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __invoke(Role $role)
    {
        $role->load('permissions');

        return $role->permissions->pluck('name')->values();
    }
}
