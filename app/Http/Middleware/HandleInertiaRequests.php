<?php

namespace App\Http\Middleware;

use App\Models\Person;
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
     * Per-request memoized result of {@see profileFactsForCurrentUser()}.
     * Laravel resolves middleware fresh per request, so this stays
     * request-scoped without leaking across users.
     *
     * @var array{has_photo: bool, qualifications_count: int}|null|false
     */
    private array|false|null $profileFactsCache = false;

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
                'has_photo' => fn () => $this->profileFactsForCurrentUser($request)['has_photo'] ?? null,
                'qualifications_count' => fn () => $this->profileFactsForCurrentUser($request)['qualifications_count'] ?? null,
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

    /**
     * Return the authenticated user's photo-presence and qualification-count
     * facts as a single associative array, or null when the request has no
     * authenticated user linked to a Person. The result is memoized for the
     * life of this request; the underlying query is one eager-loaded count.
     *
     * @return array{has_photo: bool, qualifications_count: int}|null
     */
    private function profileFactsForCurrentUser(Request $request): ?array
    {
        if ($this->profileFactsCache !== false) {
            return $this->profileFactsCache;
        }

        $user = $request->user();
        if (! $user || ! $user->person_id) {
            return $this->profileFactsCache = null;
        }

        $person = Person::query()
            ->whereKey($user->person_id)
            ->withCount('qualifications')
            ->first(['id', 'image']);

        if (! $person) {
            return $this->profileFactsCache = [
                'has_photo' => false,
                'qualifications_count' => 0,
            ];
        }

        return $this->profileFactsCache = [
            'has_photo' => (bool) $person->image,
            'qualifications_count' => (int) $person->qualifications_count,
        ];
    }
}
