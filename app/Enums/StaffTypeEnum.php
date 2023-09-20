<?php

namespace App\Enums;

enum StaffTypeEnum: string
{
    case NotProvided = '';
    case Field  = 'FS';
    case Supporting = 'SS';
    case SupportService = 'FSS';

    public function label(): string
    {
        return match ($this) {
            self::Field => 'Field Staff',
            self::Supporting => 'Supporting Staff',
            self::SupportService => 'Field Staff (Suppt Serv)',
            self::NotProvided => 'Not Provided',
            default => static::NotProvided,
        };
    }
}