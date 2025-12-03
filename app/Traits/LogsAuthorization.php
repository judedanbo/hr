<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

trait LogsAuthorization
{
    /**
     * Authorize and log the action. Redirects back with error if unauthorized.
     * Returns null if authorized, RedirectResponse if not.
     */
    protected function authorizeWithLog(
        string $permission,
        string $successMessage,
        ?Model $model = null
    ): ?RedirectResponse {
        $user = auth()->user();
        $properties = [
            'user_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if (Gate::denies($permission)) {
            activity()
                ->causedBy($user)
                ->event('authorization_failed')
                ->withProperties([...$properties, 'result' => 'failed', 'permission' => $permission])
                ->log("attempted: {$permission}");

            return redirect()->back()->with('error', "You are not authorized to {$permission}");
        }

        $activity = activity()
            ->causedBy($user)
            ->event('authorization_success')
            ->withProperties([...$properties, 'result' => 'success']);

        if ($model) {
            $activity->performedOn($model);
        }

        $activity->log($successMessage);

        return null;
    }

    /**
     * Log a successful action (for use after authorization passes via middleware).
     */
    protected function logSuccess(string $message, ?Model $model = null): void
    {
        $activity = activity()
            ->causedBy(auth()->user())
            ->event('success')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

        if ($model) {
            $activity->performedOn($model);
        }

        $activity->log($message);
    }

    /**
     * Log a failed authorization attempt (for use when authorization fails).
     */
    protected function logFailedAuthorization(string $permission): void
    {
        activity()
            ->causedBy(auth()->user())
            ->event('authorization_failed')
            ->withProperties([
                'result' => 'failed',
                'permission' => $permission,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log("attempted: {$permission}");
    }
}
