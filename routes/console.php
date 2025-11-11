<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Winner selection is handled automatically by PollObserver when poll status changes to 'ended'
// Manual command available: php artisan polls:select-winners

// 🔄 AUTOMATED HOURLY NEWS GENERATION
// Generates news every hour from Google News (last 1 hour) at random minutes
// Topics: নির্বাচন, ভোট, রাজনীতি
// Runs at random minute (0-59) each hour for natural timing
$randomMinute = rand(0, 59);
Schedule::command('news:generate --all')
    ->cron("{$randomMinute} * * * *") // ✅ RUNS AT MINUTE {$randomMinute} EVERY HOUR
    ->timezone('Asia/Dhaka')
    ->withoutOverlapping()
    ->runInBackground()
    ->onSuccess(function () use ($randomMinute) {
        Log::info("✅ Hourly news generation completed successfully at minute {$randomMinute}");
    })
    ->onFailure(function () use ($randomMinute) {
        Log::error("❌ Hourly news generation failed at minute {$randomMinute}");
    });