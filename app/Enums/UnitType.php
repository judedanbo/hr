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
    case DISTRICT = 'DIS';
    case NOT_AVAILABLE = "";

    public function label(): string
    {
        return match ($this) {
            self::DEPARTMENT => 'Department',
            self::DIVISION => 'Division',
            self::SECTION => 'Section',
            self::MINISTRY => 'Ministry',
            self::BRANCH => 'Branch',
            self::DISTRICT => 'District',
            self::NOT_AVAILABLE => 'Unit not Provided',
            default => static::NOT_AVAILABLE,
        };
    }
}