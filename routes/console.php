<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send reminders daily to submitters who have not completed their form after 3 days
Schedule::command('vouch:send-reminders')->dailyAt('09:00');

// Expire overdue submissions hourly
Schedule::command('vouch:expire-submissions')->hourly();
