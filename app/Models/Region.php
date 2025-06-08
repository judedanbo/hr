<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    protected $fillable = [
        'name',
    ];

    /**
     * Get all of the districts for the Region
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    // public function getOfficesNumberAttribute(): int
    // {
    //     return $this->districts()
    //         ->withCount('offices')
    //         ->get()
    //         ->sum('offices_count');
    // }

    // function getUnitsNumberAttribute(): int
    // {
    //     return $this->districts()
    //         // ->withCount('offices.units')
    //         ->get()
    //         ->sum('units_number');
    // }
}
