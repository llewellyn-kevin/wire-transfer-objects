<?php

namespace LlewellynKevin\WireTransferObjects;

use Illuminate\Support\ServiceProvider;

class WireTransferObjectsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('wire-transfer-objects.php'),
            ], 'config');

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'wire-transfer-objects');

        // Register the main class to use with the facade
        $this->app->singleton('wire-transfer-objects', function () {
            return new WireTransferObjects;
        });
    }
}
