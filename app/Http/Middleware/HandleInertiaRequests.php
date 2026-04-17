<?php

namespace App\Http\Middleware;

use App\Models\Person;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => fn () => $request->user()
                    ? array_merge(
                        $request->user()->only('id', 'name', 'email'),
                        ['person_id' => $request->user()->person_id],
                    )
                    : null,
                'roles' => fn () => $request->user()?->getRoleNames(),
                // 'is_admin' => fn() => $request->user()?->isAdmin(),
                'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name'),
                'viewMode' => fn () => $request->session()->get('view_mode'),
                'isMultiRoleStaff' => fn () => $request->user()?->isMultiRoleStaff() ?? false,
                'viewModeLabel' => fn () => $this->resolveViewModeLabel($request->user()),
                'has_photo' => fn () => $this->hasPhotoForCurrentUser($request),
                'qualifications_count' => fn () => $this->qualificationsCountForCurrentUser($request),
            ],
            // 'permissions' => fn() => $request->user()?->getAllPermissions()->pluck('name'),
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ]);
    }

    private function resolveViewModeLabel(?\App\Models\User $user): ?string
    {
        if (! $user?->isMultiRoleStaff()) {
            return null;
        }

        return $user->canAccessAdminDashboard() ? 'Admin' : 'Other';
    }

    private function hasPhotoForCurrentUser(Request $request): ?bool
    {
        $user = $request->user();
        if (! $user || ! $user->person_id) {
            return null;
        }

        return (bool) Person::query()
            ->whereKey($user->person_id)
            ->whereNotNull('image')
            ->exists();
    }

    private function qualificationsCountForCurrentUser(Request $request): ?int
    {
        $user = $request->user();
        if (! $user || ! $user->person_id) {
            return null;
        }

        return Qualification::query()
            ->where('person_id', $user->person_id)
            ->count();
    }
}
