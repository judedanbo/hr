<?php

namespace App\Models;

use App\Enums\EmployeeStatusEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

class Status extends Pivot
{
    use SoftDeletes;

    protected $fillable = ['staff_id', 'status', 'description', 'start_date', 'end_date', 'institution_id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => EmployeeStatusEnum::class,
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }


    /**
     * Get the staff that owns the Status
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'staff_id', 'id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
