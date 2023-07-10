<?php

namespace App\Models;

use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'type' => UnitType::class,
    ];

    protected $fillable = [
        'name',
        'type',
        'unit_id',
        'institution_id',
        'start_date',
        'end_date',
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

    /**
     * Get all of the subs for the Unit
     */
    public function subs(): HasMany
    {
        return $this->hasMany(Unit::class, 'unit_id', 'id');
    }
    public function divisions(): HasMany
    {
        return $this->hasMany(Unit::class, 'unit_id', 'id')->where('units.type', UnitType::Division);
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
        return $query->where('type', UnitType::Department);
    }

    public function scopeDivisions($query)
    {
        return $query->where('type', UnitType::Division);
    }

    public function scopeUnits($query)
    {
        return $query->where('type', UnitType::Unit);
    }

    /**
     * The staff that belong to the Department
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(InstitutionPerson::class, 'staff_unit', 'unit_id', 'staff_id')
            ->using(StaffUnit::class)
            ->withPivot('start_date', 'end_date')
            ->wherePivot('end_date', '>=', now());
        // ->where('status', 'Active');
    }
}