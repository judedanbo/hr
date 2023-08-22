<?php

namespace App\Enums;

enum EmployeeStatusEnum: String
{
    case Active = 'A';
    case Inactive = 'I';
    case Suspended = 'S';
    case Left = 'L';

    public  function label(): string
    {
        return match ($this) {
            self::Active => 'Active staff',
            self::Inactive => 'Inactive staff',
            self::Suspended => 'Suspended staff',
            self::Left => 'Separated staff',
        };
    }
}