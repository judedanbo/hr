<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Gender;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'surname',
        'other_names',
        'date_of_birth',
        'gender',
        'nationality',
        'social_security_number',
        'national_id_number',
        'image',
        'about',
    ];

    protected $casts = [
        'gender' => Gender::class,
        'date_of_birth' => 'date',
    ];

    /// get full name of person
    public function getFullNameAttribute()
    {
        return "{$this->title} {$this->other_names} {$this->surname}";
    }

    public function scopeOrderDob($query)
    {
        return $query->orderBy('date_of_birth');
    }

    function getInitialsAttribute()
    {
        return strtoupper(substr($this->other_names, 0, 1) . substr($this->surname, 0, 1));
    }

    public function getNumberAttribute()
    {
        return Person::count();
    }

    /**
     * The units that belong to the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)
            ->withPivot('id', 'staff_number', 'old_staff_number', 'hire_date', 'start_date')
            ->withTimestamps()
            ->using(PersonUnit::class)
            ->whereNull('end_date')
            ->as('staff');
    }

    /**
     * Get the dependent associated with the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dependent(): HasOne
    {
        return $this->hasOne(Dependent::class);
    }


    /**
     * Get all of the contacts for the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get all all persons's addressed
     */

    public function address()
    {
        return $this->morphMany(Address::class, 'addressable')->latest();
    }
}