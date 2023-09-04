<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StaffUnit extends Pivot
{
    protected $fillable = [
        'unit_id',
        'staff_id',
        'start_date',
        'end_date',
    ];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];
}