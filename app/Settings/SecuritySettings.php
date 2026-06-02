<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    public int $password_change_interval_days;

    public static function group(): string
    {
        return 'security';
    }
}
