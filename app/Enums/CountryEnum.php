<?php

namespace App\Enums;

enum CountryEnum: string
{
    case GHANA = 'GH';

    public function label(): string
    {
        return match ($this) {
            self::GHANA => 'Ghana'
        };
    }

    public function nationality(): string
    {
        return match ($this) {
            self::GHANA => 'Ghanaian'
        };
    }
}
