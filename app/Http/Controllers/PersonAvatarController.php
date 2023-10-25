<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;

class PersonAvatarController extends Controller
{
    public function index(Person $person)
    {
        return [
            'image' => $person->only(['image']),
            'initials' => $person->initials,
        ];
    }
    public function store (Request $request, Person $person)
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

        $request->file('image')->store('public/images');

        $person->update([
            'image' =>  $request->file('image')->hashName(),
        ]);

        return back()->with('success', 'Image uploaded successfully');
    }
}
