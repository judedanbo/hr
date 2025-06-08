<?php

namespace App\Models;

use App\Enums\OfficeTypeEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Office extends Model
{
    use HasFactory, LogAllTraits;

    protected $casts = [
        'name' => 'string',
        'type' => OfficeTypeEnum::class
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class);
    }
    public function getUnitsNumberAttribute(): int
    {
        return $this->units()->count();
    }
}
