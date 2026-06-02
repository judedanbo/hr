<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.org_name', 'HRMIS');
        $this->migrator->add('general.support_email', null);
        $this->migrator->add('general.date_format', 'd M Y');
        $this->migrator->add('general.pagination_size', 10);
        $this->migrator->add('security.password_change_interval_days', 90);
    }
};
