<?php

namespace App\Enums;

enum OfficeTypeEnum: int
{
    case HEADQUARTERS = 1;
    case REGIONAL = 2;
    case DISTRICT = 3;

    public function label(): string
    {
        return match ($this) {
            self::HEADQUARTERS => 'Headquarters',
            self::REGIONAL => 'Regional Office',
            self::DISTRICT => 'District Office',
            default => 'Office type not provided'
        };
    }
}