<?php

namespace App\Enums;

enum QualificationLevelEnum: string
{
    case SssceWassce = 'sssce_wassce';
    case Certificate = 'certificate';
    case Diploma = 'diploma';
    case Hnd = 'hnd';
    case Degree = 'degree';
    case PostGraduateCertificate = 'pg_certificate';
    case PostGraduateDiploma = 'pg_diploma';
    case Masters = 'masters';
    case Doctorate = 'doctorate';
    case Professional = 'professional';

    public function label(): string
    {
        return match ($this) {
            self::SssceWassce => 'SSSCE/WASSCE',
            self::Certificate => 'Certificate',
            self::Diploma => 'Diploma',
            self::Hnd => 'HND',
            self::Degree => 'Degree',
            self::PostGraduateCertificate => 'Post Graduate Certificate',
            self::PostGraduateDiploma => 'Post Graduate Diploma',
            self::Masters => 'Masters',
            self::Doctorate => 'Doctorate/PHD',
            self::Professional => 'Professional',
        };
    }

    /**
     * Numeric ordinality for "highest qualification per person" calculations.
     * Higher number = higher qualification.
     */
    public function rank(): int
    {
        return match ($this) {
            self::SssceWassce => 10,
            self::Certificate => 20,
            self::Professional => 25,
            self::Diploma => 30,
            self::Hnd => 40,
            self::Degree => 50,
            self::PostGraduateCertificate => 60,
            self::PostGraduateDiploma => 70,
            self::Masters => 80,
            self::Doctorate => 90,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function orderedByRank(): array
    {
        $cases = self::cases();
        usort($cases, fn (self $a, self $b) => $a->rank() <=> $b->rank());

        return $cases;
    }
}
