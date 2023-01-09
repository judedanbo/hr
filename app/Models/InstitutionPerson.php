<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InstitutionPerson extends Pivot
{
    protected $fillable = [
        'institution_id',
        'person_id',
        'file_number',
        'staff_number',
        'email',
        'old_staff_number',
        'hire_date',
        'end_date',
    ];
    // protected $appends =  ['status'];

    protected $casts = [
        'hire_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the person that owns the InstitutionPerson
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the institution that owns the InstitutionPerson
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get all of the units for the InstitutionPerson
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function units(): belongsToMany
    {
        return $this->belongsToMany(Unit::class, 'staff_unit', 'staff_id', 'unit_id')
            ->withPivot('start_date', 'end_date')
            ->using(StaffUnit::class)
            ->orderByPivot('start_date', 'desc');
    }

    /**
     * The ranks that belong to the InstitutionPerson
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ranks(): BelongsToMany
    {
        return $this->belongsToMany(
            Job::class,
            'job_staff',
            'staff_id',
            'job_id'
        )->withPivot(
            'start_date',
            'end_date'
        )
            ->using(JobStaff::class)
            ->orderByPivot('start_date', 'desc');
    }
    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class, 'staff_id');
    }

    public function scopeActive($query)
    {
        return $query->whereRaw("(DATEDIFF(NOW(), people.date_of_birth)/365) < 60");
    }
    public function scopeRetired($query)
    {
        return $query->whereRaw("(DATEDIFF(NOW(), people.date_of_birth)/365) > 60");
    }

    public function getStatusAttribute()
    {
        // return $this->person->date_of_birth->diffInYears(Carbon::now());
        if ($this->person->date_of_birth->diffInYears(Carbon::now()) > 59) {
            return 'Retired';
        };
        return 'Active';
    }
}