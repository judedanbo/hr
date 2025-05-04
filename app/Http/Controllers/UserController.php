<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Gate::denies('view all users')) {
            activity()
                ->causedBy(auth()->user())
                ->event('index')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted access to view all users');
            return redirect()->back()->with('error', 'You are not authorized to view all users');
        }
        activity()
            ->causedBy(auth()->user())
            ->event('index')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('viewed all users');
        $users = User::query()
            ->with('roles', 'permissions')
            ->withCount(['roles', 'permissions'])
            ->paginate(10)
            ->withQueryString()
            ->through(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verified' => $user->email_verified_at ? 'Yes' : 'No',
                'roles_count' => $user->roles_count,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'value' => $role->id,
                        'label' => $role->name,
                        // 'start_date' => $role->created_at->format('d M Y'),
                    ];
                }),
                'permissions_count' => $user->getAllPermissions()->count(),
            ]);

        return Inertia::render('User/Index', [
            'users' => $users,
            'filters' => ['search' => request()->search],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        if (Gate::denies('create user')) {
            activity()
                ->causedBy(auth()->user())
                ->event('store')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to create a new user');
            return redirect()->back()->with('error', 'You are not authorized to create a new user');
        }
        $password = Str::random(8);
        $bio = $request->all()['userData']['bio'];
        $bio['password'] = Hash::make($password); //bcrypt('password');
        $newUser = User::create($bio);
        if ($request->all()['userData']['roles']) {
            $newUser->assignRole($request->all()['userData']['roles']);
        }
        Mail::to($bio['email'])->send(
            new \App\Mail\UserCreated($newUser, $password)
        );

        // return->redirect
        return redirect()->route('user.index')->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        if (Gate::denies('view user')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->event('show')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to view a user');
            return redirect()->back()->with('error', 'You are not authorized to view this user');
        }
        $user->load(['roles', 'permissions']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('show')
            ->withProperties([
                'result' => 'success',
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('viewed a user');
        return Inertia::render('User/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verified' => $user->email_verified_at ? 'Yes' : 'No',
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'start_date' => $role->created_at->format('d M Y'),
                    ];
                }),
                // 'permissions' => $user->getAllPermissions()->map(function ($permission) {
                //     return [
                //         'id' => $permission->id,
                //         'name' => $permission->name,
                //         'start_date' => $permission->created_at->format('d M Y'),
                //     ];
                // }),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        if (Gate::denies('edit user')) {
            activity()
                ->causedBy(auth()->user())
                ->event('update')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to update a user');
            return redirect()->back()->with('error', 'You are not authorized to update this user');
        }
        // return $request->validated();
        $user->update($request->validated());

        return redirect()->route('user.index')->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(User $user)
    {
        if (Gate::denies('delete user')) {
            activity()
                ->causedBy(auth()->user())
                ->event('delete')
                ->withProperties([
                    'result' => 'failed',
                    'user_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ])
                ->log('attempted to delete a user');
            return redirect()->back()->with('error', 'You are not authorized to delete this user');
        }
        $user->delete();

        return redirect()->route('user.index')->with('success', 'User deleted successfully');
    }

    public function roles(User $user)
    {
        $user->load('roles');

        return [
            'roles' => $user->roles->map(function ($role) {
                return $role->name;
            }),
        ];
    }
    public function permissions(User $user)
    {
        // return $user->getAllPermissions();
        // // dd($user);
        // $user->load('permissions');

        return [
            'permissions' => $user->getAllPermissions()->map(function ($permission) {
                return $permission->name;
            }),
        ];
    }
    public function rolesPermissions(User $user)
    {
        // return $user->getAllPermissions();
        // // dd($user);
        // $user->load('permissions');

        return [
            'permissions' => $user->getPermissionsViaRoles()->map(function ($permission) {
                return $permission->name;
            }),
        ];
    }

    public function resetPassword(User $user)
    {
        $password = Str::random(8);
        // $user = User::where(request()->user->id)->first();
        $user->update([
            'password' => Hash::make($password),
            'password_change_at' => null,
        ]);
        Mail::to($user->email)->send(
            new \App\Mail\PasswordReset($user, $password)
        );

        return redirect()->route('user.index')->with('success', 'Password reset successfully');
    }
}
