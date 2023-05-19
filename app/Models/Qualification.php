<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;


class Qualification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'course',
        'institution',
        'qualification',
        'qualification_number',
        'level',
        'pk',
        'year'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}