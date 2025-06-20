<?php

namespace App\Models;

use App\Enums\UnitType;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Unit extends Model
{
    use HasFactory, SoftDeletes, LogAllTraits;

    protected $casts = [
        'type' => UnitType::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $fillable = [
        'name',
        'type',
        'unit_id',
        'institution_id',
        'start_date',
        'end_date',
        'region_id',
    ];

    /**
     * Get the institution that owns the Unit
     *
     * @return \Illuminate\BelongsToDatabase\Eloquent\Relations\
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the parent that owns the Unit
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function region(): BelongsToMany
    {
        return $this->belongsToMany(Region::class)
            ->latest();
    }

    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(Office::class)
            ->latest();
    }

    public function currentOffice(): HasOne
    {
        return $this->hasOne(Office::class, 'id', 'id')->latestOfMany();
    }
    /**
     * Get all of the subs for the Unit
     */
    public function subs(): HasMany
    {
        return $this->hasMany(Unit::class, 'unit_id', 'id')
            ->whereNull('end_date');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Unit::class, 'unit_id', 'id')->where('units.type', UnitType::DIVISION);
    }

    // public function scopeDepartments($query)
    // {
    //     $query->whereNull('unit_id');
    // }

    public function scopeCountSubs($query)
    {
        $subQuery = DB::table('units as subUnits')
            ->selectRaw('count(*)')
            ->whereRaw('subUnits.unit_id = units.id');

        // return $query->select('units.*')->selectSub($subQuery, 'sub_number')->withCount('subs');
        return $query->select('units.*')
            ->withCount('subs')
            ->with(['subs' => function ($q) {
                $q->withCount('subs');
            }]);
    }

    public function scopeDepartments($query)
    {
        return $query
            ->where('type', UnitType::DEPARTMENT)
            ->whereNull('end_date');
    }

    public function scopeDivisions($query)
    {
        return $query
            ->where('type', UnitType::DIVISION)
            ->whereNull('end_date');
    }

    public function scopeUnits($query)
    {
        return $query
            ->where('type', UnitType::UNIT)
            ->whereNull('end_date');
    }

    /**
     * The staff that belong to the Department
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'staff_unit', 'unit_id', 'staff_id')
            ->using(StaffUnit::class)
            ->withPivot('start_date', 'end_date') //unit_id
            ->wherePivotNull('end_date'); //staff_id
        // ->where('status', 'Active');
    }

    public function scopeSearchUnit($query, $search)
    {

        $query->when($search, function ($query, $search) {
            $terms = explode(' ', $search);
            foreach ($terms as $term) {
                $query->where(function ($searchName) use ($term) {
                    $searchName->where('name', 'like', "%{$term}%");
                });
            }
        });
    }
}
