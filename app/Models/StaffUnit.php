<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StaffUnit extends Pivot
{
    protected $fillable = [
        'unit_id',
        'staff_id',
        'start_date',
        'end_date',
        'remarks'
    ];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

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