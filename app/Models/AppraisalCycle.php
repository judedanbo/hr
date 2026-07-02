<?php

namespace App\Models;

use App\Enums\AppraisalCycleStatusEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalCycle extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'name',
        'year',
        'objective_window_start',
        'objective_window_end',
        'midyear_window_start',
        'midyear_window_end',
        'final_window_start',
        'final_window_end',
        'objectives_weight',
        'competencies_weight',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'objective_window_start' => 'date',
        'objective_window_end' => 'date',
        'midyear_window_start' => 'date',
        'midyear_window_end' => 'date',
        'final_window_start' => 'date',
        'final_window_end' => 'date',
        'objectives_weight' => 'integer',
        'competencies_weight' => 'integer',
        'status' => AppraisalCycleStatusEnum::class,
    ];

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class);
    }
}
