<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class JobStaff extends Pivot
{
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    /**
     * Get all of the jobs for the JobStaff
     */
    public function job(): BelongsTo
    {
        return $this->BelongsTo(Job::class);
    }

    /**
     * Get all of the staff for the JobStaff
     */
    public function staff(): BelongsTo
    {
        return $this->BelongsTo(InstitutionPerson::class,);
    }
}