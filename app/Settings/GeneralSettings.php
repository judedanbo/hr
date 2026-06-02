<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $org_name;

    public ?string $support_email;

    public string $date_format;

    public int $pagination_size;

    public static function group(): string
    {
        return 'general';
    }
}
