<?php

namespace App\Enums;

enum OfficeTypeEnum: string
{
    case HEADQUARTERS = 'headquarters';
    case REGIONAL = 'regional';
    case ANNEX = 'annex';
    case UNIT = 'unit';
    case SECTOR = 'sector';
    case BRANCH = 'branch';
    case DISTRICT = 'district';

    public function label(): string
    {
        return match ($this) {
            self::HEADQUARTERS => 'Headquarters',
            self::REGIONAL => 'Regional Office',
            self::DISTRICT => 'District Office',
            self::ANNEX => 'Headquarters Annex',
            self::UNIT => 'Unit Office',
            self::SECTOR => 'Sector Office',
            self::BRANCH => 'Branch Office',
            default => 'Office type not provided'
        };
    }
}
