<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        // dd($request->all());
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg',
            ],
        ]);

        $avatar = Storage::disk('public')->put('avatars', $request->image);
        // dd($avatar);
        // $fileName = $request->file('image')->store('public/avatar');

        $person->update([
            'image' => $avatar, //$request->file('image')->hashName(),
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

        $person->update([
            'image' => null,
        ]);

        // record the action in the activity log
        activity()
            ->performedOn($person)
            ->causedBy($request->user())
            ->event('deleted avatar')
            ->log('Deleted avatar');
        // ->create([
        //     'action' => 'deleted',
        //     'description' => 'Deleted avatar',
        //     'model_type' => Person::class,
        //     'model_id' => $person->id,
        // ]);

        return back()->with('success', 'Image deleted successfully');
    }
}
