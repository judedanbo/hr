<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JobStaff extends Pivot
{
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];
}