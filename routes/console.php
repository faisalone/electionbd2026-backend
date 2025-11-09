<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Winner selection is handled automatically by PollObserver when poll status changes to 'ended'
// Manual command available: php artisan polls:select-winners