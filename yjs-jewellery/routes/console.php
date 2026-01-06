<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncShipmentTracking;
use App\Jobs\ExpireLoyaltyPoints;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file defines scheduled commands for the application.
|
*/

// Sync shipment tracking every 4 hours
Schedule::job(new SyncShipmentTracking)
    ->everyFourHours()
    ->name('sync-shipment-tracking')
    ->withoutOverlapping()
    ->onOneServer();

// Expire loyalty points daily at midnight
Schedule::job(new ExpireLoyaltyPoints)
    ->dailyAt('00:00')
    ->name('expire-loyalty-points')
    ->withoutOverlapping()
    ->onOneServer();

// Prune old database notifications (older than 90 days)
Schedule::command('model:prune', ['--model' => 'Illuminate\Notifications\DatabaseNotification'])
    ->daily()
    ->name('prune-notifications');

// Clean old logs (older than 30 days)
Schedule::command('log:clear --keep=30')
    ->weekly()
    ->name('clear-old-logs');

// Clear expired cache
Schedule::command('cache:prune-stale-tags')
    ->hourly()
    ->name('prune-cache-tags');

// Optimize application for production (rebuild caches)
Schedule::command('optimize')
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->name('weekly-optimize');
