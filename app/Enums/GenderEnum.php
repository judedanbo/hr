<?php

namespace App\Enums;

enum GenderEnum: string
{
    case NOT_AVAILABLE = "";
    case MALE = 'M';
    case FEMALE = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::NOT_AVAILABLE => 'Gender not Provided',
            default => static::NOT_AVAILABLE,
        };
    }
}