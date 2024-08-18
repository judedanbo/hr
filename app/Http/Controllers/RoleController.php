<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Role/Index', [
            'roles' => Role::withCount(['permissions', 'users'])->paginate(),
            'filters' => ['search' => request()->search],
        ]);
        // return Role::with(['permissions', 'users'])->get();
    }

    public function show(Role $role)
    {
        return Inertia::render('Role/Show', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => Str::of($role->name)->replace('-', ' ')->title(),
            ],
            'users' => $role->users()
                ->withCount('permissions')
                ->paginate(),
            'permissions' => $role->permissions()
                ->withCount('users')
                ->paginate(5),
        ]);
    }

    public function list()
    {
        return Role::get(['name as value', 'name as label']);
    }

    public function addRole(Request $request, User $user)
    {
        $user->assignRole($request->roles);

        return redirect()->back()->with('success', 'Role added successfully');
    }

    public function revokeRole(Request $request, User $user)
    {
        $user->assignRole($request->role);

        return redirect()->back()->with('success', 'Role revoked successfully');
    }
}
