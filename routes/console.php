<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly expiry check at 08:00 — updates document statuses to
// 'expiring_soon' / 'expired', sends notifications, and recalculates
// vendor compliance scores. Safe to run manually at any time:
//   php artisan compliance:check-expiry
//   php artisan compliance:check-expiry --dry-run
Schedule::command('compliance:check-expiry')->dailyAt('08:00');
