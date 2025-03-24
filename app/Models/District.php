<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    /**
     * Get the Region that owns the District
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'foreign_key', 'other_key');
    }
}
