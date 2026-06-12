<?php

namespace App\Providers;

use App\Auth\CustomPasswordBrokerManager;
use App\Listeners\StripeWebhookListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('auth.password', function ($service, $app) {
            return new CustomPasswordBrokerManager($app);
        });
    }

    public function boot(): void
    {
        Event::listen(WebhookReceived::class, StripeWebhookListener::class);
    }
}
