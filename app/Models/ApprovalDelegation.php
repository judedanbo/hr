<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalDelegation extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'delegator_id',
        'delegate_id',
        'start_date',
        'end_date',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'delegator_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(InstitutionPerson::class, 'delegate_id');
    }

    public function isActive(): bool
    {
        $today = now()->startOfDay();

        return $this->start_date->lessThanOrEqualTo($today)
            && $this->end_date->greaterThanOrEqualTo($today);
    }

    /**
     * Active delegations for a given delegator (the head who is away).
     */
    public function scopeActiveFor(Builder $query, int $delegatorId): Builder
    {
        $today = now()->toDateString();

        return $query->where('delegator_id', $delegatorId)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }
}
