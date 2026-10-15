<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Beacon/siren status summary to Telegram at 6am, 9am, 12pm, 3pm, and 6pm daily.
Schedule::command('telegram:status-report')->cron('0 6,9,12,15,18 * * *');

// Same schedule, but as a rendered image (pie charts + offline beacon list).
Schedule::command('telegram:image-status-report')->cron('0 6,9,12,15,18 * * *');

// Beacon/siren status to Uplink Sentinel SysLink (skips quietly until configured in Settings > SysLink).
Schedule::command('syslink:push')->everyMinute()->withoutOverlapping();