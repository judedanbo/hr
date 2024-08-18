<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::with(['roles', 'users'])->get();
    }

    public function list()
    {
        return Permission::get(['name as value', 'name as label']);
    }

    public function addPermission(Request $request, User $user)
    {
        $user->givePermissionTo($request->permissions);

        return redirect()->back()->with('success', 'Permission added successfully');
    }

    public function revokePermission(Request $request, User $user)
    {
        $user->revokePermissionTo($request->permission);

        return redirect()->back()->with('success', 'Permission revoked successfully');
    }
}
