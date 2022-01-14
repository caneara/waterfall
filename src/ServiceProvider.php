<?php declare(strict_types=1);

namespace Waterfall;

use Illuminate\Support\ServiceProvider as Provider;

class ServiceProvider extends Provider
{
    /**
     * Bootstrap any package services.
     *
     */
    public function boot() : void
    {
        $this->publishes([
            __DIR__ . '/../config/waterfall.php' => config_path('waterfall.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     */
    public function register() : void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/waterfall.php', 'waterfall');
    }
}
