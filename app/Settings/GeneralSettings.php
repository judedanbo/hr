<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $org_name = 'HRMIS';

    public ?string $support_email = null;

    public string $date_format = 'd M Y';

    public int $pagination_size = 10;

    public static function group(): string
    {
        return 'general';
    }
}
