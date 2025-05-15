<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;


class PermissionController extends Controller
{
    public function index()
    {
        return Permission::with(['roles', 'users'])->get();
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
