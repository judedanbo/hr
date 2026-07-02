<?php

namespace App\Models;

use App\Enums\AppraisalStatusEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appraisal extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'appraisal_cycle_id',
        'staff_id',
        'appraiser_id',
        'reviewer_id',
        'unit_id',
        'status',
        'self_submitted_at',
        'objectives_score',
        'competencies_score',
        'supervisor_score',
        'overall_score',
        'overall_band',
        'acknowledged_at',
    ];

    protected $casts = [
        'status' => AppraisalStatusEnum::class,
        'self_submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'objectives_score' => 'decimal:2',
        'competencies_score' => 'decimal:2',
        'supervisor_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AppraisalCycle::class, 'appraisal_cycle_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'staff_id');
    }

    public function appraiser(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'appraiser_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'reviewer_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(AppraisalObjective::class);
    }

    public function competencyRatings(): HasMany
    {
        return $this->hasMany(AppraisalCompetencyRating::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(AppraisalStatusHistory::class)->latest();
    }

    public function isOwnedBy(User $user): bool
    {
        return in_array($this->staff_id, $user->staffIds(), true);
    }

    public function isAppraiserUser(User $user): bool
    {
        return $this->appraiser_id !== null && in_array($this->appraiser_id, $user->staffIds(), true);
    }

    public function isReviewerUser(User $user): bool
    {
        return $this->reviewer_id !== null && in_array($this->reviewer_id, $user->staffIds(), true);
    }
}
