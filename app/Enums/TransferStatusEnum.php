<?php

namespace App\Enums;

enum TransferStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'resumed';
    // case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Resumed',
            // self::Rejected => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'dark:text-gray-100 text-gray-700',
            self::Approved => 'text-green-500',
            // self::Rejected => 'text-red-500',
        };
    }
}
