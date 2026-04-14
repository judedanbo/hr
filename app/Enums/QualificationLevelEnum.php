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
}
