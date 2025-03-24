<?php

namespace App\Models;

use App\Enums\EmployeeStatusEnum;
use App\Traits\LogAllTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PersonUnit extends Pivot
{
    use LogAllTraits;
    protected $casts = [
        // 'status' => EmployeeStatusEnum::class,
        'hire_date' => 'date',
        'date_of_birth' => 'date',
        'start_date' => 'date',
    ];

    // protected $appends = ['status', 'years_employed'];

    /**
     * Get the person that owns the PersonUnit
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the unit that owns the PersonUnit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * The jobs that belong to the PersonUnit
     */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_staff', 'staff_id', 'job_id')
            ->withPivot('start_date', 'end_date')
            ->orderByPivot('start_date')
            ->latest();
    }

    /**
     * The currentRank that belong to the PersonUnit
     */
    public function currentRank(): BelongsTo
    {
        return $this->belongsTo(Job::class)
            // ->withPivot('start_date', 'end_date')
            // ->orderByPivot('start_date')
            ->latestOfMany();
    }

    /**
     * Get all of the dependents for the PersonUnit
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class, 'staff_id');
    }

    public function getYearsEmployedAttribute()
    {
        return $this->person->date_of_birth->diffInYears(Carbon::now()) > 59 ? $this->hire_date?->diffInYears($this->person->date_of_birth->addYears(60)) : $this->hire_date?->diffInYears(Carbon::now());
    }

    public function scopeActive($query)
    {
        return $query->whereRaw('(DATEDIFF(NOW(), PEOPLE.DATE_OF_BIRTH)/365) < 60');
    }

    public function scopeRetired($query)
    {
        return $query->whereRaw('(DATEDIFF(NOW(), PEOPLE.DATE_OF_BIRTH)/365) > 60');
    }

    public function getStatusAttribute()
    {
        if ($this->person->date_of_birth->diffInYears(Carbon::now()) > 59) {
            return 'Retired';
        }

        return 'Active';
    }
}
