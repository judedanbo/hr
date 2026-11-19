<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use App\Notifications\PhotoPendingApprovalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PersonAvatarController extends Controller
{
    public function index(Person $person)
    {
        $this->authorize('view', $person);

        return [
            'image' => $person->only(['image']),
            'initials' => $person->initials,
        ];
    }

    public function store(Request $request, Person $person)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:2048'],
        ]);
    }

    public function update(Request $request, Person $person)
    {
        $this->authorizeAvatarAction($request->user(), $person);

        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
            ],
        ]);

        // Delete any existing pending file to avoid storage bloat.
        if ($person->pending_image) {
            Storage::disk('public')->delete($person->pending_image);
        }

        $pendingAvatar = Storage::disk('public')->put('avatars', $request->image);

        $person->update([
            'pending_image' => $pendingAvatar,
            'pending_image_at' => now(),
        ]);

        // Notify all approvers.
        $approvers = \App\Models\User::permission('approve staff photo')->get();
        Notification::send($approvers, new PhotoPendingApprovalNotification($person));

        activity()
            ->performedOn($person)
            ->causedBy($request->user())
            ->event('uploaded pending avatar')
            ->log('Uploaded photo pending approval');

        return back()->with('success', 'Photo uploaded and pending admin approval');
    }

    public function delete(Request $request, Person $person)
    {
        $this->authorizeAvatarAction($request->user(), $person);

        $person->update([
            'image' => null,
        ]);

        activity()
            ->performedOn($person)
            ->causedBy($request->user())
            ->event('deleted avatar')
            ->log('Deleted avatar');

        return back()->with('success', 'Image deleted successfully');
    }

    /**
     * Users may modify their own avatar. Changing someone else's needs the
     * update staff permission.
     *
     * The upload avatar permission is not enough for that: every member of the
     * staff role holds it for their own photo, so it would let any member of
     * staff replace or remove a colleague's.
     */
    protected function authorizeAvatarAction(User $user, Person $person): void
    {
        if ($user->person_id !== null && (int) $user->person_id === (int) $person->getKey()) {
            return;
        }

        if (! $user->can('update staff')) {
            abort(Response::HTTP_FORBIDDEN, 'You can only modify your own avatar.');
        }
    }
}
