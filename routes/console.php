<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function (): void {
    /** @var \Illuminate\Console\Command $this */
    echo Inspiring::quote().PHP_EOL;
})->purpose('Display an inspiring quote');
