<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The gateway's EXPIRED notification is the normal signal, but a callback can be
// lost entirely, so both sweeps run on a schedule as a safety net.
Schedule::command('tripay:expire-stale-payments')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('tripay:reconcile-payments')
    ->hourly()
    ->withoutOverlapping();
