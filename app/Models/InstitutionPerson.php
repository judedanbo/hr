<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Storage;

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
        'job_category_id',
    ];
    // protected $appends =  ['status'];

    protected $casts = [
        'hire_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * get staff types of a staff
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
            ->withPivot('start_date', 'end_date', 'remarks', 'status', 'old_data')
            ->using(StaffUnit::class)
            ->orderByPivot('created_at', 'desc')
            ->orderByPivot('start_date', 'desc')
            ->withTimestamps()
            // ->wherePivotNull('end_date')
            // ->whereNull('units.end_date');
            ->latest();
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'position_staff', 'staff_id', 'position_id')
            ->withPivot('start_date', 'end_date')
            ->withTimestamps()
            ->orderByPivot('start_date', 'desc')
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
        )
            ->withPivot(
                'start_date',
                'end_date',
                'remarks'
            )
            ->withTimestamps()
            ->using(JobStaff::class)
            ->orderByPivot('created_at', 'desc')
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

    public function scopeCurrentUnit($query)
    {
        $query->addSelect([
            'current_unit_id' => StaffUnit::select('id')
                ->whereColumn('institution_person.id', 'staff_unit.staff_id')
                ->latest('staff_unit.start_date')
                ->take(1),
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

    public function scopeUnit($query)
    {
        return $query->whereHas('units', function ($query) {
            $query->whereNull('staff_unit.end_date');
        });
    }

    public function scopePromotion($query, $year = null)
    {
        return $query->whereHas('ranks', function ($query) use ($year) {
            $searchYear = $year ?? $year - 3;
            $query->whereHas('category');
            $query->whereNull('job_staff.end_date');
            $query->whereRaw('YEAR(job_staff.start_date) < ?', [$searchYear]);
        });
    }

    public function scopeSearchPerson($query, $search)
    {
        return $query->whereHas('person', function ($personQuery) use ($search) {
            $personQuery->search($search);
        });
    }

    public function scopeSearchRank($query, $search)
    {
        return $query->orWhereHas('ranks', function ($rankQuery) use ($search) {
            $rankQuery->searchRank($search);
        });
    }

    public function scopeSearchOtherRank($query, $search)
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

    public function scopeCurrentRank($query)
    {
        $query->addSelect([
            'current_rank_id' => JobStaff::select('id')
                ->whereColumn('institution_person.id', 'job_staff.staff_id')
                ->latest('job_staff.start_date')
                ->take(1),
        ])->with(['currentRank' => function ($query) {
            $query->with(['job' => function ($query) {
                $query->whereNull('jobs.deleted_at');
                $query->with('category');
            }]);
        }]);
    }

    public function scopeCurrentRankName($query, $rank = null)
    {
        $query->addSelect([
            'current_rank_id' => JobStaff::select('id')
                ->whereColumn('institution_person.id', 'job_staff.staff_id')
                ->when($rank !== null, function ($query) use ($rank) {
                    $query->where('job_staff.job_id', $rank);
                })
                ->latest('job_staff.start_date')
                ->take(1),
        ])->with(['currentRank' => function ($query) {
            $query->with(['job' => function ($query) {
                $query->with('category');
            }]);
        }]);
    }

    // function scopeCurrentStatus()
    // {
    //     return $this->statuses()->first()->status->name;
    // }

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
        return $query->whereHas('statuses', function ($query) {
            $query->where(function ($query) {
                $query->where('status', 'A');
                $query->where(function ($query) {
                    $query->whereNull('end_date');
                    $query->orWhere('end_date', '>', now());
                });
            });
        });
    }

    public function scopeRetired($query)
    {
        // return $query->whereHas('currentStatus');
        return $query->whereHas('statuses', function ($query) {
            $query->where('status', '<>', 'A');
            $query->where(function ($query) {
                $query->whereNull('end_date');
                $query->orWhere('end_date', '>', now());
            });
        });
        // return $query->with(['statuses' => function ($query) {
        //     $query->whereNull('end_date');
        //     $query->where('status', '<>', 'A');
        // }]);
    }

    public function scopeToPromote($query)
    {
        return $query->whereHas('ranks', function ($query) {
            $query->whereNull('job_staff.end_date');
            $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
        });
    }

    public function scopeToPromoteApril($query)
    {
        return $query->whereHas('ranks', function ($query) {
            $query->whereNull('job_staff.end_date');
            $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
            $query->where(function ($query) {
                $query->whereRaw('month(job_staff.start_date) IN (1, 2, 3, 11, 12)');
                $query->orWhere(function ($query) {
                    $query->whereMonth('job_staff.start_date', 4);
                    $query->whereDay('job_staff.start_date', 1);
                });
                $query->orWhere(function ($query) {
                    $query->whereMonth('job_staff.start_date', 10);
                    $query->whereDay('job_staff.start_date', '>', 1);
                });
            });
        });
    }

    public function scopeToPromoteOctober($query)
    {
        return $query->whereHas('ranks', function ($query) {
            $query->whereNull('job_staff.end_date');
            $query->whereYear('job_staff.start_date', '<=', Carbon::now()->subYears(3));
            $query->where(function ($query) {
                $query->whereRaw('month(job_staff.start_date) IN (5, 6, 7, 8, 9)');
                $query->orWhere(function ($query) {
                    $query->whereMonth('job_staff.start_date', 10);
                    $query->whereDay('job_staff.start_date', 1);
                });
                $query->orWhere(function ($query) {
                    $query->whereMonth('job_staff.start_date', 4);
                    $query->whereDay('job_staff.start_date', '>', 1);
                });
            });
        });
    }

    public function scopeToRetire($query)
    {
        return $query->whereHas('person', function ($query) {
            $query->whereRaw('(DATEDIFF(NOW(), people.date_of_birth)/365) > 57');
        });
    }

    public function scopeCurrentStatus($query)
    {
        return $query->addSelect([
            'current_status' => Status::select('status')
                ->whereColumn('institution_person.id', 'staff_id')
                ->latest('start_date')
                ->take(1),
        ]);
        // ->with(['statuses' => function ($query) {
        //     $query->with('status');
        // }]);
        // return $query->whereRaw('(DATEDIFF(NOW(), people.date_of_birth)/365) > 60');
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
        return $this->hasMany(Status::class, 'staff_id', 'id')
            ->latest();
    }

    /**
     * Write a note for the staff
     */
    public function writeNote($note)
    {
        // dd($note);
        $files = [];
        foreach ($note['document'] as $file) {
            // dd($file['file']->getClientOriginalName());
            $fileDetails = [
                'document_type' => $note['note_type'],
                'document_title' => $file['file']->getClientOriginalName(),
                // 'document_number' => $file['document_number'],
                // 'document_file' => $file['document_file'],
                'file_type' => $file['file']->getMimeType(),
                'file_name' => Storage::disk('documents')->put('notes', $file['file']),
                // 'document_status' => $file['document_status'],
                // 'document_remarks' => $file['document_remarks'],
            ];
            $files[] = $fileDetails; //Storage::disk('documents')->put('notes', $file['file']);
        }
        // dd($files);
        $newNote = $this->notes()->create($note);
        $newNote->documents()->createMany($files);
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
                            $searchName->orWhere('maiden_name', 'like', "%{$term}%");
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

    public function scopeMaleStaff($query)
    {
        return $query->whereHas('person', function ($query) {
            $query->male();
        });
    }
    public function scopeFemaleStaff($query)
    {
        return $query->whereHas('person', function ($query) {
            $query->female();
        });
    }
}
