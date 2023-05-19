<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\Nationality;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'surname',
        'first_name',
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
        'marital_status' => MaritalStatus::class,
        'nationality' => Nationality::class,
    ];

    /// get full name of person
    public function getFullNameAttribute()
    {
        return ucwords(strtolower("{$this->title} {$this->first_name} {$this->other_names} {$this->surname}"));
    }

    public function scopeOrderDob($query)
    {
        return $query->orderBy('date_of_birth');
    }

    public function getInitialsAttribute()
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->surname, 0, 1));
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth->diffInYears(new Carbon());
    }

    public function getNumberAttribute()
    {
        return Person::count();
    }

    /**
     * The units that belong to the Person
     */
    public function institution(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)
            ->using(InstitutionPerson::class)
            ->withPivot(
                'file_number',
                'staff_number',
                'old_staff_number',
                'hire_date',
                'end_date',
                'id'
            )
            ->as('staff');
    }

    /**
     * Get the dependent associated with the Person
     */
    public function dependent(): HasOne
    {
        return $this->hasOne(Dependent::class);
    }

    /**
     * Get all of the contacts for the Person
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get all all persons's address
     */
    public function address()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function identities(): HasMany
    {
        return $this->hasMany(PersonIdentity::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class);
    }
}