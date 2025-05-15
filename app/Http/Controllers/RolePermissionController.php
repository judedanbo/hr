<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __invoke(Role $role)
    {
        $role->load('permissions');
        return $role->permissions->pluck('name');
    }
}
