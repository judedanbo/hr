<?php

namespace App\Enums;

enum MaritalStatusEnum: String
{
    case SINGLE = 'S';
    case MARRIED = 'M';
    case WIDOWED = 'W';
    case DEVOICED = 'D';
    case NOT_AVAILABLE = '';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single',
            self::MARRIED => 'Married',
            self::WIDOWED => 'Widowed',
            self::DEVOICED => 'Devoiced',
            self::NOT_AVAILABLE => 'Not Provided',
            default => static::NOT_AVAILABLE,
        };
    }
}