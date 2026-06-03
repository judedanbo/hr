<?php

namespace App\Enums;

enum AppraisalCycleStatusEnum: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'dark:text-gray-100 text-gray-700',
            self::Open => 'text-green-500',
            self::Closed => 'text-red-500',
        };
    }
}
