<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreChangePasswordRequest;
use App\Providers\RouteServiceProvider;
use Inertia\Inertia;

class ChangePasswordController extends Controller
{
    public function index()
    {
        $previous = url()->previous();
        if ($previous === route('change-password.index')) {
            $previous = RouteServiceProvider::HOME;
        }

        return Inertia::render('Auth/ChangePassword', ['previous' => $previous]);
    }

    public function store(StoreChangePasswordRequest $request)
    {
        auth()->user()->update([
            'password' => bcrypt($request->password),
            'password_change_at' => now(),
        ]);

        // records password Changed in log
        activity()
            ->causedBy(auth()->user())
            ->log('Password Changed');

        return redirect()->intended(RouteServiceProvider::HOME);
        // return Inertia::render('Auth/ChangePassword');
    }
}
