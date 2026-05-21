<?php

namespace App\Providers;

use App\Services\AnalyticsService;
use App\Services\BalanceService;
use App\Services\DebtSimplifier;
use App\Services\NotificationService;
use App\Services\OcrService;
use App\Services\PaymentService;
use App\Services\SplitEngine;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SplitEngine::class);
        $this->app->singleton(DebtSimplifier::class);
        $this->app->singleton(BalanceService::class);
        $this->app->singleton(AnalyticsService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(OcrService::class);
        $this->app->singleton(PaymentService::class);
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
