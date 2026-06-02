<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
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

        $recentActivity = Gate::allows('view user activity')
            ? Activity::with('causer')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Activity $activity) => [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer_name' => $activity->causer?->name ?? 'System',
                    'created_at' => $activity->created_at->format('d M Y H:i'),
                ])
                ->all()
            : [];

        return Inertia::render('Settings/Index', [
            'stats' => [
                'users' => User::count(),
                'staff' => User::role('staff')->count(),
                'hrUser' => User::role('hr-user')->count(),
                'roles' => Role::count(),
                'permissions' => Permission::count(),
                'auditLogs' => Activity::count(),
                'institutions' => Institution::count(),
            ],
            'recentActivity' => $recentActivity,
        ]);
    }
}
