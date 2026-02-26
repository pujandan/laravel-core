<?php

namespace DaniarDev\LaravelCore;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class LaravelCoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-core.php',
            'laravel-core'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/laravel-core.php' => config_path('laravel-core.php'),
        ], 'laravel-core-config');

        // Register Blueprint macros
        $this->registerBlueprintMacros();
    }

    /**
     * Register Blueprint macros for migrations
     *
     * @return void
     */
    protected function registerBlueprintMacros(): void
    {
        // Include blueprint macros file
        $macroPath = __DIR__ . '/../config/blueprint-macros.php';
        if (file_exists($macroPath)) {
            require_once $macroPath;
        }
    }
}