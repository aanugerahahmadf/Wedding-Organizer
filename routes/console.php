<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    /** @var \Illuminate\Console\Command $this */
    echo Inspiring::quote().PHP_EOL;
})->purpose('Display an inspiring quote');

Schedule::command('app:update-order-status')->everyMinute();
Schedule::command('app:mark-expired-payments')->everyMinute();
