<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;

final class LeaveReportFilter
{
    public function __construct(
        public readonly ?int $yearId = null,
        public readonly ?int $leaveTypeId = null,
        public readonly ?int $unitId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            yearId: $request->integer('year_id') ?: null,
            leaveTypeId: $request->integer('leave_type_id') ?: null,
            unitId: $request->integer('unit_id') ?: null,
        );
    }

    public function withUnitId(?int $unitId): self
    {
        return new self($this->yearId, $this->leaveTypeId, $unitId);
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return array_filter([
            'year_id' => $this->yearId,
            'leave_type_id' => $this->leaveTypeId,
            'unit_id' => $this->unitId,
        ], fn ($value): bool => $value !== null);
    }
}
