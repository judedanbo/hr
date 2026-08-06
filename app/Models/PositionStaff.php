<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class PositionStaff extends Pivot
{
    use LogAllTraits, SoftDeletes;

    protected $table = 'position_staff';

    /**
     * Pivot defaults to a non-incrementing, keyless model. This pivot carries
     * its own `id`, is route-model bound and is created directly, so both must
     * be restored.
     */
    protected $primaryKey = 'id';

    public $incrementing = true;

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

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'staff_id');
    }

    public function roleGrants(): HasMany
    {
        return $this->hasMany(PositionRoleGrant::class, 'position_staff_id');
    }

    /**
     * Assignments that have not been closed out.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('end_date');
    }

    /**
     * Assignments still in effect: never ended, or ending on a future date.
     * Only these confer the roles mapped to their position.
     */
    public function scopeInEffect(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('end_date')
                ->orWhereDate('end_date', '>=', Carbon::today());
        });
    }

    public function isInEffect(): bool
    {
        return $this->deleted_at === null
            && ($this->end_date === null || $this->end_date->gte(Carbon::today()));
    }
}
