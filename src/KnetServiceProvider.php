<?php

namespace SmartLInks\LaravelKnet;

use Illuminate\Support\ServiceProvider;

class KnetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/knet.php',
            'knet'
        );

        $this->app->singleton('knet', function ($app) {
            return new KnetService(
                $app['config']->get('knet', [])
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/knet.php' => config_path('knet.php'),
        ], 'knet-config');
    }
}