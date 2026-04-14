<?php

namespace App\Models;

use App\Enums\ContactTypeEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'person_id',
        'contact_type',
        'contact',
        'valid_end',
    ];

    protected $casts = [
        'contact_type' => ContactTypeEnum::class,
        'valid_end' => 'date',
    ];

    /**
     * Get the person that owns the contact.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
