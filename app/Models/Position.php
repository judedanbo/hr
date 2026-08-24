<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class Position extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    /**
     * Application roles conferred on whoever holds this position.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'position_role')
            ->withTimestamps();
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'position_staff', 'position_id', 'staff_id')
            ->withPivot('id', 'start_date', 'end_date')
            ->withTimestamps()
            ->using(PositionStaff::class)
            ->wherePivotNull('deleted_at')
            ->orderByPivot('start_date', 'desc');
    }
}
