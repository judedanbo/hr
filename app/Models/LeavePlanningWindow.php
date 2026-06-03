<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeavePlanningWindow extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'leave_year_id',
        'opens_at',
        'closes_at',
        'instructions',
        'unit_id',
        'allow_after_close',
        'require_full_plan',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'allow_after_close' => 'boolean',
            'require_full_plan' => 'boolean',
        ];
    }

    public function leaveYear(): BelongsTo
    {
        return $this->belongsTo(LeaveYear::class);
    }

    public function isOpen(?CarbonInterface $now = null): bool
    {
        $now ??= now();

        if ($now->lessThan($this->opens_at)) {
            return false;
        }

        return $now->lessThanOrEqualTo($this->closes_at) || $this->allow_after_close;
    }
}
