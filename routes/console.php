<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule subscription management commands
Schedule::command('subscriptions:expire')
    ->daily()
    ->at('00:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('subscriptions:activate-scheduled')
    ->daily()
    ->at('00:01')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();

// Schedule OneCard merchants sync every 24 hours
Schedule::command('merchants:sync-onecard')
    ->daily()
    ->at('01:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('products:sync-onecard')
    ->daily()
    ->at('01:30')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();

// Schedule Like4App merchants sync every 24 hours
Schedule::command('merchants:sync-like4app')
    ->daily()
    ->at('02:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();
