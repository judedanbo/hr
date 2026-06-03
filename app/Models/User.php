<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens,
        HasFactory,
        HasRoles,
        LogAllTraits,
        Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_change_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the person that owns the User
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the user's primary institution (via Person relationship)
     */
    public function institution(): ?Institution
    {
        return $this->person?->institution()->first();
    }

    public function isStaff(): bool
    {
        return $this->person?->isStaff() ?? false;
    }

    /**
     * The InstitutionPerson (staff record) ids that belong to this user's person.
     *
     * @return array<int, int>
     */
    public function staffIds(): array
    {
        if (! $this->person_id) {
            return [];
        }

        return InstitutionPerson::where('person_id', $this->person_id)->pluck('id')->all();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-administrator');
    }

    public function isMultiRoleStaff(): bool
    {
        return $this->hasRole('staff') && $this->roles->count() > 1;
    }

    /**
     * Whether the given set of role identifiers includes the staff role.
     *
     * Resolves names, ids, and Role instances case-insensitively so the check
     * cannot be bypassed by casing (the roles table uses a case-insensitive
     * collation) or by a nested request payload shape.
     *
     * @param  mixed  $roles  role names/ids/instances, possibly nested
     */
    public static function rolesIncludeStaff($roles): bool
    {
        return collect(Arr::wrap($roles))
            ->flatten()
            ->contains(function ($role): bool {
                if ($role instanceof RoleContract) {
                    $name = $role->name;
                } elseif (is_numeric($role)) {
                    $name = Role::find($role)?->name;
                } else {
                    $name = (string) $role;
                }

                return $name !== null && strtolower($name) === 'staff';
            });
    }

    public function canAccessAdminDashboard(): bool
    {
        return $this->hasRole('super-administrator') || $this->can('view dashboard');
    }
}
