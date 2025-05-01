<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ContactTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Contact;
use App\Models\InstitutionPerson;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Illuminate\Support\Str;

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
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request)
    {
        if (Auth::check()) {
            return redirect(RouteServiceProvider::HOME);
        }
        // validate staff number
        $request->validate([
            'staff_number' => ['required', 'string', 'max:15'],
            'surname' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:60'],
            // 'email' => ['required', 'string', 'max:255', 'unique:users,email'],
        ]);
        // check if staff number exists
        $staff = InstitutionPerson::where('staff_number', $request->staff_number)
            ->with('person.contacts')
            ->first();
        if (!$staff) {
            return back()->withErrors(['staff_number' => 'Staff number not found']);
        }
        if (strtolower($staff->person->surname) != strtolower($request->surname) || strtolower($staff->person->first_name) != strtolower($request->first_name)) {
            return back()->withErrors(['staff_number' => 'Staff number does not match the name']);
        }
        $fullName = $staff->person->full_name;
        $email = Str::of($request->email)
            ->lower()
            ->trim()
            ->append('@audit.gov.gh')
            ->__toString();
        $password = Str::random(10);


        // dd($email, $password);
        // check if user exists
        $user = User::where('email', $email)->first();
        if ($user) {
            return back()->withErrors(['email' => 'User account already exists']);
        }
        if ($staff->user) {
            return back()->withErrors(['staff_number' => 'User account already exists']);
        }
        // create user
        DB::transaction(function () use ($fullName, $password, $staff, $email) {
            // $user = User::create([
            //     'name' => $fullName,
            //     'email' => $email,
            //     'password' => Hash::make($password),
            // ]);

            $user = $staff->person->user()->create([
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            // update contact info with email
            $staff->person->contacts()->create([
                'contact_type' => ContactTypeEnum::EMAIL,
                'contact' => $email,
            ]);
            // add user to role
            $user->assignRole('staff');
            // send event to registered user
            event(new Registered($user));

            // add user to staff


            // send email to user with password

            Mail::to($email)->send(
                new \App\Mail\UserCreated($user, $password)
            );
        });

        return redirect()->route('login')->with('success', 'User created successfully');
    }
}
