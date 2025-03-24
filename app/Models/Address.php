<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    protected $fillable = [
        'address_line_1',
        'address_line_2',
        'city',
        'region',
        'country',
        'post_code',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}
