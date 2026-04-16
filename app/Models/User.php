<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
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

    public function canAccessAdminDashboard(): bool
    {
        return $this->hasRole('super-administrator') || $this->can('view dashboard');
    }
}
