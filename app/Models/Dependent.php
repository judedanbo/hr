<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dependent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'person_id',
        'relation',
    ];

    /**
     * Get the person associated with the Dependent
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function person(): BelongsTo
    {
        return $this->BelongsTo(Person::class);
    }

    /**
     * Get the staff associated with the Dependent
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function staff(): HasOne
    {
        return $this->hasOne(PersonUnit::class,'staff_id');
    }
}
