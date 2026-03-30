<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pds:migrate-json', function () {
    $this->call('pds:migrate-json:run');
})->purpose('Migrate existing PDS JSON columns into normalized tables');

Schedule::command('pds:purge-temp')->hourly();
