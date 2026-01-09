<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'San Vincenzo Calcio');
        $this->migrator->add('general.site_logo', null);
        $this->migrator->add('general.primary_color', '#3490dc');
    }
};
