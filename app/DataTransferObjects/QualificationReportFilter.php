<?php

namespace App\DataTransferObjects;

use Illuminate\Http\Request;

final class QualificationReportFilter
{
    public function __construct(
        public readonly ?int $unitId = null,
        public readonly ?int $departmentId = null,
        public readonly ?string $level = null,
        public readonly ?string $status = null,
        public readonly ?int $yearFrom = null,
        public readonly ?int $yearTo = null,
        public readonly ?string $gender = null,
        public readonly ?int $jobCategoryId = null,
        public readonly ?string $institution = null,
        public readonly ?string $course = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->all());
    }

    public static function fromArray(array $data): self
    {
        return new self(
            unitId: self::nullableInt($data['unit_id'] ?? null),
            departmentId: self::nullableInt($data['department_id'] ?? null),
            level: self::nullableString($data['level'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            yearFrom: self::nullableInt($data['year_from'] ?? null),
            yearTo: self::nullableInt($data['year_to'] ?? null),
            gender: self::nullableString($data['gender'] ?? null),
            jobCategoryId: self::nullableInt($data['job_category_id'] ?? null),
            institution: self::nullableString($data['institution'] ?? null),
            course: self::nullableString($data['course'] ?? null),
        );
    }

    /** @return array<string, scalar> */
    public function toQueryArray(): array
    {
        return array_filter([
            'unit_id' => $this->unitId,
            'department_id' => $this->departmentId,
            'level' => $this->level,
            'status' => $this->status,
            'year_from' => $this->yearFrom,
            'year_to' => $this->yearTo,
            'gender' => $this->gender,
            'job_category_id' => $this->jobCategoryId,
            'institution' => $this->institution,
            'course' => $this->course,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function cacheKey(): string
    {
        $data = $this->toQueryArray();
        ksort($data);

        return md5(json_encode($data));
    }

    public function withUnitId(?int $unitId): self
    {
        return new self(
            unitId: $unitId,
            departmentId: $this->departmentId,
            level: $this->level,
            status: $this->status,
            yearFrom: $this->yearFrom,
            yearTo: $this->yearTo,
            gender: $this->gender,
            jobCategoryId: $this->jobCategoryId,
            institution: $this->institution,
            course: $this->course,
        );
    }

    private static function nullableInt(mixed $v): ?int
    {
        return ($v === null || $v === '') ? null : (int) $v;
    }

    private static function nullableString(mixed $v): ?string
    {
        return ($v === null || $v === '') ? null : (string) $v;
    }
}
