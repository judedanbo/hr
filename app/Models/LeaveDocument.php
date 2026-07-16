<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveDocument extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'leave_request_id',
        'title',
        'file_name',
        'file_type',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
