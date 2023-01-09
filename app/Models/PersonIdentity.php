<?php

namespace App\Models;

use App\Enums\Identity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonIdentity extends Model
{
    use SoftDeletes;

    protected $fillable = ['person_id', 'id_type', 'id_number', 'notes'];

    protected $casts = ['id_type' => Identity::class];

    /**
     * Get the person that owns the PersonIdentity
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}