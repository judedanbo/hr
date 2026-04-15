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

    /**
     * Normalize a raw level string (legacy free-text or enum value) to a canonical case.
     * Returns null for unrecognized, blank, or "not available" entries.
     */
    public static function normalize(?string $raw): ?self
    {
        if ($raw === null) {
            return null;
        }

        $key = strtolower(trim(preg_replace('/[^a-z0-9]+/i', ' ', $raw) ?? ''));
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? '');

        if ($key === '' || in_array($key, ['not available', '08', '06'], true)) {
            return null;
        }

        // Exact enum values first.
        foreach (self::cases() as $case) {
            if ($key === $case->value) {
                return $case;
            }
        }

        return match (true) {
            str_contains($key, 'phd'),
            str_contains($key, 'doctor') => self::Doctorate,

            str_contains($key, 'mba'),
            str_contains($key, 'masters'),
            str_contains($key, 'master'),
            str_contains($key, '2nd degree'),
            str_contains($key, '2 degree'),
            str_contains($key, '2nd degre'),
            str_contains($key, '2nd dgree'),
            str_contains($key, 'second degree'),
            str_contains($key, '3 degree'),
            str_contains($key, 'nd degree'),
            $key === '2nd' => self::Masters,

            str_contains($key, 'post graduate dip'),
            str_contains($key, 'post grad dip'),
            str_contains($key, 'post grad.dip'),
            str_contains($key, 'post grad'),
            str_contains($key, 'post chartered dip'),
            str_contains($key, 'post charted dip'),
            str_contains($key, 'post ch dip'),
            str_contains($key, 'post charter'),
            str_contains($key, 'post grag'),
            str_contains($key, 'prof dip'),
            str_contains($key, 'prof. dip'),
            str_contains($key, 'pg diploma'),
            str_contains($key, 'pg_diploma') => self::PostGraduateDiploma,

            str_contains($key, 'post diploma cert'),
            str_contains($key, 'pg_certificate'),
            str_contains($key, 'pg certificate'),
            str_contains($key, 'icag'),
            str_contains($key, 'iicfa') => self::PostGraduateCertificate,

            str_contains($key, '1st degree'),
            str_contains($key, '1 degree'),
            str_contains($key, '1st degre'),
            str_contains($key, 'st degree'),
            str_contains($key, 'degree'),
            str_contains($key, 'degrre'),
            str_contains($key, 'll b'),
            str_contains($key, 'llb') => self::Degree,

            str_contains($key, 'hnd') => self::Hnd,

            str_contains($key, 'diploma'),
            str_contains($key, 'dipolma'),
            str_contains($key, 'dip'),
            str_contains($key, 'associate') => self::Diploma,

            str_contains($key, 'professiona'),
            str_contains($key, 'proffesional'),
            str_contains($key, 'profesional'),
            str_contains($key, 'professinal'),
            str_contains($key, 'prpfessional'),
            str_contains($key, 'professional') => self::Professional,

            str_contains($key, 'sss'),
            str_contains($key, 'ssce'),
            str_contains($key, 'wassce'),
            str_contains($key, 'w a s c e'),
            str_contains($key, 'gce'),
            str_contains($key, 'o level'),
            str_contains($key, 'a level'),
            str_contains($key, 'secondary'),
            str_contains($key, 'gov sec cert'),
            str_contains($key, 'middle school') => self::SssceWassce,

            str_contains($key, 'certificate'),
            str_contains($key, 'certification'),
            str_contains($key, 'tertiary'),
            str_contains($key, 'vocational'),
            str_contains($key, 'technical'),
            str_contains($key, 'nvti'),
            str_contains($key, 'nacvet'),
            str_contains($key, 'mslc'),
            str_contains($key, 'm s l c'),
            str_contains($key, 'primaey'),
            str_contains($key, 'jss'),
            str_contains($key, 'jhs'),
            str_contains($key, 'bece'),
            str_contains($key, 'basic'),
            str_contains($key, 'grade one'),
            str_contains($key, 'grade two'),
            str_contains($key, 'part i'),
            str_contains($key, 'part ii'),
            str_contains($key, 'part iii'),
            str_contains($key, 'part a'),
            str_contains($key, 'rsa'),
            str_contains($key, 'short course'),
            str_contains($key, 'final'),
            str_contains($key, 'special prog'),
            str_contains($key, 'private') => self::Certificate,

            default => null,
        };
    }
}
