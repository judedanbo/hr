<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveBalanceAdjustment extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'leave_year_id',
        'days',
        'reason',
        'adjusted_by',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'staff_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function leaveYear(): BelongsTo
    {
        return $this->belongsTo(LeaveYear::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
