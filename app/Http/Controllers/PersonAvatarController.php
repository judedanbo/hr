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
        $request->validate([
            'image' => ['required', 'image', 'max:2048'],
        ]);

        $avatar = Storage::disk('avatars')->put('/', $request->image);
        // dd($avatar);
        // $fileName = $request->file('image')->store('public/avatar');

        $person->update([
            'image' => $avatar, //$request->file('image')->hashName(),
        ]);

        return back()->with('success', 'Image uploaded successfully');
    }
}
