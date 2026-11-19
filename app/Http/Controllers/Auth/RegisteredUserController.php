<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\UserCreated;
use App\Models\Person;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * RegisterRequest has already matched the names to the staff record,
     * refused people who have a login, and confirmed the address is an official
     * mailbox recorded as a contact of this person, so the password below is
     * never sent to a mailbox the visitor merely typed.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect(RouteServiceProvider::HOME);
        }

        $person = $request->staff()->person;
        $email = $request->officialEmail();
        $password = Str::random(10);

        DB::transaction(function () use ($person, $email, $password): void {
            // RegisterRequest checked for an existing login before this transaction
            // began. Locking the person makes overlapping registrations for the same
            // person queue here, so the second one sees the login the first created.
            Person::query()->whereKey($person->getKey())->lockForUpdate()->first();

            if ($person->user()->exists()) {
                throw ValidationException::withMessages([
                    'staff_number' => 'User account already exists',
                ]);
            }

            $user = $person->user()->create([
                'name' => $person->full_name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $user->assignRole('staff');

            event(new Registered($user));

            Mail::to($email)->send(new UserCreated($user, $password));
        });

        return redirect()->route('login')->with('success', 'User created successfully');
    }
}
