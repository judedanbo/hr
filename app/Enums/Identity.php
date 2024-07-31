<?php

namespace App\Enums;

enum Identity: string
{
    case NationalID = 'N';
    case GhanaCard = 'G';
    case Social_Security_Number = 'S';
    case Passport = 'P';
    case Driver_License = 'D';
    case TIN = 'T';

    public function label(): string
    {
        return match ($this) {
            self::NationalID => 'National ID',
            self::GhanaCard => 'Ghana Card',
            self::Social_Security_Number => 'Social Security Number',
            self::Passport => 'Passport',
            self::Driver_License => 'Driver License',
            self::TIN => 'Tax Identification Number',
        };
    }
}
