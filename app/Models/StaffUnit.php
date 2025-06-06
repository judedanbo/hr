<?php

namespace App\Models;

use App\Enums\TransferStatusEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StaffUnit extends Pivot
{
    use LogAllTraits;
    protected $fillable = [
        'unit_id',
        'staff_id',
        'status',
        'start_date',
        'end_date',
        'remarks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => TransferStatusEnum::class,
    ];

    /**
     * Get the unit that owns the StaffUnit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    /**
     * Get the staff that owns the StaffUnit
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class);
    }
}
