<?php

namespace App\Models;

use App\Enums\StaffTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'staff_type',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'staff_type' => StaffTypeEnum::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * get staff who are of a type
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'staff_id');
    }
}
