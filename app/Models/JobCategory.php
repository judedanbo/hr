<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCategory extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    protected $fillable = ['name', 'short_name', 'level', 'job_category_id', 'description', 'institution_id', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the institution that owns the JobCategory
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get all of the jobs for the JobCategory
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * Get staff of the JobCategory
     */
    public function staff()
    {
        return $this->hasManyThrough(JobStaff::class, Job::class);
    }

    /**
     * Get the parent that owns the JobCategory
     */
    public function parent()
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id', 'id');
    }
}
