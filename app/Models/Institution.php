<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Get all of the departments for the Institution
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Unit::class)->where('type', UnitType::Department);
    }


    /**
     * Get all of the divisions for the Institution
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(Unit::class)->where('type', UnitType::Division);
    }

    /**
     * Get all of the units for the Institution
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->where('type', UnitType::Unit);
    }

    /**
     * Get all of the staff for the Institution
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staff(): HasManyThrough
    {
        return $this->hasManyThrough(PersonUnit::class,  Unit::class);
    }


}
