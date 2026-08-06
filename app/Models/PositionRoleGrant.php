<?php

namespace App\Models;

use App\Traits\LogAllTraits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

/**
 * Records that a role was given to a user because of a position assignment,
 * so it can be taken back when that assignment ends — and only then.
 */
class PositionRoleGrant extends Model
{
    use LogAllTraits;

    protected $fillable = [
        'user_id',
        'role_id',
        'position_staff_id',
        'position_id',
        'was_preexisting',
        'granted_by',
        'granted_at',
        'revoked_at',
    ];

    protected $casts = [
        'was_preexisting' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(PositionStaff::class, 'position_staff_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Grants still conferring a role: not revoked, and backed by an assignment
     * that is still open and not deleted. The assignment check makes the scope
     * self-healing if a grant row is ever left behind.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->whereHas('assignment', fn (Builder $query) => $query->inEffect());
    }
}
