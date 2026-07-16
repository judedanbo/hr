<?php

namespace App\Models;

use App\Enums\LeavePlanStatusEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeavePlan extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'leave_year_id',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeavePlanStatusEnum::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'staff_id');
    }

    public function leaveYear(): BelongsTo
    {
        return $this->belongsTo(LeaveYear::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LeavePlanItem::class);
    }
}
