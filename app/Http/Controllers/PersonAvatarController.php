<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PersonAvatarController extends Controller
{
    public function index(Person $person)
    {
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

        $avatar = Storage::disk('public')->put('avatars', $request->image);

        $person->update([
            'image' => $avatar,
        ]);

        activity()
            ->performedOn($person)
            ->causedBy($request->user())
            ->event('updated avatar')
            ->log('Updated avatar');

        return back()->with('success', 'Image uploaded successfully');
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
     * Users may modify their own avatar. Others require the upload avatar permission.
     */
    protected function authorizeAvatarAction(User $user, Person $person): void
    {
        if ($user->person?->id === $person->id) {
            return;
        }

        if (! $user->can('upload avatar')) {
            abort(Response::HTTP_FORBIDDEN, 'You can only modify your own avatar.');
        }
    }
}
