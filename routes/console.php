<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/* usage detail older than a month, daily usage counts older than 400 days —
   the scheduler is off on Cloud (scale to zero), so staff sign-in also does
   this once a day (UsageLog::pruneOldOncePerDay) */
Schedule::command('model:prune')->daily();
