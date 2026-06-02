<?php

namespace App\Models;

use App\Enums\LeaveRequestStatusEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'leave_year_id',
        'leave_plan_item_id',
        'start_date',
        'end_date',
        'requested_days',
        'reason',
        'address_during_leave',
        'contact_during_leave',
        'relieving_officer_id',
        'status',
        'approved_days',
        'approver_id',
        'decided_by',
        'decided_at',
        'decline_reason',
        'is_backdated',
        'actual_return_date',
        'actual_days',
        'amended_from_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'requested_days' => 'integer',
            'approved_days' => 'integer',
            'actual_days' => 'integer',
            'decided_at' => 'datetime',
            'actual_return_date' => 'date',
            'is_backdated' => 'boolean',
            'status' => LeaveRequestStatusEnum::class,
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

    public function relievingOfficer(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'relieving_officer_id');
    }

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(LeavePlanItem::class, 'leave_plan_item_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'approver_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function amendedFrom(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'amended_from_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LeaveDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeaveRequestStatusHistory::class)->latest('id');
    }
}
