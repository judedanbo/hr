<?php

namespace App\Models;

use App\Http\Requests\StoreNoteRequest;
use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InstitutionPerson extends Pivot
{
    protected $fillable = [
        'institution_id',
        'person_id',
        'file_number',
        'staff_number',
        'email',
        'old_staff_number',
        'hire_date',
        'end_date',
        'job_category_id'
    ];
    // protected $appends =  ['status'];

    protected $casts = [
        'hire_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * get staff types of a staff
     *
     */
    public function type(): HasMany
    {
        return $this->hasMany(StaffType::class, 'staff_id')->latest();
    }
    /**
     * Get the person that owns the InstitutionPerson
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the institution that owns the InstitutionPerson
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get all of the units for the InstitutionPerson
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function units(): belongsToMany
    {
        return $this->belongsToMany(Unit::class, 'staff_unit', 'staff_id', 'unit_id')
            ->withPivot('start_date', 'end_date', 'remarks', 'old_data')
            ->using(StaffUnit::class)
            ->orderByPivot('start_date', 'desc')
            // ->wherePivotNull('end_date')
            // ->whereNull('units.end_date');
            ->latest();
    }

    /**
     * The ranks that belong to the InstitutionPerson
     */
    public function ranks(): BelongsToMany
    {
        return $this->belongsToMany(
            Job::class,
            'job_staff',
            'staff_id',
            'job_id'
        )->withPivot(
            'start_date',
            'end_date',
            'remarks'
        )
            ->using(JobStaff::class)
            ->orderByPivot('start_date', 'desc')
            // ->wherePivotNull('end_date')
            ->latest();
    }

    public function allUnits(): HasMany
    {
        return $this->hasMany(StaffUnit::class, 'staff_id')
            ->latest('start_date');
    }

    public function currentUnit(): BelongsTo
    {
        return $this->BelongsTo(StaffUnit::class);
    }

    public function scopeWithCurrentUnit($query)
    {
        $query->addSelect([
            'current_unit_id' => StaffUnit::select('id')
                ->whereColumn('institution_person.id', 'staff_unit.staff_id')
                ->latest('staff_unit.start_date')
                ->take(1)
        ])->with(['currentUnit' => function ($query) {
            $query->with('unit:id,name');
        }]);
    }

    public function scopeRank($query)
    {
        return $query->whereHas('ranks', function ($query) {
            $query->whereNull('job_staff.end_date');
        });
    }
    function scopeUnit($query)
    {
        return $query->whereHas('units', function ($query) {
            $query->whereNull('staff_unit.end_date');
        });
    }
    public function scopePromotion($query, $year = Null)
    {
        return $query->whereHas('ranks', function ($query) use ($year) {
            $searchYear = $year  ?? $year - 3;
            $query->whereHas('category');
            $query->whereNull('job_staff.end_date');
            $query->whereRaw("YEAR(job_staff.start_date) < ?", [$searchYear]);
        });
    }

    function scopeSearchPerson($query, $search)
    {
        return $query->whereHas('person', function ($personQuery) use ($search) {
            $personQuery->search($search);
        });
    }
    function scopeSearchRank($query, $search)
    {
        return $query->orWhereHas('ranks', function ($rankQuery) use ($search) {
            $rankQuery->searchRank($search);
        });
    }

    function scopeSearchOtherRank($query, $search)
    {
        return $query->orWhereHas('ranks', function ($rankQuery) use ($search) {
            $rankQuery->searchOtherRank($search);
            // $rankQuery->searchRank($search);
        });
    }
    public function currentRank(): BelongsTo
    {
        return $this->BelongsTo(JobStaff::class);
    }

    public function scopeWithCurrentRank($query)
    {
        $query->addSelect([
            'current_rank_id' => JobStaff::select('id')
                ->whereColumn('institution_person.id', 'job_staff.staff_id')
                ->latest('job_staff.start_date')
                ->take(1)
        ])->with(['currentRank' => function ($query) {
            $query->with('job:id,name');
        }]);
    }



    // get current rank of staff
    // public function getCurrentRankAttribute()
    // {
    //     return $this->ranks()->first();
    // }
    // public function getCurrentUnitAttribute()
    // {
    //     return $this->units->first();
    // }

    public function dependents(): HasMany
    {
        return $this->hasMany(Dependent::class, 'staff_id');
    }

    public function scopeActive($query)
    {
        // return $query->addSelect([
        //     'staff_status' => Status::select('status')
        //         ->whereColumn('institution_person.id', 'status.staff_id')
        //         // ->whereNull('status.end_date')
        //         // ->whereNotNull('status.status')
        //         ->where('status.status', 'A')
        //         ->latest('status.start_date')
        //         ->take(1)
        // ]);
        return $query->whereHas('statuses', function ($query) {
            $query->whereNull('end_date');
            $query->where('status', 'A');
        });
    }

    public function scopeRetired($query)
    {
        return $query->with(['statuses' => function ($query) {
            $query->whereNull('end_date');
            $query->where('status', '<>', 'A');
        }]);
    }

    public function scopeCurrentStatus($query)
    {
        return $query->whereRaw('(DATEDIFF(NOW(), people.date_of_birth)/365) > 60');
    }

    // public function getCurrentStatusAttribute()
    // {
    //     // if ($this->person->age > 59) {
    //     //     return 'Retired';
    //     // };
    //     return $this->statuses()->first()->status->name;
    // }

    /**
     * Get all of the statuses for the InstitutionPerson
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(Status::class, 'staff_id', 'id')->latest();
    }

    /**
     * Write a note for the staff
     */
    public function writeNote($note)
    {
        $this->notes()->create($note);
    }

    /** Get staff's notes */

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable')->latest();
    }



    public function scopeManagement($query)
    {
        return $query->whereHas('ranks', function ($whereHasQuery) {
            $whereHasQuery->managementRanks();
        });
    }
    public function scopeOtherRanks($query)
    {
        return $query->whereHas('ranks', function ($whereHasQuery) {
            $whereHasQuery->otherRanks();
        });
    }


    public function scopeSearch($query, $search)
    {
        $query->when($search, function ($query, $search) {
            $query->where(function ($whereQry) use ($search) {
                $whereQry->where('staff_number', 'like', "%{$search}%");
                $whereQry->orWhere('file_number', 'like', "%{$search}%");
                $whereQry->orWhere('old_staff_number', 'like', "%{$search}%");
                $whereQry->orWhereYear('hire_date', $search);
                $whereQry->orWhereRaw('monthname(hire_date) like ?', ['%' . $search . '%']);
                $whereQry->orWhereHas('person', function ($perQuery) use ($search) {
                    $terms = explode(' ', $search);
                    foreach ($terms as $term) {
                        $perQuery->where(function ($searchName) use ($term) {
                            $searchName->where('first_name', 'like', "%{$term}%");
                            $searchName->orWhere('other_names', 'like', "%{$term}%");
                            $searchName->orWhere('surname', 'like', "%{$term}%");
                            $searchName->orWhere('date_of_birth', 'like', "%{$term}%");
                            $searchName->orWhereRaw('monthname(date_of_birth) like ?', [$term]);
                        });
                    }
                });
                $whereQry->orWhereHas('ranks', function ($rankQuery) use ($search) {
                    $rankQuery->where('name', 'like', "%{$search}%");
                    $rankQuery->whereNull('job_staff.end_date');
                });
                $whereQry->orWhereHas('units', function ($jobQuery) use ($search) {
                    $jobQuery->where('name', 'like', "%{$search}%");
                    $jobQuery->whereNull('staff_unit.end_date');
                });
            });
        });
    }
}
