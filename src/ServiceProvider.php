<?php

namespace NickDeKruijk\Webshop;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
//         $this->loadViewsFrom(__DIR__.'/views', 'webshop');
        $this->publishes([
            __DIR__.'/config.php' => config_path('webshop.php'),
        ], 'config');
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        if (config('webshop.migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/migrations');
        }
//         $this->loadRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'webshop');

        // Register the main class to use with the facade
        $this->app->singleton('nickdekruijk-webshop', function () {
            return new Webshop;
        });
    }
}
