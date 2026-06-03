<?php

namespace App\Models;

use App\Enums\GenderEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'requires_evidence',
        'gender_restriction',
        'counts_weekends',
        'counts_holidays',
        'min_notice_days',
        'max_consecutive_days',
        'max_concurrent_per_unit',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_evidence' => 'boolean',
            'gender_restriction' => GenderEnum::class,
            'counts_weekends' => 'boolean',
            'counts_holidays' => 'boolean',
            'min_notice_days' => 'integer',
            'max_consecutive_days' => 'integer',
            'max_concurrent_per_unit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(LeaveEntitlement::class);
    }
}
