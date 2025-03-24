<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PositionStaff extends Pivot
{
    use LogAllTraits;
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
