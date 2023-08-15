<?php

namespace App\Enums;

enum MaritalStatusEnum: String
{
    case SINGLE = 'S';
    case MARRIED = 'M';
    case WIDOWED = 'W';
    case DEVOICED = 'D';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single',
            self::MARRIED => 'Married',
            self::WIDOWED => 'Widowed',
            self::DEVOICED => 'Devoiced'
        };
    }
}