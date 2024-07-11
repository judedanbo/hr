<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\JobCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Job extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'institution_id', 'job_category_id'];

    /**
     * Get the institution that owns the Job
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * The staff that belong to the Job
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'job_staff', 'job_id', 'staff_id')
            ->withPivot(
                'start_date',
                'end_date',
                'remarks'
            );
    }
    public function activeStaff(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'job_staff', 'job_id', 'staff_id')
            ->withPivot('start_date', 'end_date', 'remarks')
            ->active()
            ->wherePivotNull('end_date')
            ->orWherePivot('end_date', '>', now());
    }
    public function staffToPromote(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'job_staff', 'job_id', 'staff_id')
            ->withPivot('start_date', 'end_date', 'remarks')
            ->active()
            ->toPromote()
            ->wherePivotNull('end_date');
        // ->orWherePivot('end_date', '>', now());
    }
    public function staffToPromoteApril(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'job_staff', 'job_id', 'staff_id')
            ->withPivot('start_date', 'end_date', 'remarks')
            ->active()
            ->toPromoteApril()
            ->wherePivotNull('end_date')
            ->orWherePivot('end_date', '>', now());
    }
    public function staffToPromoteOctober(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'job_staff', 'job_id', 'staff_id')
            ->withPivot('start_date', 'end_date', 'remarks')
            ->active()
            ->toPromoteOctober()
            ->wherePivotNull('end_date')
            ->orWherePivot('end_date', '>', now());
    }


    /**
     * Get the previousRank associated with the Job
     */
    public function previousRank(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'previous_rank_id');
    }

    /**
     * Get the job category associated with the Job
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id', 'id');
    }
    public function next()
    {
        return $this->category();
        // return $this->hasMany(Job::class, 'next_rank_id');
    }

    public function jobStaff(): HasMany
    {
        return $this->hasMany(JobStaff::class);
    }

    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(Unit::class, JobStaff::class, 'job_id', 'id', 'id', 'unit_id');
    }

    public function scopeManagementRanks($query)
    {
        return $query->WhereHas('category', function ($whereHasQuery) {
            $whereHasQuery->where("level", "<", 3);
        });
    }
    public function scopeOtherRanks($query)
    {
        return $query->WhereHas('category', function ($whereHasQuery) {
            $whereHasQuery->where("level", ">=", 2);
        });
    }

    public function scopeSearchRank($query, $search)
    {
        $query->when($search, function ($query, $search) {
            $terms = explode(' ', $search);
            foreach ($terms as $term) {
                $query->where(function ($searchRank) use ($term) {
                    $searchRank->where('name', 'like', "%{$term}%");
                    $searchRank->orWhereHas('category', function ($searchRank) use ($term) {
                        $searchRank->where('name', 'like', "%{$term}%");
                        $searchRank->orWhere('short_name', 'like', "%{$term}%");
                    });
                });
            }
        });
    }
}
