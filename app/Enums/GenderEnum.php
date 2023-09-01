<?php

namespace App\Enums;

enum GenderEnum: string
{
    case MALE = 'M';
    case FEMALE = 'F';
    case NOT_AVAILABLE = "";

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