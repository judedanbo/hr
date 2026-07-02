<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalRatingLevel extends Model
{
    use HasFactory, LogAllTraits;

    protected $fillable = [
        'value',
        'label',
        'min_score',
        'max_score',
        'description',
        'color',
    ];

    protected $casts = [
        'value' => 'integer',
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    /**
     * Resolve the rating level whose score band contains the given score.
     */
    public static function bandFor(float $score): ?self
    {
        return static::query()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderByDesc('value')
            ->first();
    }
}
