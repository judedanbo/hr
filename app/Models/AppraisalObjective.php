<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalObjective extends Model
{
    use HasFactory, LogAllTraits;

    protected $fillable = [
        'appraisal_id',
        'title',
        'description',
        'weight',
        'measure',
        'midyear_progress',
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
}
