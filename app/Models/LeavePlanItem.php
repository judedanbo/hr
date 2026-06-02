<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeavePlanItem extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'leave_plan_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'proposed_days',
        'note',
        'converted_request_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'proposed_days' => 'integer',
        ];
    }

    public function leavePlan(): BelongsTo
    {
        return $this->belongsTo(LeavePlan::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
