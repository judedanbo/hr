<?php

namespace App\Enums;

enum DistrictTypeEnum: int
{
    case DISTRICT = 1;
    case MUNICIPAL = 2;
    case METROPOLITAN = 3;

    public function label(): string
    {
        return match ($this) {
            self::DISTRICT => 'District',
            self::MUNICIPAL => 'Municipal',
            self::METROPOLITAN => 'Metropolitan',
            default => 'District type not provided'
        };
    }
}
