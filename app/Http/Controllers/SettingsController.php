<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function __invoke()
    {
        if (Gate::denies('view admin settings')) {
            activity()
                ->causedBy(auth()->user())
                ->event('view')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted access to settings');
            return redirect()->back()->with('error', 'You are not authorized to view settings');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('view')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('viewed settings');

        // Fetch the settings data from the database or any other source

        $users = User::count();
        $staff = User::role('staff')->count();
        $hrUser = User::role('hr-user')->count();
        $roles = Role::count();
        $permissions = Permission::count();
        return Inertia::render('Settings/Index', [
            'users' => $users,
            'hr-user' => $hrUser,
            'staff' => $staff,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
