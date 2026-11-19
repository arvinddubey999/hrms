<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto check for employees who forgot to punch out (30 mins after shift end)
Schedule::command('attendance:check-forgot-punchout')->everyFifteenMinutes();

// Auto process recurring tasks iterations
Schedule::command('tasks:process-recurring')->hourly();
