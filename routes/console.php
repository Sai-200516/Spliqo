<?php

use App\Console\Commands\ProcessRecurringExpenses;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run recurring expense processing every day at 00:05
Schedule::command(ProcessRecurringExpenses::class)->dailyAt('00:05');
