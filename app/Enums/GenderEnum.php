<?php

namespace App\Enums;

enum GenderEnum: string
{
    case MALE = 'M';
    case FEMALE = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female'
        };
    }
}