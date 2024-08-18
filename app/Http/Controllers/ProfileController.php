<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Profile/Index', [
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id(),
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        auth()->user()->update($request->all());

        return back()->with('success', 'Profile updated successfully.');
    }

    public function delete()
    {
        auth()->user()->delete();

        return redirect()->route('login')->with('success', 'Your account has been deleted.');
    }
}
