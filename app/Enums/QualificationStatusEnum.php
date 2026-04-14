<?php

namespace App\Enums;

enum QualificationStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'text-yellow-600 dark:text-yellow-400',
            self::Approved => 'text-green-600 dark:text-green-400',
            self::Rejected => 'text-red-600 dark:text-red-400',
        };
    }
}
