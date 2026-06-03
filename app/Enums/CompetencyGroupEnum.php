<?php

namespace App\Enums;

enum CompetencyGroupEnum: string
{
    case Core = 'core';
    case Leadership = 'leadership';
    case Technical = 'technical';

    public function label(): string
    {
        return match ($this) {
            self::Core => 'Core',
            self::Leadership => 'Leadership',
            self::Technical => 'Technical',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Core => 'text-blue-500',
            self::Leadership => 'text-purple-500',
            self::Technical => 'text-amber-500',
        };
    }
}
