<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JobStaff extends Pivot
{
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    /**
     * Get all of the jobs for the JobStaff
     */
    public function job(): HasOne
    {
        return $this->HasOne(Job::class, 'id', 'job_id');
    }

    /**
     * Get all of the staff for the JobStaff
     */
    public function staff(): HasOne
    {
        return $this->HasOne(InstitutionPerson::class, 'id', 'staff_id');
    }
}
