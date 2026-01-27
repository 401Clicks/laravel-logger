<?php

namespace Clicks\Logger;

use Illuminate\Support\ServiceProvider;

class ClicksLoggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/clicks-logger.php', 'clicks-logger');

        // Extend the log manager to add our custom driver
        $this->app->make('log')->extend('clicks', function ($app, array $config) {
            return new ClicksLogger($config);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/clicks-logger.php' => config_path('clicks-logger.php'),
            ], 'clicks-logger-config');
        }

        // Auto-register the logging channel if not already defined
        $this->registerLoggingChannel();
    }

    protected function registerLoggingChannel(): void
    {
        $config = $this->app['config'];

        // Only add if the channel doesn't already exist
        if (! $config->has('logging.channels.clicks')) {
            $config->set('logging.channels.clicks', [
                'driver' => 'clicks',
                'url' => env('CLICKS_LOG_URL', 'https://logs.401clicks.com/api/v1/logs'),
                'token' => env('CLICKS_API_TOKEN'),
                'level' => env('CLICKS_LOG_LEVEL', 'debug'),
                'bubble' => true,
            ]);
        }
    }
}
