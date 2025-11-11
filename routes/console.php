<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Winner selection is handled automatically by PollObserver when poll status changes to 'ended'
// Manual command available: php artisan polls:select-winners

// 🔄 AUTOMATED HOURLY NEWS GENERATION
// Generates news every hour from Google News (last 1 hour)
// Topics: নির্বাচন, ভোট, রাজনীতি
Schedule::command('news:generate --all')
    ->hourly() // ✅ RUNS EVERY HOUR
    ->timezone('Asia/Dhaka')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () {
        Log::info('✅ Hourly news generation completed successfully');
    })
    ->onFailure(function () {
        Log::error('❌ Hourly news generation failed');
    });