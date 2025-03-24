<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dependent extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    protected $fillable = [
        'staff_id',
        'person_id',
        'relation',
    ];

    public function person(): BelongsTo
    {
        return $this->BelongsTo(Person::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'staff_id');
    }
}
