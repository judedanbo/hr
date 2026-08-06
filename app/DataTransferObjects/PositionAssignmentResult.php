<?php

namespace App\DataTransferObjects;

use App\Models\PositionStaff;

/**
 * Outcome of assigning or updating a staff position.
 *
 * Role grants can legitimately be skipped — most often because the staff
 * member has no linked user account — so the warning travels back with the
 * assignment instead of failing it.
 */
final class PositionAssignmentResult
{
    /**
     * @param  array<int, string>  $grantedRoles  Names of roles granted by this assignment
     */
    public function __construct(
        public readonly PositionStaff $assignment,
        public readonly array $grantedRoles = [],
        public readonly ?string $warning = null,
    ) {}
}
