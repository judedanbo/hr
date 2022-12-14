<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\Nationality;

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
        'nationality' => Nationality::class
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

    function getInitialsAttribute()
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->surname, 0, 1));
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
     * Get all all persons's address
     */

    public function address()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    /**
     * Get all of the identities for the Person
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function identities(): HasMany
    {
        return $this->hasMany(PersonIdentity::class);
    }

    // /**
    //  * Get all of the identity for the Person
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\HasMany
    //  */
    // public function identity(): HasMany
    // {
    //     return $this->hasMany(Comment::class, 'foreign_key', 'local_key');
    // }
}