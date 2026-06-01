<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveEntitlement extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'leave_year_id',
        'leave_type_id',
        'job_category_id',
        'days_allowed',
        'min_service_months',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'days_allowed' => 'integer',
            'min_service_months' => 'integer',
        ];
    }

    public function leaveYear(): BelongsTo
    {
        return $this->belongsTo(LeaveYear::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function jobCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }
}
