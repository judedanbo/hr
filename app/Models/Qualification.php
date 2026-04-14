<?php

namespace App\Models;

use App\Enums\QualificationStatusEnum;
use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Qualification extends Model
{
    use HasFactory, LogAllTraits, SoftDeletes;

    protected $fillable = [
        'person_id',
        'course',
        'institution',
        'qualification',
        'qualification_number',
        'level',
        'pk',
        'year',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status' => QualificationStatusEnum::class,
        'approved_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * Scope to get only pending qualifications.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', QualificationStatusEnum::Pending);
    }

    /**
     * Scope to get only approved qualifications.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', QualificationStatusEnum::Approved);
    }

    /**
     * Scope to filter qualifications visible to a user.
     * - Approved qualifications are visible to everyone
     * - Pending qualifications are visible to the owner or users with approval permission
     */
    public function scopeVisibleTo(Builder $query, $user, ?int $personId = null): Builder
    {
        // If user has approval permission, show all
        if ($user->can('approve staff qualification')) {
            return $query;
        }

        // Otherwise, show approved OR own pending
        return $query->where(function ($q) use ($user, $personId) {
            $q->where('status', QualificationStatusEnum::Approved)
                ->orWhere(function ($q2) use ($user, $personId) {
                    $q2->where('status', QualificationStatusEnum::Pending);

                    // If personId provided, check against it
                    if ($personId !== null) {
                        $q2->where('person_id', $personId);
                    } elseif ($user->person) {
                        // Otherwise check if user's person matches
                        $q2->where('person_id', $user->person->id);
                    }
                });
        });
    }

    /**
     * Check if the qualification can be edited by the given user.
     */
    public function canBeEditedBy($user): bool
    {
        // Only pending qualifications can be edited
        if ($this->status !== QualificationStatusEnum::Pending) {
            return false;
        }

        // User must own the qualification (via their person record)
        if ($user->person && $this->person_id === $user->person->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if the qualification can be deleted by the given user.
     */
    public function canBeDeletedBy($user): bool
    {
        return $this->canBeEditedBy($user);
    }
}
