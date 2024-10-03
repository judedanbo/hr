<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'position_staff', 'position_id', 'staff_id')
            ->withPivot('start_date', 'end_date')
            ->withTimestamps()
            ->using(PositionStaff::class)
            ->orderByPivot('start_date', 'desc');
    }
}
