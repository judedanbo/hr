<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate as Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        if (Gate::denies('view all roles')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to view roles');
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view roles');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('view role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('view all roles');
        // $roles = Role::with(['permissions', 'users'])->get();
        return Inertia::render('Role/Index', [
            'roles' => Role::withCount(['permissions', 'users'])
                ->paginate()
                ->through(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => Str::of($role->name)->replace('-', ' ')->title(),
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                ])
                ->withQueryString(),
            'filters' => ['search' => request()->search],
        ]);
        // return Role::with(['permissions', 'users'])->get();
    }

    public function show(Role $role)
    {
        if (Gate::denies('view role')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->event('view role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to view a role');
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view this role');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('view role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('view a role');
        $role->load(['permissions' => function (Builder $query) {
            $query->withCount('users');
            $query->paginate(5);
            // $query->withQueryString();
        }, 'users']);
        dd($role->permissions);
        return Inertia::render('Role/Show', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => Str::of($role->name)->replace('-', ' ')->title(),
            ],
            'users' => $role->users()
                ->withCount('permissions')
                ->paginate(),
            'permissions' => $role
                ->permissions
                ->map(fn($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'displayName' => Str::of($permission->name)->replace('-', ' ')->title(),
                    'userCount' => $permission->user_count,
                ])
        ]);
    }

    public function store(Request $request)
    {
        if (Gate::denies('create role')) {
            activity()
                ->causedBy(auth()->user())
                ->event('create role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'role' => $request->name,
                ])
                ->log('attempted to create a role');
            return redirect()->back()->with('error', 'You do not have permission to create roles');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('create role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('create a role');
        $role = Role::create(['name' => $request->name]);

        return redirect()->back()->with('success', 'Role created successfully');
    }
    public function update(Request $request, Role $role)
    {
        if (Gate::denies('update role')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->event('update role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'role' => $request->name,
                ])
                ->log('attempted to update a role');
            return redirect()->back()->with('error', 'You do not have permission to update roles');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('update role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('update a role');
        $role->update(['name' => $request->name]);

        return redirect()->back()->with('success', 'Role updated successfully');
    }
    public function destroy(Role $role)
    {
        if (Gate::denies('delete role')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->event('delete role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to delete a role');
            return redirect()->back()->with('error', 'You do not have permission to delete roles');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('delete role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('delete a role');
        $role->delete();

        return redirect()->back()->with('success', 'Role deleted successfully');
    }
    public function restore(Role $role)
    {
        if (Gate::denies('restore role')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->event('restore role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to restore a role');
            return redirect()->back()->with('error', 'You do not have permission to restore roles');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('restore role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('restore a role');
        $role->restore();

        return redirect()->back()->with('success', 'Role restored successfully');
    }
    public function destroyRole(Role $role)
    {
        if (Gate::denies('destroy role')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->event('destroy role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to destroy a role');
            return redirect()->back()->with('error', 'You do not have permission to destroy roles');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('destroy role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('destroy a role');
        $role->forceDelete();

        return redirect()->back()->with('success', 'Role destroyed successfully');
    }

    public function list()
    {
        return Role::get(['name as value', 'name as label']);
    }

    public function addRole(Request $request, User $user)
    {
        if (Gate::denies('assign roles to user')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->event('assign role to user')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'role' => $request->roles,
                ])
                ->log('attempted to add/update a user roles')
            ;
            return redirect()->back()->with('error', 'You do not have permission to add/update roles to users');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('assign role to user')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('add/update a user roles');
        $user->syncRoles($request->roles);

        return redirect()->back()->with('success', 'Role update successfully');
    }

    public function revokeRole(Request $request, User $user)
    {
        if (Gate::denies('assign roles to user')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->event('revoke role from user')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'role' => $request->role,
                ])
                ->log('attempted to revoke a user role')
            ;
            return redirect()->back()->with('error', 'You do not have permission to revoke roles from users');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('revoke role from user')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('revoke a user role');
        $user->removeRole($request->role);

        return redirect()->back()->with('success', 'Role revoked successfully');
    }

    function addPermission(Request $request, Role $role)
    {
        if (Gate::denies('assign permissions to role')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->event('assign permission to role')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'permission' => $request->permissions,
                ])
                ->log('attempted to add/update a role permissions')
            ;
            return redirect()->back()->with('error', 'You do not have permission to add/update permissions to roles');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($role)
            ->event('assign permission to role')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('add/update a role permissions');
        $role->syncPermissions($request->permissions);

        return redirect()->back()->with('success', 'Permission update successfully');
    }
}
