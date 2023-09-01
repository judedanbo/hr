<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'abbreviation', 'start_date', 'end_date', 'status'];

    /**
     * Get all of the departments for the Institution
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Unit::class)->where('units.type', UnitType::DEPARTMENT);
    }

    /**
     * Get all of the divisions for the Institution
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(Unit::class)->where('units.type', UnitType::DIVISION);
    }

    /**
     * Get all of the units for the Institution
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->where('units.type', UnitType::UNIT);
    }
    public function allUnits(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * Get all of the staff for the Institution
     */
    public function staff(): HasMany
    {
        return $this->hasMany(InstitutionPerson::class);
    }

    /**
     * Get all of the jobs for the Institution
     */
    public function ranks(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class);
    }
}