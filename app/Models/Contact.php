<?php

namespace App\Models;

use App\Enums\ContactTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

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
}