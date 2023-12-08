<?php

namespace App\Enums;

enum ContactTypeEnum: int
{
    case EMAIL = 1;
    case PHONE = 2;
    case ADDRESS = 3;
    case GHPOSTGPS = 4;
    case EMERGENCY = 5;

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => 'Email Address',
            self::PHONE => 'Phone number',
            self::ADDRESS => 'Address',
            self::GHPOSTGPS => 'Ghana PostGPS',
            self::EMERGENCY => 'Emergency contact',
        };
    }
}