<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalCompetencyRating extends Model
{
    use HasFactory, LogAllTraits;

    protected $fillable = [
        'appraisal_id',
        'appraisal_competency_id',
        'weight',
        'self_score',
        'supervisor_score',
        'comment',
    ];

    protected $casts = [
        'weight' => 'integer',
        'self_score' => 'decimal:2',
        'supervisor_score' => 'decimal:2',
    ];

    public function appraisal(): BelongsTo
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(AppraisalCompetency::class, 'appraisal_competency_id');
    }
}
