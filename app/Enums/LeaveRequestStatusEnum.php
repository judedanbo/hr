<?php

namespace App\Enums;

enum LeaveRequestStatusEnum: string
{
    case Draft = 'Draft';
    case Pending = 'Pending';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Cancelled => 'Cancelled',
        };
    }
}
