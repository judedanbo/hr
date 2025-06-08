<?php

namespace App\Models;

use App\Enums\DistrictTypeEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    protected $casts = [
        'name' => 'string',
        'district_type' => DistrictTypeEnum::class,
    ];

    /**
     * Get the Region that owns the District
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function offices()
    {
        return $this->hasMany(Office::class);
    }

    // public function getUnitsNumberAttribute(): int
    // {
    //     return $this->offices()
    //         ->withCount('units')
    //         ->get()
    //         ->sum('units_count');
    // }
}
