<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PersonUnit extends Pivot
{
    /**
     * Get the person that owns the PersonUnit
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the unit that owns the PersonUnit
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The jobs that belong to the PersonUnit
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class,'job_staff','staff_id','job_id')
            ->withPivot('start_date', 'end_date')
            ->orderByPivot('start_date');
    }

   /**
    * Get all of the dependents for the PersonUnit
    *
    * @return \Illuminate\Database\Eloquent\Relations\HasMany
    */
   public function dependents(): HasMany
   {
       return $this->hasMany(Dependent::class,'staff_id');
   }
}
