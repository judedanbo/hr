<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        if (Gate::denies('view all permissions')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view permission')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to view permissions');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view permissions');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('view permission')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('view all permissions');

        return Inertia::render('Permission/Index', [
            'permissions' => Permission::withCount(['roles', 'users'])
                ->when(request('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->paginate()
                ->through(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => Str::of($permission->name)->replace('-', ' ')->title(),
                    'roles_count' => $permission->roles_count,
                    'users_count' => $permission->users_count,
                ])
                ->withQueryString(),
            'filters' => ['search' => request()->search],
        ]);
    }

    public function show(Permission $permission)
    {
        if (Gate::denies('view permission')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($permission)
                ->event('view permission')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to view a permission');

            return redirect()->route('dashboard')->with('error', 'You do not have permission to view this permission');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->event('view permission')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('view a permission');

        $permission->load(['roles' => function (Builder $query) {
            $query->withCount('users');
            $query->paginate(5);
        }, 'users']);

        return Inertia::render('Permission/Show', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => Str::of($permission->name)->replace('-', ' ')->title(),
            ],
            'roles' => $permission->roles()
                ->withCount('users')
                ->paginate(10),
            'users' => $permission->users()
                ->withCount('roles')
                ->paginate(10, ['*'], 'users_page'),
        ]);
    }

    public function store(StorePermissionRequest $request)
    {
        if (Gate::denies('create permission')) {
            activity()
                ->causedBy(auth()->user())
                ->event('create permission')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'permission' => $request->name,
                ])
                ->log('attempted to create a permission');

            return redirect()->back()->with('error', 'You do not have permission to create permissions');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('create permission')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('create a permission');

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web',
        ]);

        return redirect()->back()->with('success', 'Permission created successfully');
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        if (Gate::denies('update permission')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($permission)
                ->event('update permission')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'permission' => $request->name,
                ])
                ->log('attempted to update a permission');

            return redirect()->back()->with('error', 'You do not have permission to update permissions');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->event('update permission')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('update a permission');

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? $permission->guard_name,
        ]);

        return redirect()->back()->with('success', 'Permission updated successfully');
    }

    public function destroy(Permission $permission)
    {
        if (Gate::denies('delete permission')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($permission)
                ->event('delete permission')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to delete a permission');

            return redirect()->back()->with('error', 'You do not have permission to delete permissions');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->event('delete permission')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('delete a permission');

        $permission->delete();

        return redirect()->back()->with('success', 'Permission deleted successfully');
    }

    public function list()
    {
        $permissions = Permission::all();

        return $permissions->map(function ($permission) {
            return [
                'value' => $permission->name,
                'label' => $permission->name,
                // 'display_name' => Str::of($permission->name)->replace('-', ' ')->title(),
            ];
        });
    }

    public function addPermission(Request $request, User $user)
    {
        if (Gate::denies('assign permissions to user')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->event('assign permission to user')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'permissions' => $request->permissions,
                ])
                ->log('attempted to add/update a user permissions');

            return redirect()->back()->with('error', 'You do not have permission to add/update permissions to users');
        }
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('assign permission to user')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('add/update a user permission');
        $user->syncPermissions($request->permissions);

        return redirect()->back()->with('success', 'Permission added successfully');
    }

    public function revokePermission(Request $request, User $user)
    {
        $user->revokePermissionTo($request->permission);

        return redirect()->back()->with('success', 'Permission revoked successfully');
    }
}
