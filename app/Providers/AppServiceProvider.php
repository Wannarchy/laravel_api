<?php

namespace App\Providers;

use App\Auth\CustomPasswordBrokerManager;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
