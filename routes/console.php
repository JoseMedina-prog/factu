<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:send-overdue-invoices --days=1')
    ->dailyAt('08:00')
    ->name('notify-overdue-invoices')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('notifications:check-numbering-ranges')
    ->dailyAt('07:00')
    ->name('check-numbering-ranges')
    ->withoutOverlapping()
    ->onOneServer();