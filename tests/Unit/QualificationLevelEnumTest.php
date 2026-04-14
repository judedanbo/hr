<?php

namespace Tests\Unit;

use App\Enums\QualificationLevelEnum;
use PHPUnit\Framework\TestCase;

class QualificationLevelEnumTest extends TestCase
{
    public function test_rank_orders_levels_from_lowest_to_highest(): void
    {
        $ordered = [
            QualificationLevelEnum::SssceWassce,
            QualificationLevelEnum::Certificate,
            QualificationLevelEnum::Diploma,
            QualificationLevelEnum::Hnd,
            QualificationLevelEnum::Degree,
            QualificationLevelEnum::PostGraduateCertificate,
            QualificationLevelEnum::PostGraduateDiploma,
            QualificationLevelEnum::Masters,
            QualificationLevelEnum::Doctorate,
        ];

        $ranks = array_map(fn ($level) => $level->rank(), $ordered);
        $sorted = $ranks;
        sort($sorted);
        $this->assertSame($sorted, $ranks, 'rank() should order lowest->highest in the given sequence');
    }

    public function test_professional_has_nonzero_rank(): void
    {
        $this->assertGreaterThan(0, QualificationLevelEnum::Professional->rank());
    }

    public function test_ordered_by_rank_returns_all_cases_sorted(): void
    {
        $ordered = QualificationLevelEnum::orderedByRank();
        $this->assertCount(count(QualificationLevelEnum::cases()), $ordered);
        $prev = -1;
        foreach ($ordered as $case) {
            $this->assertGreaterThanOrEqual($prev, $case->rank());
            $prev = $case->rank();
        }
    }
}
