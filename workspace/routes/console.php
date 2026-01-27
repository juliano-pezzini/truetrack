<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled Tasks
Schedule::command('ofx:cleanup-expired')->dailyAt('02:00');
Schedule::command('xlsx:cleanup-expired')->dailyAt('02:00');
Schedule::command('xlsx:cleanup-hashes')->weeklyOn(0, '03:00'); // Sunday at 3:00 AM
