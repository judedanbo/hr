<?php

namespace App\Enums;

enum DistrictTypeEnum: string
{
    case DISTRICT = 'district';
    case MUNICIPAL = 'municipal';
    case METROPOLITAN = 'metropolitan';

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
