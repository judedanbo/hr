<?php

namespace App\Enums;

enum ContactTypeEnum: int
{
    case EMAIL = 1;
    case pHONE = 2;
    case ADDRESS = 3;
    case GHPOSTGPS = 4;
    case EMERGENCY = 5;

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email Address',
            self::pHONE => 'Phone number',
            self::ADDRESS => 'Address',
            self::GHPOSTGPS => 'Ghana postGPS',
            self::EMERGENCY => 'Emergency contact',
        };
    }
}