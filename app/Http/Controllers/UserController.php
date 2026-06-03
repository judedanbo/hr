<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\StoreUserStaffRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Person;
use App\Models\User;
use App\Traits\LogsAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    use LogsAuthorization;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->logSuccess('viewed all users');

        $users = User::query()
            ->with('roles', 'permissions', 'person.institution')
            ->withCount(['roles', 'permissions'])
            ->paginate(per_page())
            ->withQueryString()
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verified' => $user->email_verified_at ? 'Yes' : 'No',
                'person_id' => $user->person_id,
                'staff_name' => $user->person?->full_name,
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
        $password = Str::random(8);
        $bio = $request->all()['userData']['bio'];
        $bio['password'] = Hash::make($password); // bcrypt('password');
        $newUser = User::create($bio);
        if ($request->all()['userData']['roles']) {
            $newUser->assignRole($request->all()['userData']['roles']);
        }
        Mail::to($bio['email'])->send(
            new \App\Mail\UserCreated($newUser, $password)
        );

        $this->logSuccess('created a new user', $newUser);

        return redirect()->route('user.index')->with('success', 'User created successfully');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $user->load(['roles.permissions', 'permissions', 'person.institution']);

        $this->logSuccess('viewed a user', $user);

        $directIds = $user->getDirectPermissions()->pluck('id');
        $roles = $user->roles;

        $inherited = $user->getAllPermissions()
            ->reject(fn ($permission) => $directIds->contains($permission->id))
            ->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'via' => $roles
                    ->filter(fn ($role) => $role->permissions->contains('id', $permission->id))
                    ->pluck('name')
                    ->implode(', '),
            ])
            ->values();

        return Inertia::render('User/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'verified' => $user->email_verified_at ? 'Yes' : 'No',
                'person_id' => $user->person_id,
                'staff' => $user->person ? [
                    'id' => $user->person->id,
                    'name' => $user->person->full_name,
                    'staff_number' => $user->person->institution->first()?->staff?->staff_number,
                ] : null,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'start_date' => $role->created_at->displayDate(),
                ]),
                'direct_permissions' => $user->getDirectPermissions()->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'start_date' => $permission->created_at->displayDate(),
                ])->values(),
                'inherited_permissions' => $inherited,
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
        $user->update($request->validated());

        $this->logSuccess('updated a user', $user);

        return redirect()->route('user.index')->with('success', 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(User $user)
    {
        $this->logSuccess('deleted a user', $user);

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
        DB::transaction(function () use ($user) {
            $password = Str::random(8);
            $user->update([
                'password' => Hash::make($password),
                'password_change_at' => null,
            ]);
            Mail::to($user->email)->send(
                new \App\Mail\PasswordReset($user, $password)
            );
        });

        $this->logSuccess('reset a user password', $user);

        return redirect()->route('user.index')->with('success', 'Password reset successfully');
    }

    public function list()
    {
        return User::query()
            ->withCount(['roles', 'permissions'])
            ->paginate(per_page())
            ->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles_count' => $user->roles_count,
                'permissions_count' => $user->permissions_count,
            ]);
    }

    /**
     * Associate a user account with an existing staff record.
     */
    public function associateStaff(StoreUserStaffRequest $request, User $user): RedirectResponse
    {
        $user->person_id = $request->validated()['person_id'];
        $user->save();

        Cache::forget('user_staff_options_default');

        $this->logSuccess('associated a user with a staff record', $user);

        return redirect()->back()->with('success', 'User associated with staff record successfully');
    }

    /**
     * Remove a user's association with a staff record.
     *
     * An unlinked user cannot be staff, so the staff role is removed as well to
     * keep the invariant consistent.
     */
    public function dissociateStaff(User $user): RedirectResponse
    {
        $user->person_id = null;
        $user->save();

        if ($user->hasRole('staff')) {
            $user->removeRole('staff');
        }

        Cache::forget('user_staff_options_default');

        $this->logSuccess('removed a user staff association', $user);

        return redirect()->back()->with('success', 'Staff association removed successfully');
    }

    /**
     * Return staff people available to link to a user account.
     *
     * Only people who are staff (have an institution_person row) and are not
     * already linked to another user account are returned.
     */
    public function staffOptions(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $build = function () use ($search): array {
            $linkedPersonIds = User::query()->whereNotNull('person_id')->pluck('person_id');

            return Person::query()
                ->whereHas('institution')
                ->whereNotIn('id', $linkedPersonIds)
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('surname', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('other_names', 'like', "%{$search}%")
                            ->orWhereHas('institution', fn ($iq) => $iq->where('institution_person.staff_number', 'like', "%{$search}%"));
                    });
                })
                ->with('institution')
                ->orderBy('surname')
                ->limit(25)
                ->get()
                ->map(function (Person $person): array {
                    $staffNumber = $person->institution->first()?->staff?->staff_number;

                    return [
                        'value' => $person->id,
                        'label' => $staffNumber
                            ? "{$person->full_name} — {$staffNumber}"
                            : $person->full_name,
                    ];
                })
                ->values()
                ->all();
        };

        $options = $search === ''
            ? Cache::remember('user_staff_options_default', 3600, $build)
            : $build();

        return response()->json($options);
    }
}
