<?php

namespace App\Enums;

enum LeavePlanStatusEnum: string
{
    case Draft = 'Draft';
    case Submitted = 'Submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
        };
    }
}
