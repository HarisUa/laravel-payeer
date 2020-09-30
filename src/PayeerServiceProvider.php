<?php

namespace Haris\Payeer;

use Illuminate\Support\ServiceProvider;

class PayeerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/payeer.php' => config_path('payeer.php'),
        ], 'config');

        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/payeer.php', 'payeer');

        $this->app->singleton('payeer', function () {
            return $this->app->make(Payeer::class);
        });

        $this->app->alias('payeer', 'Payeer');
    }
}