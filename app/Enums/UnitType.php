<?php

namespace App\Enums;

enum UnitType: string
{
    case DEPARTMENT = 'DEP';
    case DIVISION = 'DIV';
    case UNIT = 'MU';
    case SECTION = 'SEC';
    case MINISTRY = 'MIN';
    case BRANCH = 'BRH';
    case REGION = 'REG';
    case DISTRICT = 'DIS';

    public function label(): string
    {
        return match ($this) {
            self::DEPARTMENT => 'Department',
            self::DIVISION => 'Division',
            self::SECTION => 'Section',
            self::MINISTRY => 'Ministry',
            self::BRANCH => 'Branch',
            self::REGION => 'Regional Office',
            self::DISTRICT => 'District Office',
            default => "Unit type not Provided",
        };
    }
}