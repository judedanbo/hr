<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PositionStaff extends Pivot
{
    protected $table = 'position_staff';

    protected $fillable = [
        'staff_id',
        'position_id',
        'start_date',
        'end_date',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
