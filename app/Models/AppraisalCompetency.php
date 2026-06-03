<?php

namespace App\Models;

use App\Enums\CompetencyGroupEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppraisalCompetency extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'group',
        'default_weight',
        'job_category_id',
        'is_active',
    ];

    protected $casts = [
        'group' => CompetencyGroupEnum::class,
        'default_weight' => 'integer',
        'is_active' => 'boolean',
    ];

    public function jobCategory(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
